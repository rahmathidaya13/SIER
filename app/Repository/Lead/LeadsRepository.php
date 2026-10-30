<?php

namespace App\Repository\Lead;

use App\Models\LeadModel;
use App\Repository\BaseCacheRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Support\ScopeKey;

class LeadsRepository extends BaseCacheRepository
{
    protected string $cachePrefix = 'leads_cache';

    protected int $cacheMinutes = 30;

    protected function buildBaseQuery()
    {
        $query = LeadModel::query()
            ->joinUserRole()
            ->whereNull('leads.archived_at')
            ->leftJoin('channels', 'channels.id_channel', '=', 'leads.id_channel')
            ->leftJoin('sales', 'sales.id_lead', '=', 'leads.id_lead')
            ->leftJoin('customers', 'customers.id_customer', '=', 'leads.id_customer')
            ->leftJoin('branches', 'branches.id_branch', '=', 'creator.id_branch');
        return $query;
    }
    protected function buildListQuery(): Builder
    {
        return $this->buildBaseQuery()
            ->with([
                'leadProduct:id_lead_product,id_lead,id_product',
                'leadProduct.product:id_product,name',
                'activity' => function ($query) {
                    $query->select('id_lead', 'created_at')
                        ->latest()
                        ->limit(10);
                },
            ])->select([
                'leads.id_lead',
                'leads.created_by as creator_id',
                'leads.updated_by as updater_id',
                'leads.id_branch',
                'leads.id_customer',
                'leads.temperature',
                'leads.status',
                'leads.follow_up_date',
                'leads.notes',
                'leads.created_at',
                'leads.updated_at',
                'creator.name as creator_name',
                'updater.name as updater_name',
                'creator_role.name as creator_role_name',
                'updater_role.name as updater_role_name',
                'branches.name as branch_name',
                'channels.name as channel_name',
                'channels.icon',
                'sales.invoice',
                'customers.name as customer_name',
                'customers.phone as customer_phone',
                'customers.email as customer_email',
                'customers.customer_tag',
            ]);
    }
    protected function getData(string|int|null $scopeKey, array $filters = []): LengthAwarePaginator
    {

        // $filters = array_intersect_key($filters, array_flip(self::ALLOWED_FILTERS));
        $query = $this->buildListQuery();
        $this->applyScope($query, $scopeKey);
        $this->applyFilters($query, $filters);
        [$column, $direction] = $this->resolveSorting($filters);
        $limit = min((int) ($filters['limit'] ?? 10), 100);
        return $query
            ->orderBy($column, $direction)
            ->paginate($limit);
    }

    // ------------------------------------------------------------------
    // SCOPE FILTER — inti keamanan multi-tenant
    // ------------------------------------------------------------------

    /**
     * Terapkan scope key ke query.
     *
     * Format scope yang didukung (dari ScopeResolverService):
     *   - 'user_5'              → hanya lead milik user 5
     *   - 'branch_3'            → lead dari cabang 3
     *   - 'global'              → semua lead yang izinkan
     *   - 'global_super_admin'  → semua lead
     *
     * PENTING: fungsi ini HARUS konsisten dengan cara ScopeResolverService
     * menghasilkan scopeKey. Kalau tidak, cache bisa bocor antar tenant.
     */
    protected function applyScope(Builder $query, string|int|null $scopeKey): void
    {
        if ($scopeKey === null || $scopeKey === '') return;

        if (in_array($scopeKey, [ScopeKey::GLOBAL, ScopeKey::GLOBAL_SUPER_ADMIN], true)) return;

        if (str_starts_with($scopeKey, 'branch_')) {
            $query->where('leads.id_branch',  substr($scopeKey, strlen('branch_')));
            return;
        }

        if (str_starts_with($scopeKey, 'user_')) {
            $query->where('leads.created_by', substr($scopeKey, strlen('user_')));

            return;
        }

        throw new \InvalidArgumentException("Scope key tidak dikenal: {$scopeKey}");
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
            $search = (string) $filters['keyword'];
            $cleanSearch = addcslashes($search, '%_');
            $q->where(function ($subQuery) use ($cleanSearch) {
                $subQuery->where('customers.name', 'like', "%{$cleanSearch}%")
                    ->orWhere('customers.phone', 'like', "%{$cleanSearch}%");
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
