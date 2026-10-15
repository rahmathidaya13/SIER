<?php

namespace App\Traits;

use Throwable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Repository\BaseCacheRepository;

trait InvalidatesCaches
{
    public function invalidateCaches(string|array $repositories, array $scopeKeys, bool $afterCommit = true): void
    {
        $repositories = array_unique((array) $repositories);
        $scopeKeys    = array_values(array_unique(array_filter(
            $scopeKeys,
            static fn($v) => $v !== null && $v !== ''
        )));

        if ($scopeKeys === []) {
            return;
        }

        $run = function () use ($repositories, $scopeKeys): void {
            foreach ($repositories as $repoClass) {
                try {
                    $repo = app($repoClass);

                    if (!$repo instanceof BaseCacheRepository) {
                        Log::warning('Invalidasi cache dilewati: bukan BaseCacheRepository', [
                            'repository' => $repoClass,
                        ]);

                        continue;
                    }

                    $repo->clearMultipleCaches($scopeKeys);
                } catch (Throwable $e) {
                    // Jangan biarkan 1 repo gagal menghentikan invalidasi repo lain.
                    Log::error('Gagal invalidasi cache repository', [
                        'repository' => $repoClass,
                        'scope_keys' => $scopeKeys,
                        'exception'  => $e,
                    ]);
                }
            }
        };

        // ✅ Jalankan SETELAH DB commit untuk menghindari stale cache.
        if ($afterCommit && DB::transactionLevel() > 0) {
            DB::afterCommit($run);
            return;
        }

        $run();
    }
}
