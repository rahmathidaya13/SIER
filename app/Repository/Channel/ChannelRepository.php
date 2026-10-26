<?php

namespace App\Repository\Channel;

use App\Models\ChannelModel;
use App\Repository\BaseCacheRepository;
use Illuminate\Pagination\LengthAwarePaginator;

class ChannelRepository extends BaseCacheRepository
{
    protected string $cachePrefix = 'channel_cache_';

    protected function buildBaseQuery(array $filters = [])
    {
        $user = auth()->user();
        $query = ChannelModel::query()
            ->withCount([
                'leads as total_leads' => function ($q) use ($user) {
                    if (!$user->hasRole('super_admin')) {
                        $q->where('id_branch', $user->id_branch);
                    }
                },
                'leads as closed_leads' => function ($q) use ($user) {
                    $q->where('status', 'CLOSED_WON');
                    if (!$user->hasRole('super_admin')) {
                        $q->where('id_branch', $user->id_branch);
                    }
                }
            ])
            ->when(!empty($filters['keyword']), function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%');
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
            ->through(function ($channel) {

                $conversionRate = $channel->total_leads > 0
                    ? round(($channel->closed_leads / $channel->total_leads) * 100, 1)
                    : 0;

                return [
                    'id_channel'      => $channel->id_channel,
                    'name'            => $channel->name,
                    'code'            => $channel->code,
                    'icon'            => $channel->icon,
                    'total_leads'     => $channel->total_leads,
                    'closed_leads'    => $channel->closed_leads,
                    'conversion_rate' => $conversionRate,
                ];
            });
    }
    protected function resolveSorting(array $filters): ?array
    {
        return match ($filters['order_by'] ?? 'latest') {
            'oldest' => ['created_at', 'asc'],
            default => ['created_at', 'desc'],
        };
    }
}
