<?php

namespace App\Repository\User;

use App\Models\User;
use App\Support\ScopeKey;
use App\Models\BranchModel;
use App\Repository\BaseCacheRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository extends BaseCacheRepository
{
    protected string $cachePrefix = 'user_cache';

    public function buildBaseQuery(): Builder
    {
        $query = User::query()
            ->leftJoin('model_has_roles', function ($join) {
                $join->on('model_has_roles.model_id', '=', 'users.id')
                    ->where('model_has_roles.model_type', '=', User::class); // Wajib filter tipe modelnya
            })
            ->leftJoin('roles', 'roles.id',  '=',  'model_has_roles.role_id')
            ->leftJoin('branches', 'branches.id_branch',  '=',  'users.id_branch');
        return $query;
    }
    protected function getData(string|int|null $scopeKey, array $filters = []): LengthAwarePaginator
    {
        $query = $this->buildBaseQuery()
            ->select([
                'users.id',
                'users.id_branch',
                'users.name',
                'users.email',
                'users.is_active',
                'users.created_at',
                'users.updated_at',
                'branches.name as branch_name',
                'roles.name as role_name'
            ]);
        $this->applyScope($query, $scopeKey);
        $this->resolveStatus($query, $filters);
        $this->filterBranch($query, $filters);
        $this->filterRole($query, $filters);
        $this->filterKeyword($query, $filters);

        $limit = min((int) ($filters['limit'] ?? 10), 100);
        [$column, $direction] = $this->resolveSorting($filters);
        return $query
            ->orderBy($column, $direction)
            ->paginate($limit);
    }
    protected function filterKeyword(Builder $query, array $filters): Builder
    {
        return $query->when(!empty($filters['keyword']), function ($q) use ($filters) {
            $search = $filters['keyword'];
            $cleanSearch = addcslashes($search, '%_');
            $q->where(function ($subQuery) use ($cleanSearch) {
                $subQuery->where('users.name', 'like', "%{$cleanSearch}%")
                    ->orWhere('users.email', 'like', "%{$cleanSearch}%");
            });
        });
    }
    protected function resolveStatus(Builder $query, array $filters)
    {
        return match ($filters['status'] ?? 'all') {
            'active' => $query->where('users.is_active', boolval(true)),
            'inactive' => $query->where('users.is_active', boolval(false)),
            'all' => $query,
            default => $query,
        };
    }
    protected function resolveSorting(array $filters): array
    {
        return match ($filters['sort_order'] ?? 'latest') {
            'oldest' => ['users.created_at', 'asc'],
            default => ['users.created_at', 'desc'],
        };
    }

    protected function filterBranch(Builder $query, array $filters): Builder
    {
        $branchName = $filters['branch'] ?? null;

        return $query->when(!empty($branchName) && $branchName !== 'all', function (Builder $q) use ($branchName) {
            $q->where('branches.name', $branchName);
        });
    }

    protected function filterRole(Builder $query, array $filters): Builder
    {
        return $query->when(!empty($filters['roles']) && $filters['roles'] !== 'all', function (Builder $q) use ($filters) {
            $q->where('roles.name', $filters['roles']);
        });
    }

    protected function applyScope(Builder $query, string|int|null $scopeKey): void
    {
        if ($scopeKey === null || $scopeKey === '') return;

        // 1. Scope GLOBAL (Khusus Developer): Bisa melihat SEMUA akun termasuk Developer
        if ($scopeKey === ScopeKey::GLOBAL) {
            return;
        }

        // 2. Scope GLOBAL_SUPER_ADMIN (Khusus Super Admin):
        // Bisa melihat semua cabang, TAPI sembunyikan akun Developer!
        if ($scopeKey === ScopeKey::GLOBAL_SUPER_ADMIN) {
            $query->where(function (Builder $q) {
                $q->whereNotIn('roles.name', ['developer', 'super_admin'])
                    ->orWhereNull('roles.name');
            });
            return;
        }

        // 3 & 4 Opsional jaga-jaga jika dibutuhkan
        // 3. Scope Cabang (Khusus Supervisor):
        // Hanya melihat pegawai di cabangnya sendiri, dan sembunyikan Developer & Super Admin
        if (str_starts_with((string) $scopeKey, 'branch_')) {
            $branchId = substr((string) $scopeKey, strlen('branch_'));

            $query->where('users.id_branch', $branchId)
                ->where(function (Builder $q) {
                    $q->whereNotIn('roles.name', ['developer', 'super_admin'])
                        ->orWhereNull('roles.name');
                });
            return;
        }

        // 4. Scope User (Jaga-jaga jika diakses per individu)
        if (str_starts_with((string) $scopeKey, 'user_')) {
            $userId = substr((string) $scopeKey, strlen('user_'));
            $query->where('users.id', $userId);
            return;
        }

        throw new \InvalidArgumentException("Scope key tidak dikenal: {$scopeKey}");
    }
}
