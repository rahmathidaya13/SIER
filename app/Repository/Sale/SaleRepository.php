<?php

namespace App\Repository\Sale;

use App\Support\ScopeKey;
use App\Models\SalesModel;
use App\Models\BranchModel;
use App\Repository\BaseCacheRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class SaleRepository extends BaseCacheRepository
{
    protected string $cachePrefix = 'sales_cache_';

    protected function buildBaseQuery(): Builder
    {
        $query = SalesModel::query()
            ->joinUserRole()
            ->leftJoin('leads', 'leads.id_lead', '=', 'sales.id_lead')
            ->leftJoin('branches', 'branches.id_branch', '=', 'sales.id_branch')
            ->leftJoin('customers', 'customers.id_customer', '=', 'sales.id_customer');
        return $query;
    }
    protected function buildListQuery(): Builder
    {
        return $this->buildBaseQuery()
            ->with([
                'salesProduct:id_sales,id_product',
                'salesProduct.product:id_product,name',
            ])
            ->select([
                'sales.id_sales',
                'sales.id_lead',
                'sales.created_by',
                'sales.updated_by',
                'sales.id_branch',
                'sales.invoice',
                'sales.deal_price',
                'sales.remaining_bill',
                'sales.paid_amount',
                'sales.status',
                'sales.payment_status',
                'sales.deal_at',
                'sales.notes',
                'sales.cancelled_at',
                'sales.cancelled_by',
                'sales.cancelled_reason',
                'sales.cancelled_notes',
                'sales.created_at',
                'sales.updated_at',


                'creator.name as creator_name',
                'updater.name as updater_name',
                'creator_role.name as creator_role_name',
                'updater_role.name as updater_role_name',

                'branches.name as branch_name',
                'customers.id_customer',
                'customers.name as customer_name',
                'customers.phone as customer_phone',
                'customers.email as customer_email',
            ]);
    }
    protected function getData(string|int|null $scopeKey, array $filters = []): LengthAwarePaginator
    {
        $query = $this->buildListQuery();
        $this->applyScope($query, $scopeKey);
        $this->applyFilters($query, $filters);
        [$column, $direction] = $this->resolveSorting($filters);
        $limit = min((int) ($filters['limit'] ?? 10), 100);
        return $query
            ->orderBy($column, $direction)
            ->paginate($limit);
    }
    protected function applyScope(Builder $query, string|int|null $scopeKey): void
    {
        if ($scopeKey === null || $scopeKey === '') return;

        if (in_array($scopeKey, [ScopeKey::GLOBAL, ScopeKey::GLOBAL_SUPER_ADMIN], true)) return;

        if (str_starts_with($scopeKey, 'branch_')) {
            $query->where('sales.id_branch',  substr($scopeKey, strlen('branch_')));
            return;
        }

        if (str_starts_with($scopeKey, 'user_')) {
            $query->where('sales.created_by', substr($scopeKey, strlen('user_')));

            return;
        }

        throw new \InvalidArgumentException("Scope key tidak dikenal: {$scopeKey}");
    }


    protected function applyFilters(Builder $query, array $filters): Builder
    {
        $query = $this->resolveStatus($query, $filters);
        $query = $this->filterKeyword($query, $filters);
        $query = $this->filterBranch($query, $filters);
        return $query;
    }
    protected function resolveSorting(array $filters): array
    {
        return match ($filters['order_by'] ?? 'latest') {
            'oldest' => ['sales.created_at', 'asc'],
            default => ['sales.created_at', 'desc'],
        };
    }
    protected function resolveStatus(Builder $query, array $filters): Builder
    {
        return match ($filters['status'] ?? 'closing') {
            'closing' => $query->where('sales.status', 'ACTIVE'),
            'cancelled' => $query->where('sales.status', 'CANCELLED'),
            default => $query,
        };
    }

    public function getSalesStats(array $filters = []): array
    {
        // Panggil query yang sama, jadi filternya tetap akurat
        $query = $this->buildBaseQuery();
        $query = $this->applyFilters($query, $filters);

        return [
            'omset_lunas' => (clone $query)
                ->where('sales.status', '!=', 'CANCELLED')
                ->sum('sales.paid_amount'),
            'piutang'     => (clone $query)
                ->sum('sales.remaining_bill'),
            'avg_deal'    => (clone $query)
                ->where('sales.status', '!=', 'CANCELLED')
                ->avg('sales.deal_price') ?? 0,
        ];
    }
    protected function filterKeyword(Builder $query, array $filters): Builder
    {
        return $query->when(!empty($filters['keyword']), function ($q) use ($filters) {
            $search = $filters['keyword'];
            $cleanSearch = addcslashes($search, '%_');
            $q->where(function ($subQuery) use ($cleanSearch) {
                $subQuery->where('customers.name', 'like', "%{$cleanSearch}%")
                    ->orWhere('customers.phone', 'like', "%{$cleanSearch}%")
                    ->orWhere('customers.email', 'like', "%{$cleanSearch}%")
                    ->orWhere('sales.invoice', 'like', "%{$cleanSearch}%");
            });
        });
    }
    protected function filterBranch(Builder $query, array $filters): Builder // Filter berdasarkan branch
    {
        $user = auth()->user();
        $isSuperAdmin = $user->hasRole(['super_admin', 'developer']);

        $branchName = trim((string) ($filters['branch'] ?? 'all'));

        return $query
            // Jika bukan admin, wajib kunci ke id_branch milik user
            ->when(!$isSuperAdmin, fn($q) => $q->where('sales.id_branch', $user->id_branch ?? '__not_found__'))

            // Jika super admin dan memilih nama cabang tertentu, cari via subquery
            ->when($isSuperAdmin && $branchName !== '' && $branchName !== 'all', function ($q) use ($branchName) {
                $q->where('sales.id_branch', BranchModel::select(['id_branch', 'name'])->where('name', $branchName)->value('id_branch'));
            });
    }
}
