<?php

namespace App\Repository\Branches;

use App\Models\BranchModel;
use App\Repository\BaseCacheRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class BranchRepository extends BaseCacheRepository
{
    protected string $cachePrefix = 'branch_cache_';
    public function buildBaseQuery(array $filters = [])
    {
        $query = BranchModel::query()
            ->select([
                'id_branch',
                'created_by',
                'branch_code',
                'name',
                'address',
                'is_active',
                'status',
                'created_at',
                'updated_at'
            ])
            ->with(['branchPhone:id_branch,phone'])
            ->withCount('users');

        $query->when(!empty($filters['keyword']), function ($q) use ($filters) {
            $search = $filters['keyword'];
            $cleanSearch = addcslashes($search, '%_');
            $q->where(function ($subQuery) use ($cleanSearch) {
                $subQuery->where('branches.name', 'like', "%{$cleanSearch}%")
                    ->orWhereHas('branchPhone', function ($q) use ($cleanSearch) {
                        $q->where('phone', 'like', "%{$cleanSearch}%");
                    });
            });
        });
        return $query;
    }

    protected function getData(string|int|null $branchId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->buildBaseQuery($filters);
        [$column, $direction] = $this->resolveSorting($filters);
        $limit = min((int) ($filters['limit'] ?? 10), 100);
        return $query
            ->orderBy($column, $direction)
            ->paginate($limit)
            ->withQueryString();
    }

    protected function resolveSorting(array $filters): ?array
    {
        return match ($filters['order_by'] ?? 'latest') {
            'oldest' => ['branches.created_at', 'asc'],
            default => ['branches.created_at', 'desc'],
        };
    }
}
