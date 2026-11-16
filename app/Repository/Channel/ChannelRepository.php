<?php

namespace App\Repository\Channel;

use Carbon\Carbon;
use App\Support\ScopeKey;
use Illuminate\Support\Str;
use App\Models\ChannelModel;
use App\Repository\BaseCacheRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ChannelRepository extends BaseCacheRepository
{
    protected string $cachePrefix = 'channel_cache_';
    protected int $cacheMinutes = 30;

    protected function buildBaseQuery()
    {
        $query = ChannelModel::query()
            ->joinUserRole();
        return $query;
    }
    protected function buildListQuery()
    {
        return $this->buildBaseQuery()
            ->select([
                'channels.id_channel',
                'channels.created_by',
                'channels.updated_by',
                'channels.name',
                'channels.code',
                'channels.icon',
                'channels.created_at',
                'channels.updated_at',

                'creator.name as creator_name',
                'creator_role.name as creator_role_name',

                'updater.name as updater_name',
                'updater_role.name as updater_role_name',
            ]);
    }
    protected function getData(string|int|null $scopekey, array $filters = []): LengthAwarePaginator
    {
        $query = $this->buildListQuery();
        $this->leadCount($scopekey, $query, $filters);
        $this->applyScope($query, $scopekey);
        $this->filterKeyword($query, $filters);

        [$column, $direction] = $this->resolveSorting($filters);

        return $query
            ->orderBy($column, $direction)
            ->paginate($filters['limit'] ?? 10)
            ->through(function ($channel) {
                $totalLeads = (int) ($channel->total_leads ?? 0);
                $closedLeads = (int) ($channel->closed_leads ?? 0);

                $channel->conversion_rate = $totalLeads > 0
                    ? round(($closedLeads / $totalLeads) * 100, 1)
                    : 0;

                return $channel;
            });
    }
    protected function filterKeyword(Builder $query, array $filters): void
    {
        if (!empty($filters['keyword'])) {
            $search = $filters['keyword'];
            $cleanSearch = addcslashes($search, '%_');

            $query->where(function ($subQuery) use ($cleanSearch) {
                $subQuery->where('channels.name', 'like', "%{$cleanSearch}%")
                    ->orWhere('channels.code', 'like', "%{$cleanSearch}%");
            });
        }
    }
    protected function resolveSorting(array $filters): ?array
    {
        return match ($filters['order_by'] ?? 'latest') {
            'oldest' => ['channels.created_at', 'asc'],
            default => ['channels.created_at', 'desc'],
        };
    }
    protected function leadCount(string|int|null $scopeKey, Builder $query, array $filters)
    {
        // Filter periode
        $period = $filters['periode'] ?? now()->format('Y-m');
        $date = Carbon::createFromFormat('Y-m', $period) ?: now();

        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();
        // Ekstrak ID cabang dari $scopeKey (null jika GLOBAL / GLOBAL_SUPER_ADMIN)
        $branchId = str_starts_with((string) $scopeKey, 'branch_')
            ? Str::after((string) $scopeKey, 'branch_')
            : null;

        $userId = str_starts_with((string) $scopeKey, 'user_')
            ? Str::after((string) $scopeKey, 'user_')
            : null;


        return $query->withCount([
            'leads as total_leads' => function ($q) use ($startOfMonth, $endOfMonth, $branchId) {
                $q->whereBetween('leads.created_at', [$startOfMonth, $endOfMonth])
                    ->when($branchId, fn($query) => $query->where('leads.id_branch', $branchId));
            },
            'leads as closed_leads' => function ($q) use ($startOfMonth, $endOfMonth, $branchId) {
                $q->where('leads.status', 'CLOSED_WON')
                    ->whereBetween('leads.created_at', [$startOfMonth, $endOfMonth])
                    ->when($branchId, fn($query) => $query->where('leads.id_branch', $branchId));
            }
        ]);
    }

    protected function applyScope(Builder $query, string|int|null $scopeKey): void
    {
        // Semua user yang memiliki permission boleh melihat seluruh daftar channel,
        // sehingga tidak ada filter WHERE id_branch maupun created_by yang diterapkan.
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
