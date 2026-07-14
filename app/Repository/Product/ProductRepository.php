<?php

namespace App\Repository\Product;

use App\Models\ProductModel;
use App\Repository\BaseCacheRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductRepository extends BaseCacheRepository
{
    protected string $cachePrefix = 'product_cache_';
    public function buildBaseQuery($filters = [])
    {
        $query = ProductModel::query()
            ->leftJoin('users', 'products.created_by', '=', 'users.id')
            ->leftJoin('product_price', 'products.id_product', '=', 'product_price.id_product')
            ->select([
                'products.id_product',
                'products.name as product_name',
                'products.created_by as id_user',
                'products.product_code as code',
                'products.source',
                'products.category',
                'products.slug',
                'products.item_condition as condition',
                'products.image_path',
                'products.link',
                'products.image_link',
                'products.created_at',
                'products.updated_at',
                'users.name as creator_name',
                'product_price.status',
                'product_price.valid_from',
                'product_price.valid_until',
                'product_price.base_price',
                'product_price.discount_percentage',
                'product_price.discount_price',
                'product_price.price_type',
            ]);
        $query->when(!empty($filters['keyword']), function ($q) use ($filters) {
            $search = $filters['keyword'];

            $q->where(function ($subQuery) use ($search) {
                $subQuery->where('products.name', 'like', "%{$search}%");
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

    protected function resolveSorting(array $filters): ?array
    {
        return match ($filters['order_by'] ?? 'latest') {
            'oldest' => ['products.created_at', 'asc'],
            default => ['products.created_at', 'desc'],
        };
    }
}
