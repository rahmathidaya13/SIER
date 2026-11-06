<?php

namespace App\Notifications\Lead;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;

abstract class BaseLeadNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    public $lead;
    public $actor;
    public function __construct($lead, $actor)
    {
        $this->lead = $lead;
        $this->actor = $actor;
    }
    /** * Type notification. */
    abstract protected function type(): string;
    /** * Title notification. */
    abstract protected function title(): string;
    /** * Message notification. */
    abstract protected function message(): string;
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast',];
    }

    public function toArray($notifiable): array
    {
        return $this->notificationData();
    }
    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->notificationData());
    }
    protected function notificationData(): array
    {
        return [
            'type' => $this->type(),
            'title' => $this->title(),
            'message' => $this->message(),
            'lead_id' => $this->lead->id_lead,
            'lead_name' => $this->lead->customer->name,
            'actor' => [
                'id' => $this->actor->id,
                'name' => $this->actor->name,
            ],
            'action' => $this->action()
        ];
    }
    protected function action(): ?array
    {
        return [
            'route' => route('leads.index', ['keyword' => $this->lead->id_lead]),
            'label' => 'Lihat Lead',
        ];
    }
}
