<?php

namespace App\Http\Controllers\Sales;

use App\Models\SalesModel;
use App\Repository\BaseCacheRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class SaleRepository extends BaseCacheRepository
{
    protected string $cachePrefix = 'sales_cache_';
    protected function getData(string|int|null $branchId, array $filters = []): LengthAwarePaginator
    {
        $query = SalesModel::query()
            ->allowedForUser(auth()->user())
            ->leftJoin('leads', 'leads.id_lead', '=', 'sales.id_lead')
            ->leftJoin('users', 'users.id', '=', 'sales.id_user')
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
                'sales.payment_method',
                'sales.deal_at',
                'leads.name as lead_name',
                'leads.phone as lead_phone',
                'users.name as sales_name',
                'products.name as product_name',
            ]);

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
    protected function resolveSorting(array $filters): ?array
    {
        return match ($filters['order_by'] ?? 'latest') {

            'oldest' => ['leads.created_at', 'asc'],

            default => ['leads.created_at', 'desc'],
        };
    }
}
