<?php

namespace App\Repository\Branches;

use App\Support\ScopeKey;
use App\Models\BranchModel;
use App\Repository\BaseCacheRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class BranchRepository extends BaseCacheRepository
{
    protected string $cachePrefix = 'branch_cache_';
    protected int $cacheMinutes = 30;
    public function buildBaseQuery()
    {
        $query = BranchModel::query()
            ->joinUserRole();
        return $query;
    }
    protected function buildListQuery(): Builder
    {
        return $this->buildBaseQuery()
            ->select([
                'branches.id_branch',
                'branches.created_by',
                'branches.updated_by',
                'branches.branch_code',
                'branches.name',
                'branches.address',
                'branches.is_active',
                'branches.status',

                'creator.name as creator_name',
                'updater.name as updater_name',
                'creator_role.name as creator_role_name',
                'updater_role.name as updater_role_name',

                'branches.created_at',
                'branches.updated_at'
            ])
            ->with(['branchPhone:id_branch,phone'])
            ->withCount('users');
    }
    protected function getData(string|int|null $scopeKey, array $filters = []): LengthAwarePaginator
    {
        $query = $this->buildListQuery();
        $this->applyScope($query, $scopeKey);
        $this->filterKeyword($query, $filters);
        [$column, $direction] = $this->resolveSorting($filters);
        $limit = min((int) ($filters['limit'] ?? 10), 100);
        return $query
            ->orderBy($column, $direction)
            ->paginate($limit);
    }

    protected function resolveSorting(array $filters): ?array
    {
        return match ($filters['order_by'] ?? 'latest') {
            'oldest' => ['branches.created_at', 'asc'],
            default => ['branches.created_at', 'desc'],
        };
    }

    protected function filterKeyword(Builder $query, array $filters)
    {
        if (!empty($filters['keyword'])) {
            $search = $filters['keyword'];
            $cleanSearch = addcslashes($search, '%_');
            $query->where(function ($subQuery) use ($cleanSearch) {
                $subQuery->where('branches.name', 'like', "%{$cleanSearch}%")
                    ->orWhereHas('branchPhone', function ($q) use ($cleanSearch) {
                        $q->where('phone', 'like', "%{$cleanSearch}%");
                    });
            });
        }
    }


    protected function applyScope(Builder $query, string|int|null $scopeKey): void
    {
        // Semua user yang memiliki permission boleh melihat seluruh daftar cabang,
        // sehingga tidak ada filter WHERE id_branch yang diterapkan.
        if (
            $scopeKey === null ||
            $scopeKey === '' ||
            in_array($scopeKey, [ScopeKey::GLOBAL, ScopeKey::GLOBAL_SUPER_ADMIN], true) ||
            str_starts_with((string) $scopeKey, 'branch_') ||
            str_starts_with((string) $scopeKey, 'user_')
        ) {
            return;
        }

        throw new \InvalidArgumentException("Scope key tidak dikenal: {$scopeKey}");
    }
}
