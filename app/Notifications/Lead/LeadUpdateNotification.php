<?php

namespace App\Notifications\Lead;

class LeadUpdateNotification extends BaseLeadNotification
{
    protected function type(): string
    {
        return 'lead_updated';
    }
    protected function title(): string
    {
        return 'Lead Diperbarui';
    }
    protected function message(): string
    {
        return ucwords($this->actor->name)
            . ' memperbarui data lead atas nama pelanggan '
            . ucwords($this->lead->name);
    }
    protected function action(): ?array
    {
        return [
            'label' => 'Lihat Lead',
            'icon' => 'bi bi-box-arrow-up-right',
            'route' => route('leads.index', ['keyword' => $this->lead->name]),
        ];
    }
}
