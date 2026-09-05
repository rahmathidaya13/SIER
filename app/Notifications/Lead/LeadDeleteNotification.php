<?php

namespace App\Notifications\Lead;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class LeadDeleteNotification extends Notification implements ShouldBroadcast
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

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray($notifiable)
    {
        return [
            'title'   => 'Lead Dihapus',
            'message' => $this->actor . " menghapus lead pelanggan " . $this->lead->name,
            'lead_id' => $this->lead->id_lead,
        ];
    }
    // 2. Data yang ditembakkan secara Real-time via Pusher ke Vue
    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'title'   => 'Lead Dihapus',
            'message' => $this->actor . " menghapus lead pelanggan " . $this->lead->name,
            'lead_id' => $this->lead->id_lead,
        ]);
    }
}
