<?php

namespace App\Repository\User;

use App\Models\User;
use App\Repository\BaseCacheRepository;
use Illuminate\Pagination\LengthAwarePaginator;


class UserRepository extends BaseCacheRepository
{
    protected string $cachePrefix = 'user_cache';

    protected function getData(string|int|null $branchId, array $filters = []): LengthAwarePaginator
    {
        $query = User::query()
            ->allowedForUser()
            ->leftJoin('model_has_roles', function ($join) {
                $join->on('model_has_roles.model_id', '=', 'users.id')
                    ->where('model_has_roles.model_type', '=', User::class); // Wajib filter tipe modelnya
            })
            ->leftJoin('roles', 'roles.id',  '=',  'model_has_roles.role_id')
            ->leftJoin('branches', 'branches.id_branch',  '=',  'users.id_branch')
            ->select([
                'users.id',
                'users.id_branch',
                'users.name',
                'users.email',
                'users.is_active',
                'users.created_at',
                'users.updated_at',
                'branches.name as branch_name',
                'roles.name as role_name'
            ]);

        $status = $this->resolveStatus($filters);
        $query->when(
            $status !== null,
            fn($q) => $q->where('users.is_active', $status)
        );

        $query->when(!empty($filters['keyword']), function ($q) use ($filters) {
            $search = addcslashes($filters['keyword'], '%_');

            $q->where(function ($subQuery) use ($search) {
                $subQuery->where('users.name', 'like', "%{$search}%");
            });
        });

        $limit = min((int) ($filters['limit'] ?? 10), 100);
        [$column, $direction] = $this->resolveSorting($filters);
        return $query
            ->orderBy($column, $direction)
            ->paginate($limit);
    }
    protected function resolveStatus(array $filters)
    {
        return match ($filters['status'] ?? 'all') {
            'active' => 'active',
            'inactive' => 'inactive',
            'all' => null,
            default => null,
        };
    }
    protected function resolveSorting(array $filters): array
    {
        return match ($filters['sort_order'] ?? 'latest') {

            'oldest' => ['users.created_at', 'asc'],

            default => ['users.created_at', 'desc'],
        };
    }
}
