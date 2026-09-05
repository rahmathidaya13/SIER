<?php

namespace App\Repository\Notifications;

class NotificationRepository
{
    public function buildBaseQuery()
    {
        $query = auth()->user()->notifications();
        return $query;
    }
    public function getData(array $filters = [])
    {
        $query = $this->buildBaseQuery();
        $this->resolveSorting($filters, $query);
        $this->resolveStatus($filters, $query);
        return $query
            ->paginate($filters['limit'] ?? 10)
            ->withQueryString();
    }
    protected function resolveStatus(array $filters, $query)
    {
        return match ($filters['status'] ?? 'unread') {
            'read' => $query->whereNotNull('read_at'),
            'unread' => $query->whereNull('read_at'),
            default => $query,
        };
    }
    protected function resolveSorting(array $filters, $query)
    {
        return match ($filters['order_by'] ?? 'latest') {
            'oldest' => $query->reorder('created_at', 'asc'),
            default  => $query->reorder('created_at', 'desc'),
        };
    }
}
