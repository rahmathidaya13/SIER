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
        $user = auth()->user();

        $query = User::query()
            ->leftJoin('model_has_roles', function ($join) {
                $join->on('model_has_roles.model_id', '=', 'users.id')
                    ->where('model_has_roles.model_type', '=', User::class); // Wajib filter tipe modelnya
            })
            ->leftJoin('roles', 'roles.id',  '=',  'model_has_roles.role_id')
            ->leftJoin('branches', 'branches.id_branch',  '=',  'users.id_branch')
            ->select([
                'users.*',
                'branches.name as branch_name',
                'roles.name as role_name'
            ]);

        if ($branchId !== 'global') {
            $query->where('users.id_branch', $user->id_branch);
        }

        $status = $this->resolveStatus($filters);
        $query->when(
            $status !== null,
            fn($q) => $q->where('users.is_active', $status)
        );

        $query->when(!empty($filters['keyword']), function ($q) use ($filters) {
            $search = $filters['keyword'];

            $q->where(function ($subQuery) use ($search) {
                $subQuery->where('users.name', 'like', "%{$search}%");
            });
        });


        [$column, $direction] = $this->resolveSorting($filters);
        return $query
            ->orderBy($column, $direction)
            ->paginate($filters['limit'] ?? 10);
    }
    protected function resolveStatus(array $filters): ?bool
    {
        return match ($filters['status'] ?? 'all') {
            'active' => true,
            'inactive' => false,
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
