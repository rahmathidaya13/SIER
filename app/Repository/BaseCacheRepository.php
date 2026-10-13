<?php

declare(strict_types=1);

namespace App\Repository;

use Throwable;
use RuntimeException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Cache\LockTimeoutException;

abstract class BaseCacheRepository
{
    /** Prefix global untuk semua key. Override di child bila perlu. */
    protected string $cachePrefix = 'repository_cache';

    /** TTL default cache data (menit). Bisa di-override via property di child. */
    protected int $cacheMinutes = 60;

    /** TTL version key (hari). 0 = forever. */
    protected int $versionTtlDays = 30;

    /** Durasi lock (detik). */
    protected int $lockSeconds = 5;

    /** Lama menunggu lock sebelum fallback ke DB (detik). */
    protected int $lockWaitSeconds = 3;

    /** Jumlah log realtime metrics yang disimpan (sliding window). */
    protected int $realtimeWindowSeconds = 300;


    // ---------------------------------------------------------------------
    // ABSTRACT — wajib diimplementasikan child
    // ---------------------------------------------------------------------
    /**
     * Query sumber data (DB/Eloquent) yang akan di-cache.
     *
     * @param  array<string,mixed>  $filters
     * @return LengthAwarePaginator<int, mixed>
     */

    abstract protected function getData(string|int|null $scopeKey, array $filters = []): LengthAwarePaginator;

    // ---------------------------------------------------------------------
    // PUBLIC API
    // ---------------------------------------------------------------------

    /**
     * Ambil data dari cache, atau query DB jika miss.
     *
     * @param  array<string,mixed>  $filters
     * @param  int|null             $page     Halaman saat ini (null = ambil dari request).
     * @return LengthAwarePaginator<int, mixed>
     */

    public function getCached(string|int|null $scopeKey, array $filters = [], ?int $page = null): LengthAwarePaginator
    {
        // Scope null = data global (super admin). Bisa juga di-bypass total.
        $scopeKey =   $scopeKey === null || $scopeKey === '' ? 'global' : $scopeKey;
        $page     = $page ?? (int) (request()->get('page', 1));
        $cacheKey = $this->buildCacheKey($scopeKey, $filters);

        $start = microtime(true);

        // ✅ 1. HIT CACHE
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            $this->trackMetric('hit', $start);

            return $this->rebuildPaginator(
                $this->decodePayload($cached)
            );
        }

        // MISS PATH: lock per cache key ----
        $lockKey = 'lock_' . $cacheKey;
        $lock    = Cache::lock($lockKey, $this->lockSeconds);
        $locked  = false;

        // 🔒 2. LOCK (Anti Race Condition)
        // $lockKey = "lock_{$this->cachePrefix}_scope_{$scopeKey}";
        // $lock = Cache::lock($lockKey, $this->lockSeconds);

        try {
            $lock->block($this->lockWaitSeconds); // Tunggu sampai lock tersedia
            $locked = true;

            // Double-check: siapa tahu request sebelumnya sudah mengisi cache.
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                $this->trackMetric('hit', $start);

                return $this->rebuildPaginator(
                    $this->decodePayload($cached)
                );
            }

            // 🚀 3. QUERY DB (Panggil fungsi dari class turunannya)
            $result = $this->getData($scopeKey, $filters);
            $payload = $this->paginatorToPayload($result);

            // 💾 4. SIMPAN CACHE (dengan kompresi)
            Cache::put(
                $cacheKey,
                $this->encodePayload($payload),
                now()->addMinutes($this->cacheMinutes)
            );

