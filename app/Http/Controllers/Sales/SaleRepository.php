<?php

namespace App\Http\Controllers\Sales;

use App\Models\SalesModel;
use App\Repository\BaseCacheRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class SaleRepository extends BaseCacheRepository
{
    protected string $cachePrefix = 'sales_cache_';

    protected function buildBaseQuery(string|int|null $branchId, array $filters = [])
    {
        $user = auth()->user();
        $query = SalesModel::query()
            ->allowedForUser()
            ->joinUserRole()
            ->leftJoin('leads', 'leads.id_lead', '=', 'sales.id_lead')
            ->leftJoin('products', 'products.id_product', '=', 'sales.id_product')
            ->leftJoin('branches', 'branches.id_branch', '=', 'sales.id_branch')
            ->select([
                'sales.id_sales',
                'sales.id_lead',
                'sales.id_user',
                'sales.id_product',
                'sales.id_branch',
                'sales.invoice',
                'sales.deal_price',
                'sales.remaining_bill',
                'sales.paid_amount',
                'sales.payment_status',
                'sales.deal_at',
                'sales.created_at',
                'sales.updated_at',
                'leads.name as lead_customer_name',
                'leads.phone as lead_customer_phone',
                'leads.email as lead_customer_email',
                'users.name as sales_name',
                'roles.name as role_name',
                'products.name as product_name',
                'branches.name as branch_name',
            ]);

        if ($user->hasRole('super_admin')) {
            $query->when(!empty($filters['branch']), function ($q) use ($filters) {
                $q->where('branches.name', $filters['branch']);
            });
        }

        $query->when(!empty($filters['keyword']), function ($q) use ($filters) {
            $search = $filters['keyword'];
            $cleanSearch = addcslashes($search, '%_');
            $q->where(function ($subQuery) use ($cleanSearch) {
                $subQuery->where('leads.name', 'like', "%{$cleanSearch}%")
                    ->orWhere('leads.phone', 'like', "%{$cleanSearch}%")
                    ->orWhere('sales.invoice', 'like', "%{$cleanSearch}%");
            });
        });

        return $query;
    }
    protected function getData(string|int|null $branchId, array $filters = []): LengthAwarePaginator
    {

        $query = $this->buildBaseQuery($branchId, $filters);
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

            'oldest' => ['sales.created_at', 'asc'],

            default => ['sales.created_at', 'desc'],
        };
    }

    public function getSalesStats(string|int|null $branchId, array $filters = []): array
    {
        // Panggil pabrik kueri yang sama, jadi filternya tetap akurat
        $query = $this->buildBaseQuery($branchId, $filters);

        return [
            'omset_lunas' => (clone $query)->sum('sales.paid_amount'),
            'piutang'     => (clone $query)->sum('sales.remaining_bill'),
            'avg_deal'    => (clone $query)->avg('sales.deal_price') ?? 0,
        ];
    }
}
