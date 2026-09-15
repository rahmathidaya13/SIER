<?php

namespace App\Repository\Lead;

use App\Models\LeadModel;
use App\Repository\BaseCacheRepository;
use Illuminate\Pagination\LengthAwarePaginator;


class LeadsRepository extends BaseCacheRepository
{
    protected string $cachePrefix = 'leads_cache';

    protected function buildBaseQuery(array $filters = [])
    {
        $query = LeadModel::query()
            ->with([
                // 'activity:id_lead,created_at',
                'leadProduct.product:id_product,name,image_link,image_path',
                'activity' => function ($query) {
                    $query->select('id_lead', 'created_at')
                        ->latest()
                        ->limit(10);
                },
            ])
            ->allowedForUser()
            ->joinUserRole()
            ->leftJoin('channels', 'channels.id_channel', '=', 'leads.id_channel')
            ->leftJoin('sales', 'sales.id_lead', '=', 'leads.id_lead')
            ->leftJoin('branches', 'branches.id_branch', '=', 'users.id_branch')
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
                'roles.name as role_name',
                'branches.name as branch_name',
                'channels.name as channel_name',
                'channels.icon',
                'sales.invoice',
            ]);


        $status = $this->resolveStatus($filters);
        $query->when(
            !empty($status),
            function ($q) use ($status) {
                if (is_array($status)) {
                    $q->whereIn('leads.status', $status);
                } else {
                    $q->where('leads.status', $status);
                }
            }
        );

        $query->when(!empty($filters['periode']), function ($q) use ($filters) {
            $datePart = explode('-', $filters['periode']);

            if (count($datePart) === 2) {
                $year = $datePart[0];
                $month = $datePart[1];

                $q->whereMonth('leads.created_at', $month)
                    ->whereYear('leads.created_at', $year);
            }
        });

        $query->when(!empty($filters['keyword']), function ($q) use ($filters) {
            $search = $filters['keyword'];

            $q->where(function ($subQuery) use ($search) {
                $subQuery->where('leads.name', 'like', "%{$search}%")
                    ->orWhere('leads.phone', 'like', "%{$search}%");
            });
        });


        return $query;
    }

    protected function getData(string|int|null $branchId, array $filters = []): LengthAwarePaginator
    {
        $query = $this->buildBaseQuery($filters);
        [$column, $direction] = $this->resolveSorting($filters);
        return $query
            ->orderBy($column, $direction)
            ->paginate($filters['limit'] ?? 10)
            ->withQueryString();
    }
    protected function resolveStatus(array $filters): ?array
    {
        return match ($filters['status'] ?? 'active') {
            'new'         => ['NEW'],
            'follow_up'   => ['FOLLOW_UP'],
            'closed_won'  => ['CLOSED_WON'],
            'closed_lost' => ['CLOSED_LOST'],
            'active'      => ['NEW', 'FOLLOW_UP'],
            'all'         => null,
            default       => null,
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
