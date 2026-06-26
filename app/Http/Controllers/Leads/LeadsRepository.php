<?php

namespace App\Http\Controllers\Leads;

use App\Models\LeadModel;
use App\Repository\BaseCacheRepository;
use Illuminate\Pagination\LengthAwarePaginator;

use function Symfony\Component\Translation\t;

class LeadsRepository extends BaseCacheRepository
{
    protected string $cachePrefix = 'leads_cache';
    protected function getData(string|int|null $branchId, array $filters = []): LengthAwarePaginator
    {
        $query = LeadModel::allowedForUser()
            ->with([
                'user:id,name as creator_name',
                'channel:id_channel,name as channel_name,icon',
                'product:id_product,name as product_name',
                'activity.user:id,name as activity_creator_name',
            ]);


        $status = $this->resolveStatus($filters);
        $query->when(
            !empty($status),
            fn($q) => $q->where('status', $status)
        );
        $temperature = $this->resolveTemperature($filters);
        $query->when(!empty($temperature), function ($query) use ($filters) {
            $query->where('temperature', $filters['temperature']);
        });

        $query->when(!empty($filters['keyword']), function ($q) use ($filters) {
            $search = $filters['keyword'];

            $q->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        });

        [$column, $direction] = $this->resolveSorting($filters);
        return $query
            ->orderBy($column, $direction)
            ->paginate($filters['limit'] ?? 10);
    }

    protected function resolveTemperature(array $filters): ?string
    {
        return match ($filters['temperature'] ?? 'all') {
            'hot' => 'HOT',
            'warm' => 'WARM',
            'cold' => 'COLD',
            default => null,
        };
    }
    protected function resolveStatus(array $filters): ?string
    {
        return match ($filters['status'] ?? 'all') {
            'new' => 'NEW',
            'follow_up' => 'FOLLOW_UP',
            'closed_won' => 'CLOSED_WON',
            'closed_lost' => 'CLOSED_LOST',
            default => null,
        };
    }
    protected function resolveSorting(array $filters): array
    {
        return match ($filters['order_by'] ?? 'latest') {

            'oldest' => ['created_at', 'asc'],

            default => ['created_at', 'desc'],
        };
    }
}
