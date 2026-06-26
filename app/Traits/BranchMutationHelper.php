<?php

namespace App\Traits;

use Illuminate\Validation\ValidationException;

trait BranchMutationHelper
{
    /**
     * ==================================================
     * 1. FUNGSI OTORISASI UNIVERSAL
     * ==================================================
     */
    protected function authorizeBranchAction($model, string $moduleName = 'data ini'): bool
    {
        $user = auth()->user();

        // Super admin bebas akses semua cabang
        if ($user->hasRole('super_admin')) {
            return true;
        }
        // Supervisor hanya boleh cabangnya sendiri
        if (
            isset($model->id_branch) &&
            (string) $model->id_branch === (string) $user->id_branch
        ) {
            return true;
        }

        throw ValidationException::withMessages([
            'unauthorized_action' => "Anda tidak memiliki hak akses untuk mengubah {$moduleName}!"
        ]);
    }
    protected function invalidateCaches(string|array $repositories, array $scopeIds): void
    {
        foreach ((array)$repositories as $repoClass) {

            $repo = app($repoClass);

            if (method_exists($repo, 'clearMultipleCaches')) {
                $repo->clearMultipleCaches($scopeIds);
            }
        }
    }
}
