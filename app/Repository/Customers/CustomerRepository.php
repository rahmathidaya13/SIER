<?php

namespace App\Repository\Customers;

use App\Models\User;
use App\Support\ScopeKey;
use App\Models\CustomerModel;
use App\Repository\BaseCacheRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerRepository extends BaseCacheRepository
{
    protected string $cachePrefix = 'customer_cache';
    protected int $cacheMinutes = 30;
    protected function buildBaseQuery()
    {
        $query = CustomerModel::query()
            ->leftJoin('branches', 'branches.id_branch', '=', 'customers.created_of_branch')
            ->leftJoin('users as creator', 'creator.id', '=', 'customers.created_by')
            ->leftJoin('users as updater', 'updater.id', '=', 'customers.updated_by');
        return $query;
    }
    protected function buildListQuery(): Builder
    {
        return $this->buildBaseQuery()
            ->select([
                'customers.id_customer',
                'customers.created_of_branch',
                'branches.name as branch_name',
                'customers.customer_code',
                'customers.name',
                'customers.pic_name',
                'customers.phone',
                'customers.email',
                'customers.tax_number',
                'customers.city',
                'customers.address',
                'customers.business_type',
                'customers.customer_type',
                'customers.customer_tag',
                'customers.status',
                'customers.is_member',
                'customers.created_at',
                'customers.updated_at',

                'creator.name as creator_name',
                'updater.name as updater_name',
            ]);
    }
    protected function getData(string|int|null $scopeKey, array $filters = []): LengthAwarePaginator
    {

        // $filters = array_intersect_key($filters, array_flip(self::ALLOWED_FILTERS));
        $query = $this->buildListQuery();
        $this->applyScope($query, $scopeKey);
        $this->applyFilters($query, $filters);
        [$column, $direction] = $this->resolveSorting($filters);
        return $query
            ->orderBy($column, $direction)
            ->paginate($filters['limit'] ?? 10);
    }
    protected function applyScope(Builder $query, string|int|null $scopeKey): void
    {
        if ($scopeKey === null || $scopeKey === '') return;

        if (in_array($scopeKey, [ScopeKey::GLOBAL, ScopeKey::GLOBAL_SUPER_ADMIN], true)) return;

        if (str_starts_with($scopeKey, 'branch_')) {
            $query->where('customers.created_of_branch',  substr($scopeKey, strlen('branch_')));
            return;
        }

        if (str_starts_with((string) $scopeKey, 'user_')) {
            $userId = substr($scopeKey, strlen('user_'));

            $branchId = User::whereKey($userId)->value('id_branch');

            if ($branchId === null) {
                // Fail-closed: user tidak punya cabang → jangan tampilkan apa-apa
                $query->whereRaw('1 = 0');
                return;
            }

            $query->where('customers.created_of_branch', $branchId);
            return;
        }

        throw new \InvalidArgumentException("Scope key tidak dikenal: {$scopeKey}");
    }
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        $query->when(! empty($filters['keyword']), function (Builder $q) use ($filters): void {
            $escaped = addcslashes((string) $filters['keyword'], '%_\\');

            $q->where(function (Builder $sub) use ($escaped): void {
                $sub->where('customers.name', 'like', "%{$escaped}%")
                    ->orWhere('customers.phone', 'like', "%{$escaped}%");
            });
        });

        // $query->when(
        //     ! empty($filters['customer_type']),
        //     fn(Builder $q) =>
        //     $q->where('customers.customer_type', $filters['customer_type'])
        // );

        return $query;
    }
    protected function resolveSorting(array $filters): array
    {
        return match ($filters['order_by'] ?? 'latest') {
            'oldest'    => ['customers.created_at', 'asc'],
            'name_asc'  => ['customers.name', 'asc'],
            'name_desc' => ['customers.name', 'desc'],
            default     => ['customers.created_at', 'desc'],
        };
    }
}
