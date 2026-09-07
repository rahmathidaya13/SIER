<?php

namespace App\Notifications\Lead;

class LeadCreateNotification extends BaseLeadNotification
{
    protected function type(): string
    {
        return 'lead_created';
    }
    protected function title(): string
    {
        return 'Lead Baru';
    }

    protected function message(): string
    {
        return ucwords($this->actor->name)
            . " membuat lead baru atas nama pelanggan "
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
