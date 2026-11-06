<?php

namespace App\Notifications\Lead;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;

class LeadConversionNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public $lead, public $actor, public $paymentStatus, public $dealPrice, public $paidAmount)
    {
        //
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
            'type' => 'lead.converted',

            'title' => 'Lead Berhasil Closing',

            'message' => $this->buildMessage(),

            'lead_id' => $this->lead->id_lead,

            'payment_status' => $this->paymentStatus,

            'deal_price' => $this->dealPrice,

            'paid_amount' => $this->paidAmount,
            'lead_name' => $this->lead->customer->name,
            'action' => [
                'label' => 'Lihat Selengkapnya',
                'icon' => 'bi bi-box-arrow-up-right',
                'route' => route('sales.index', ['keyword' => $this->lead->customer->name]),
            ],
            'actor' => [
                'users_id' => $this->actor->id,
                'name' => $this->actor->name,
            ],
        ];
    }

    protected function buildMessage(): string
    {
        $paymentLabel = $this->paymentStatus === 'LUNAS'
            ? 'Lunas'
            : 'Dana Awal (DP)';

        return '💰 '
            . ucwords($this->actor->name)
            . ' berhasil mengkonversikan lead atas nama pelanggan '
            . ucwords($this->lead->customer->name)
            . ' menjadi transaksi '
            . $paymentLabel
            . ' sebesar Rp '
            . number_format($this->paidAmount, 0, ',', '.')
            . '.';
    }

    public function toArray($notifiable): array
    {
        return $this->notificationData();
    }
    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage(
            $this->notificationData()
        );
    }
}
