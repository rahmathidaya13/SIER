<?php

namespace App\Notifications\Lead;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;

class LeadCreateNotification extends Notification implements ShouldBroadcast
{
    use Queueable;
    public $lead;
    public $actor;
    /**
     * Create a new notification instance.
     */
    public function __construct($lead, $actor)
    {
        $this->lead = $lead;
        $this->actor = $actor;
    }
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    // 1. Data yang disimpan ke database (Tabel 'notifications')
    public function toArray($notifiable)
    {
        return [
            'title'   => 'Lead Baru',
            'message' => ucwords($this->actor) . " membuat lead baru pelanggan " . ucwords($this->lead->name),
            'lead_id' => $this->lead->id_lead,
        ];
    }
    // 2. Data yang ditembakkan secara Real-time via Pusher ke Vue
    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'title'   => 'Lead Baru',
            'message' => ucwords($this->actor) . " membuat lead baru pelanggan  " . ucwords($this->lead->name),
            'lead_id' => $this->lead->id_lead,
        ]);
    }
}
