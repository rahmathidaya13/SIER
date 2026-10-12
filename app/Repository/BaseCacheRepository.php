<?php

namespace App\Repository;

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
    // Lock config (anti race condition / cache stampede)
    protected int $lockSeconds = 5;

    /** Durasi lock (detik). */
    protected int $lockWaitSeconds = 3;

    /** Lama menunggu lock sebelum fallback ke DB (detik). */
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

    /**
     * KEY VERSION (inti dari unicorn scaling)
     * Sekarang berdasarkan Cabang/Toko, bukan User.
     */
    protected function getVersionKey(string|int|null $scopeKey): string
    {
        return "{$this->cachePrefix}_version_scope_{$scopeKey}";
    }

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
            $this->trackMetricRealtime('miss', $start);
            // $this->maybeCleanupOldCache($scopeKey);

            return $result;
        } catch (LockTimeoutException $e) {
            // Jika antrean lock terlalu lama, langsung tembak ke DB agar user tidak stuck loading
            return $this->getData($scopeKey, $filters);
        } finally {
            optional($lock)->release();
        }
    }

    protected function getVersion(string|int|null $scopeKey): int
    {
        $key = $this->getVersionKey($scopeKey);

        if (!Cache::has($key)) {
            Cache::forever($key, 1);
        }

        return Cache::get($key);
    }

    /**
     * Build cache key unik berdasarkan parameter pencarian
     */

    protected function buildCacheKey(string|int|null $scopeKey, array $filters = []): string
    {
        // Pastikan pagination ikut menjadi bagian dari key
        $filters = array_merge($filters, [
            'page' => request()->get('page', 1),
        ]);

        $filters = array_filter($filters, function ($value) {
            return $value !== null && $value !== '';
        });

        ksort($filters);

        $version = $this->getVersion($scopeKey);
        return "{$this->cachePrefix}_scope_{$scopeKey}_v{$version}_" . md5(json_encode($filters));
    }



    protected function trackMetric($type, $startTime): void
    {
        $duration = round((microtime(true) - $startTime) * 1000, 2);

        $metricKey = "metrics_{$this->cachePrefix}";

        $metrics = Cache::get($metricKey, [
            'hit' => 0,
            'miss' => 0,
            'total_time' => 0,
            'count' => 0,
        ]);

        $metrics[$type]++;
        $metrics['total_time'] += $duration;
        $metrics['count']++;

        Cache::put($metricKey, $metrics, now()->addMinutes(60));
    }
    protected function trackMetricRealtime($type, $startTime): void
    {
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $key = "metrics_log_{$this->cachePrefix}";

        $logs = Cache::get($key, []);

        $logs[] = [
            'type' => $type,
            'time' => now()->timestamp,
            'duration' => $duration,
        ];

        // 🔥 batasi biar tidak bengkak
        if (count($logs) > 100) {
            array_shift($logs);
        }

        Cache::put($key, $logs, now()->addMinutes(10));
    }
    public function getMetrics(): array
    {
        $metrics = Cache::get("metrics_{$this->cachePrefix}", []);

        if (empty($metrics)) {
            return [
                'hit' => 0,
                'miss' => 0,
                'hit_rate' => 0,
                'avg_time' => 0,
            ];
        }

        return [
            'hit' => $metrics['hit'],
            'miss' => $metrics['miss'],
            'hit_rate' => $metrics['count'] > 0
                ? round(($metrics['hit'] / $metrics['count']) * 100, 2)
                : 0,
            'avg_time' => $metrics['count'] > 0
                ? round($metrics['total_time'] / $metrics['count'], 2)
                : 0,
        ];
    }
    public function getMetricsRealtime(): array
    {
        $logs = Cache::get("metrics_log_{$this->cachePrefix}", []);

        $now = now()->timestamp;
        $window = 300; // 5 menit

        $recentLogs = array_filter($logs, function ($log) use ($now, $window) {
            return ($now - $log['time']) <= $window;
        });

        $hit = 0;
        $miss = 0;
        $totalTime = 0;

        foreach ($recentLogs as $log) {
            if ($log['type'] === 'hit') $hit++;
            if ($log['type'] === 'miss') $miss++;
            $totalTime += $log['duration'] ?? 0;
        }

        $total = $hit + $miss;

        return [
            'hit' => $hit,
            'miss' => $miss,
            'hit_rate' => $total > 0 ? round(($hit / $total) * 100, 2) : 0,
            'total' => $total,
            'avg_time' =>  $total > 0 ? round($totalTime / $total, 2) : 0,
        ];
    }
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
    /**
     * Encode payload (compress untuk hemat RAM)
     */
    protected function encodePayload(array $payload): string
    {
        return gzcompress(json_encode($payload));
    }

    /**
     * Decode payload
     */
    protected function decodePayload(string $payload): array
    {
        return json_decode(gzuncompress($payload), true);
    }

    /**
     * Clear semua cache user (super cepat via registry)
     */
    public function clearCache(string|int|null $scopeKey): void
    {
        if ($scopeKey === null || $scopeKey === '') {
            return;
        }

        $key = $this->getVersionKey($scopeKey);

        if (!Cache::has($key)) {
            Cache::forever($key, 1);
        }

        Cache::increment($key);
    }

    public function clearRelatedCaches(string|int|null $scopeKey): void
    {
        // selalu refresh global
        $this->clearCache('global');

        // refresh cabang terkait
        if ($scopeKey) {
            $this->clearCache($scopeKey);
        }
    }

    public function clearMultipleCaches(array $scopeKeys): void
    {
        foreach (array_unique(array_filter($scopeKeys)) as $scopeKey) {
            $this->clearCache($scopeKey);
        }
    }
    protected function maybeCleanupOldCache($scopeKey): void
    {
        // 10% kemungkinan untuk membersihkan sampah cache versi lama
        if (rand(1, 100) <= 10) {
            $version = $this->getVersion($scopeKey);

            // hapus versi lama (version - 2)
            $oldVersion = $version - 2;

            if ($oldVersion > 0) {
                Cache::forget("{$this->cachePrefix}_scope_{$scopeKey}_v{$oldVersion}_");
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
}
