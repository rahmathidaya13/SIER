<?php

namespace App\Repository;

use Illuminate\Support\Facades\Cache;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Cache\LockTimeoutException;

abstract class BaseCacheRepository
{
    protected string $cachePrefix = 'repository_cache';
    protected int $cacheMinutes = 60; // Bisa dibuat lebih lama (misal 60 menit) karena invalidasi instan

    // Lock config (anti race condition / cache stampede)
    protected int $lockSeconds = 5;
    protected int $lockWaitSeconds = 3;

    // Abstract method yang wajib diisi oleh Repository turunannya
    abstract protected function getData(string|int|null $scopeId, array $filters = []): LengthAwarePaginator;

    /**
     * KEY VERSION (inti dari unicorn scaling)
     * Sekarang berdasarkan Cabang/Toko, bukan User.
     */
    protected function getVersionKey(string|int|null $scopeId): string
    {
        return "{$this->cachePrefix}_version_scope_{$scopeId}";
    }

    /**
     * Ambil versi cache (default 1)
     */
    protected function getVersion(string|int|null $scopeId): int
    {
        $key = $this->getVersionKey($scopeId);

        if (!Cache::has($key)) {
            Cache::forever($key, 1);
        }

        return (int) Cache::get($key);
    }

    /**
     * Build cache key unik berdasarkan parameter pencarian
     */

    protected function buildCacheKey(string|int|null $scopeId, array $filters = []): string
    {
        // Pastikan pagination ikut menjadi bagian dari key
        $filters = array_merge($filters, [
            'page' => request()->get('page', 1),
        ]);

        $filters = array_filter($filters, function ($value) {
            return $value !== null && $value !== '';
        });

        ksort($filters);

        $version = $this->getVersion($scopeId);
        return "{$this->cachePrefix}_scope_{$scopeId}_v{$version}_" . md5(json_encode($filters));
    }

    /**
     * Ambil data dari cache atau DB
     */
    public function getCached(string|int|null $scopeId, array $filters = []): LengthAwarePaginator
    {
        // Jika tidak ada scopeId (misal super_admin yang melihat data global), bypass cache atau set ke 'global'
        $scopeId ??= 'global';

        $start = microtime(true);
        $cacheKey = $this->buildCacheKey($scopeId, $filters);

        // ✅ 1. HIT CACHE
        if (Cache::has($cacheKey)) {
            $this->trackMetric('hit', $start);
            $this->trackMetricRealtime('hit', $start);

            return $this->rebuildPaginator(
                $this->decodePayload(Cache::get($cacheKey))
            );
        }

        // 🔒 2. LOCK (Anti Race Condition)
        $lockKey = "lock_{$this->cachePrefix}_scope_{$scopeId}";
        $lock = Cache::lock($lockKey, $this->lockSeconds);

        try {
            $lock->block($this->lockWaitSeconds); // Tunggu sampai lock tersedia

            // Double check setelah mendapat lock (siapa tahu antrean sebelumnya sudah membuat cache-nya)
            if (Cache::has($cacheKey)) {
                $this->trackMetric('hit', $start);
                $this->trackMetricRealtime('hit', $start);

                return $this->rebuildPaginator(
                    $this->decodePayload(Cache::get($cacheKey))
                );
            }

            // 🚀 3. QUERY DB (Panggil fungsi dari class turunannya)
            $result = $this->getData($scopeId, $filters);

            $payload = [
                'data'         => collect($result->items())->toArray(),
                'total'        => $result->total(),
                'per_page'     => $result->perPage(),
                'current_page' => $result->currentPage(),
            ];

            // 💾 4. SIMPAN CACHE (dengan kompresi)
            Cache::put(
                $cacheKey,
                $this->encodePayload($payload),
                now()->addMinutes($this->cacheMinutes)
            );

            $this->trackMetric('miss', $start);
            $this->trackMetricRealtime('miss', $start);
            // $this->maybeCleanupOldCache($scopeId);

            return $result;
        } catch (LockTimeoutException $e) {
            // Jika antrean lock terlalu lama, langsung tembak ke DB agar user tidak stuck loading
            return $this->getData($scopeId, $filters);
        } finally {
            optional($lock)->release();
        }
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
    public function clearCache(string|int|null $scopeId): void
    {
        if (!$scopeId) return;

        $key = $this->getVersionKey($scopeId);

        $current = (int) Cache::get($key, 1);
        Cache::forever($key, $current + 1);
    }

    public function clearRelatedCaches(string|int|null $scopeId): void
    {
        // selalu refresh global
        $this->clearCache('global');

        // refresh cabang terkait
        if ($scopeId) {
            $this->clearCache($scopeId);
        }
    }

    public function clearMultipleCaches(array $scopeIds): void
    {
        foreach (array_unique(array_filter($scopeIds)) as $scopeId) {
            $this->clearCache($scopeId);
        }
    }
    protected function maybeCleanupOldCache($scopeId): void
    {
        // 10% kemungkinan untuk membersihkan sampah cache versi lama
        if (rand(1, 100) <= 10) {
            $version = $this->getVersion($scopeId);

            // hapus versi lama (version - 2)
            $oldVersion = $version - 2;

            if ($oldVersion > 0) {
                Cache::forget("{$this->cachePrefix}_scope_{$scopeId}_v{$oldVersion}_");
            }
        }
    }
}
