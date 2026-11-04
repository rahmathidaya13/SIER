<?php

namespace App\Repository\Product;

use App\Support\ScopeKey;
use App\Models\ProductModel;
use App\Repository\BaseCacheRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductRepository extends BaseCacheRepository
{
    protected string $cachePrefix = 'product_cache_';
    protected int $cacheMinutes = 30;

    public function buildBaseQuery()
    {
        $query = ProductModel::query()
            ->whereNull('products.deleted_at')
            ->leftJoin('users', 'products.created_by', '=', 'users.id')
            ->leftJoin('product_price', function ($join) {
                $join->on('products.id_product', '=', 'product_price.id_product')
                    ->whereNull('product_price.deleted_at')
                    ->where('product_price.status', '=', 'published')
                    ->where(function ($q) {
                        $q->whereNull('product_price.valid_until')
                            ->orWhere('product_price.valid_until', '>=', now()->toDateString());
                    });
            });
        return $query;
    }

    protected function buildListQuery(): Builder
    {
        return $this->buildBaseQuery()
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
    }
    protected function getData(string|int|null $scopeKey, array $filters = []): LengthAwarePaginator
    {
        $query = $this->buildListQuery();
        $this->applyScope($query, $scopeKey);
        $this->filterKeyword($query, $filters);
        [$column, $direction] = $this->resolveSorting($filters);
        $limit = min((int)($filters['limit'] ?? 10), 100);

        return $query->orderBy($column, $direction)
            ->paginate($limit);
    }
    protected function filterKeyword(Builder $query, array $filters): void
    {
        if (!empty($filters['keyword'])) {
            $search = $filters['keyword'];
            $cleanSearch = addcslashes($search, '%_');
            $query->where(function ($subQuery) use ($cleanSearch) {
                $subQuery->where('products.name', 'like', "%{$cleanSearch}%")
                    ->orWhere('products.product_code', 'like', "%{$cleanSearch}%");
            });
        }
    }
    protected function resolveSorting(array $filters): ?array
    {
        return match ($filters['order_by'] ?? 'latest') {
            'oldest' => ['products.created_at', 'asc'],
            default => ['products.created_at', 'desc'],
        };
    }
    protected function applyScope(Builder $query, string|int|null $scopeKey): void
    {
        // Karena semua cabang boleh melihat semua katalog produk,
        // kita tidak memfilter berdasarkan id_branch di tabel products.
        if (
            $scopeKey === null ||
            $scopeKey === '' ||
            in_array($scopeKey, [ScopeKey::GLOBAL, ScopeKey::GLOBAL_SUPER_ADMIN], true) ||
            str_starts_with((string) $scopeKey, 'branch_') ||
            str_starts_with((string) $scopeKey, 'user_')
        ) {
            return;
        }

        throw new \InvalidArgumentException("Scope key tidak dikenal: {$scopeKey}");
    }
}