            $this->trackMetric('miss', $start);
            return $result;
        } catch (LockTimeoutException $e) {
            // Lock timeout → fallback langsung ke DB, jangan biarkan user menunggu.
            Log::warning('Cache lock timeout, fallback ke DB', [
                'lock_key' => $lockKey,
                'scope'    => $scopeKey,
            ]);
            $this->trackMetric('miss', $start);

            return $this->getData($scopeKey, $filters);
        } finally {
            // Hanya release kalau memang kita yang acquire.
            if ($locked) {
                try {
                    $lock->release();
                } catch (Throwable) {
                    // ignore — TTL akan expire sendiri.
                }
            }
        }
    }

    /**
     * Invalidasi cache untuk 1 scope (increment version = O(1)).
     */
    public function clearCache(string|int|null $scopeKey): void
    {
        $scopeKey = $scopeKey === null || $scopeKey === '' ? 'global' : $scopeKey;
        $key      = $this->getVersionKey($scopeKey);

        // Pastikan key ada supaya INCR tidak bikin key tanpa TTL (tergantung driver).
        $this->ensureVersionKey($key);

        Cache::increment($key);
    }

    /**
     * Invalidasi cache untuk scope terkait + global.
     */
    public function clearRelatedCaches(string|int|null $scopeKey): void
    {
        // selalu refresh global
        $this->clearCache('global');

        if ($scopeKey !== null && $scopeKey !== '' && $scopeKey !== 'global') {
            $this->clearCache($scopeKey);
        }
    }

    /**
     * Invalidasi banyak scope sekaligus.
     *
     * @param  array<int,string|int|null>  $scopeKeys
     */
    public function clearMultipleCaches(array $scopeKeys): void
    {
        $unique = array_unique(
            array_filter(
                $scopeKeys,
                static fn($v) => $v !== null && $v !== ''
            )
        );
        foreach ($unique as $scopeKey) {
            $this->clearCache($scopeKey);
        }
    }

    /**
     * Statistik agregat sejak terakhir di-reset.
     *
     * @return array{hit:int,miss:int,hit_rate:float,avg_time:float,total:int}
     */
    public function getMetrics(): array
    {
        $hit    = (int) Cache::get($this->metricKey('hit'), 0);
        $miss   = (int) Cache::get($this->metricKey('miss'), 0);
        $timeMs = (int) Cache::get($this->metricKey('time_ms'), 0);
        $total  = $hit + $miss;

        return [
            'hit'      => $hit,
            'miss'     => $miss,
            'total'    => $total,
            'hit_rate' => $total > 0 ? round(($hit / $total) * 100, 2) : 0.0,
            'avg_time' => $total > 0 ? round($timeMs / $total, 2) : 0.0,
        ];
    }

    /**
     * Statistik realtime (sliding window sederhana).
     *
     * @return array{hit:int,miss:int,hit_rate:float,total:int,avg_time:float}
     */
    public function getMetricsRealtime(): array
    {
        $logKey = $this->metricKey('realtime_log');
        $logs   = Cache::get($logKey, []);

        $now    = now()->timestamp;
        $cutoff = $now - $this->realtimeWindowSeconds;

        $hit = $miss = $totalMs = 0;

        foreach ($logs as $log) {
            if (($log['time'] ?? 0) < $cutoff) {
                continue;
            }
            $totalMs += (int) ($log['duration_ms'] ?? 0);

            if (($log['type'] ?? '') === 'hit') {
                $hit++;
            } elseif (($log['type'] ?? '') === 'miss') {
                $miss++;
            }
        }

        $total = $hit + $miss;

        return [
            'hit'      => $hit,
            'miss'     => $miss,
            'total'    => $total,
            'hit_rate' => $total > 0 ? round(($hit / $total) * 100, 2) : 0.0,
            'avg_time' => $total > 0 ? round($totalMs / $total, 2) : 0.0,
        ];
    }

    // ---------------------------------------------------------------------
    // INTERNAL — key building
    // ---------------------------------------------------------------------
    protected function getVersionKey(string|int|null $scopeKey): string
    {
        return "{$this->cachePrefix}_version_scope_{$scopeKey}";
    }
    protected function getVersion(string|int|null $scopeKey): int
    {
        $key = $this->getVersionKey($scopeKey);

        // rememberForever menghindari race `has() + forever()` dan 1 round-trip.
        return (int) Cache::rememberForever($key, static fn() => 1);
    }

    /**
     * Pastikan version key ada (idempotent).
     */
    protected function ensureVersionKey(string $key): void
    {
        if (!Cache::has($key)) {
            $ttl = $this->versionTtlDays > 0
                ? now()->addDays($this->versionTtlDays)
                : null;

            if ($ttl !== null) {
                // add() = set-if-not-exists → aman dari race.
                Cache::add($key, 1, $ttl);
            } else {
                Cache::forever($key, 1);
            }
        }
    }

    /**
     * Build cache key unik berdasarkan parameter pencarian
     */

    protected function buildCacheKey(string|int|null $scopeKey, array $filters = [], ?int $page = null): string
    {
        // Pastikan pagination ikut menjadi bagian dari key

        $page ??= (int) (request()->get('page', 1));

        // Gabung page ke filters supaya bisa di-hash bersama.
        $filters['page'] = $page;

        // Normalisasi: buang null dan string kosong, TAPI pertahankan 0/false.
        $filters = array_filter(
            $filters,
            static fn($v) => $v !== null && $v !== ''
        );

        // Deterministic order (termasuk nested, walau nested harus benar2 dinormalisasi di sini).
        $this->ksortRecursive($filters);

        $version = $this->getVersion($scopeKey);
        return sprintf(
            '%s_scope_%s_v%d_%s',
            $this->cachePrefix,
            $scopeKey,
            $version,
            md5(json_encode($filters, JSON_THROW_ON_ERROR))
        );
    }

    /**
     * Ksort rekursif supaya nested array juga deterministik.
     */
    protected function ksortRecursive(array &$array): void
    {
        ksort($array);

        foreach ($array as &$value) {
            if (is_array($value)) {
                $this->ksortRecursive($value);
            }
        }
    }

    // ---------------------------------------------------------------------
    // INTERNAL — payload (de)serialization
    // ---------------------------------------------------------------------

    /**
     * @param  LengthAwarePaginator<int, mixed>  $p
     * @return array{data:array,total:int,per_page:int,current_page:int}
     */

    protected function paginatorToPayload(LengthAwarePaginator $p): array
    {
        return [
            'data'         => collect($p->items())->toArray(),
            'total'        => $p->total(),
            'per_page'     => $p->perPage(),
            'current_page' => $p->currentPage(),
        ];
    }
    /**
     * Encode payload (compress untuk hemat RAM)
     */
    protected function encodePayload(array $payload): string
    {
        $json = json_encode($payload, JSON_THROW_ON_ERROR);
        $gz   = gzcompress($json, 6);

        if ($gz === false) {
            throw new RuntimeException('Gagal compress cache payload');
        }

        return $gz;
    }

    /**
     * @return array{data:array,total:int,per_page:int,current_page:int}
     */
    protected function decodePayload(string $payload): array
    {
        $raw = @gzuncompress($payload);

        if ($raw === false) {
            // Cache korup — log lalu lempar supaya caller fallback ke DB.
            throw new RuntimeException('Gagal decompress cache payload');
        }

        return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array{data:array,total:int,per_page:int,current_page:int}  $data
     * @return LengthAwarePaginator<int, mixed>
     */
    protected function rebuildPaginator(array $data): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            $data['data'],
            $data['total'],
            $data['per_page'],
            $data['current_page'],
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }

    // ---------------------------------------------------------------------
    // INTERNAL — metrics
    // ---------------------------------------------------------------------

    protected function metricKey(string $suffix): string
    {
        return "metrics_{$this->cachePrefix}_{$suffix}";
    }

    /**
     * Counter atomic — pakai increment, bukan read-modify-write.
     */
    protected function trackMetric($type, $startTime): void
    {
        $durationMs = (int) round((microtime(true) - $startTime) * 1000);
        $ttl        = now()->addMinutes(60);

        $this->incrementWithTtl($this->metricKey($type), 1, $ttl);
        $this->incrementWithTtl($this->metricKey('time_ms'), $durationMs, $ttl);

        $this->trackMetricRealtime($type, $durationMs);
    }

    /**
     * Simpan log realtime di cache list (dibatasi ukuran).
     */
    protected function trackMetricRealtime($type, $durationMs): void
    {
        $key  = $this->metricKey('realtime_log');
        $logs = Cache::get($key, []);

        $logs[] = [
            'type'        => $type,
            'time'        => now()->timestamp,
            'duration_ms' => $durationMs,
        ];

        // Batasi panjang log (bukan array_shift di setiap loop, tapi slice).
        if (count($logs) > 200) {
            $logs = array_slice($logs, -200);
        }

        Cache::put($key, $logs, now()->addMinutes(10));
    }

    /**
     * Increment dengan TTL — hindari key tanpa TTL setelah INCR.
     */

    protected function incrementWithTtl(string $key, int $amount, \DateTimeInterface $ttl): void
    {
        if (! Cache::has($key)) {
            // add() = SET NX, jadi race-safe.
            Cache::add($key, 0, $ttl);
        }

        Cache::increment($key, $amount);
    }
}
