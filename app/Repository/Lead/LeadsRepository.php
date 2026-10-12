<?php

namespace App\Repository\Lead;

use App\Models\LeadModel;
use App\Repository\BaseCacheRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;


class LeadsRepository extends BaseCacheRepository
{
    protected string $cachePrefix = 'leads_cache';

    protected function buildBaseQuery()
    {
        $query = LeadModel::query()
            ->allowedForUser()
            ->joinUserRole()
            ->leftJoin('channels', 'channels.id_channel', '=', 'leads.id_channel')
            ->leftJoin('sales', 'sales.id_lead', '=', 'leads.id_lead')
            ->leftJoin('branches', 'branches.id_branch', '=', 'users.id_branch');
        return $query;
    }
    protected function buildListQuery(): Builder
    {
        return $this->buildBaseQuery()
            ->with([
                // 'activity:id_lead,created_at',
                'leadProduct:id_lead_product,id_lead,id_product',
                'leadProduct.product:id_product,name',
                'activity' => function ($query) {
                    $query->select('id_lead', 'created_at')
                        ->latest()
                        ->limit(10);
                },
            ])->select([
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
                'roles.name as role_name',
                'branches.name as branch_name',
                'channels.name as channel_name',
                'channels.icon',
                'sales.invoice',
            ]);
    }
    protected function getData(string|int|null $branchId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->buildListQuery();
        $this->applyFilters($query, $filters);
        [$column, $direction] = $this->resolveSorting($filters);
        return $query
            ->orderBy($column, $direction)
            ->paginate($filters['limit'] ?? 10)
            ->withQueryString();
    }
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        $query = $this->resolveStatus($query, $filters);
        $query = $this->filterKeyword($query, $filters);
        $query = $this->filterPeriode($query, $filters);
        return $query;
    }
    protected function resolveStatus(Builder $query, array $filters): Builder
    {
        return match ($filters['status'] ?? 'active') {
            'new'         => $query->where('leads.status', 'NEW'),
            'follow_up'   => $query->where('leads.status', 'FOLLOW_UP'),
            'closed_won'  => $query->where('leads.status', 'CLOSED_WON'),
            'closed_lost' => $query->where('leads.status', 'CLOSED_LOST'),
            'active'      => $query->whereIn('leads.status', ['NEW', 'FOLLOW_UP']),
            'all'         => $query,
            default       => $query,
        };
    }
    protected function resolveSorting(array $filters): ?array
    {
        return match ($filters['order_by'] ?? 'latest') {
            'oldest' => ['leads.created_at', 'asc'],
            default => ['leads.created_at', 'desc'],
        };
    }
    protected function filterKeyword(Builder $query, array $filters)
    {
        return $query->when(!empty($filters['keyword']), function ($q) use ($filters) {
            $search = $filters['keyword'];
            $cleanSearch = addcslashes($search, '%_');
            $q->where(function ($subQuery) use ($cleanSearch) {
                $subQuery->where('leads.name', 'like', "%{$cleanSearch}%")
                    ->orWhere('leads.phone', 'like', "%{$cleanSearch}%");
            });
        });
    }
    protected function filterPeriode(Builder $query, array $filters)
    {
        return $query->when(!empty($filters['periode']), function ($q) use ($filters) {
            $datePart = explode('-', $filters['periode']);
            if (count($datePart) === 2) {
                $year = $datePart[0];
                $month = $datePart[1];
                $q->whereMonth('leads.created_at', $month)
                    ->whereYear('leads.created_at', $year);
            }
        });
    }
}
