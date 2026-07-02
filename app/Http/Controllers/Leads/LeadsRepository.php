<?php

namespace App\Http\Controllers\Leads;

use App\Models\User;
use App\Models\LeadModel;
use App\Repository\BaseCacheRepository;
use Illuminate\Pagination\LengthAwarePaginator;


class LeadsRepository extends BaseCacheRepository
{
    protected string $cachePrefix = 'leads_cache';
    protected function getData(string|int|null $branchId, array $filters = []): LengthAwarePaginator
    {
        $query = LeadModel::query()
            ->allowedForUser(auth()->user())
            ->leftJoin('users', 'users.id', '=', 'leads.id_user')
            ->leftJoin('channels', 'channels.id_channel', '=', 'leads.id_channel')
            ->leftJoin('products', 'products.id_product', '=', 'leads.id_product')
            ->select([
                'leads.id_lead',
                'leads.name',
                'leads.email',
                'leads.phone',
                'leads.temperature',
                'leads.status',
                'leads.follow_up_date',
                'leads.notes',
                'leads.created_at',
                'leads.updated_at',
                'users.name as creator_name',
                'channels.name as channel_name',
                'channels.icon',
                'products.name as product_name',
            ]);


        $status = $this->resolveStatus($filters);
        $query->when(
            !empty($status),
            fn($q) => $q->where('leads.status', $status)
        );
        $temperature = $this->resolveTemperature($filters);
        $query->when(!empty($temperature), function ($query) use ($filters) {
            $query->where('leads.temperature', $filters['temperature']);
        });

        $query->when(!empty($filters['keyword']), function ($q) use ($filters) {
            $search = $filters['keyword'];

            $q->where(function ($subQuery) use ($search) {
                $subQuery->where('leads.name', 'like', "%{$search}%")
                    ->orWhere('leads.phone', 'like', "%{$search}%");
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
    protected function resolveSorting(array $filters): ?array
    {
        return match ($filters['order_by'] ?? 'latest') {

            'oldest' => ['leads.created_at', 'asc'],

            default => ['leads.created_at', 'desc'],
        };
    }
}
