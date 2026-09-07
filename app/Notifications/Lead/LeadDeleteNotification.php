<?php

namespace App\Notifications\Lead;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class LeadDeleteNotification extends Notification implements ShouldBroadcast, ShouldQueue
{
    use Queueable;
    public $leadId;
    public $leadName;
    public $actor;
    /**
     * Create a new notification instance.
     */
    public function __construct($leadId, $leadName, $actor)
    {
        $this->leadId = $leadId;
        $this->leadName = $leadName;
        $this->actor = $actor;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    protected function notificationData(): array
    {
        return [
            'type' => 'lead_deleted',
            'title' => 'Lead Dihapus',
            'message' => ucwords($this->actor->name)
                . ' menghapus lead atas nama pelanggan '
                . ucwords($this->leadName),
            'lead_id' => $this->leadId,
            'lead_name' => $this->leadName,
            'action' => null,
            'actor' => [
                'id' => $this->actor->id,
                'name' => $this->actor->name,
            ],
        ];
    }

    public function toArray($notifiable): array
    {
        return $this->notificationData();
    }
    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->notificationData());
    }
}
