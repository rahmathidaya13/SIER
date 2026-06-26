<?php

namespace App\Http\Controllers\Leads;

use App\Models\LeadModel;
use App\Repository\BaseCacheRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class LeadsRepository extends BaseCacheRepository
{
    protected string $cachePrefix = 'leads_cache';
    protected function getData(string|int|null $shopId, array $filters = []): LengthAwarePaginator
    {
        $user = auth()->user();

        $query = LeadModel::query()
            ->leftJoin('users', 'users.id',  '=',  'addons.user_id')
            ->leftJoin('shops', 'shops.shop_id',  '=', 'addons.shop_id')
            ->select([
                'addons.*',
                'users.name as creator_name',
                'shops.name as shop_name',
            ]);

        if ($shopId !== 'global') {
            $query->where('addons.shop_id', $user->shop_id);
        }

        $status = $this->resolveStatus($filters);
        $query->when(
            $status !== null,
            fn($q) => $q->where('addons.is_active', $status)
        );

        $query->when(!empty($filters['keyword']), function ($q) use ($filters) {
            $search = $filters['keyword'];

            $q->where(function ($subQuery) use ($search) {
                $subQuery->where('addons.name', 'like', "%{$search}%");
            });
        });


        [$column, $direction] = $this->resolveSorting($filters);
        return $query
            ->orderBy($column, $direction)
            ->paginate($filters['limit'] ?? 10);
    }
    protected function resolveStatus(array $filters): ?bool
    {
        return match ($filters['status'] ?? 'all') {
            'show' => true,
            'visible' => false,
            default => null,
        };
    }
    protected function resolveSorting(array $filters): array
    {
        return match ($filters['sort_order'] ?? 'latest') {

            'oldest' => ['addons.created_at', 'asc'],

            default => ['addons.created_at', 'desc'],
        };
    }
}
