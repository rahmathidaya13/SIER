<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LeadCreatedNotification extends Notification implements ShouldBroadcast
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
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    // 1. Data yang disimpan ke database (Tabel 'notifications')
    public function toArray($notifiable)
    {
        return [
            'title'   => 'Lead Baru Ditambahkan',
            'message' => ucwords($this->actor) . " menambahkan lead baru pelanggan baru " . ucwords($this->lead->name),
            'lead_id' => $this->lead->id_lead,
        ];
    }
    // 2. Data yang ditembakkan secara Real-time via Pusher ke Vue
    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'title'   => 'Lead Baru Ditambahkan',
            'message' => ucwords($this->actor) . " menambahkan lead baru pelanggan baru " . ucwords($this->lead->name),
            'lead_id' => $this->lead->id_lead,
        ]);
    }
}
