<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentHistoriesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_payment_history' => $this->id_payment_history,
            'id_sales' => $this->id_sales,
            'id_user' => $this->id_user,
            'id_lead' => $this->id_lead,
            'payment_method' => $this->payment_method,
            'bank_name' => $this->bank_name,
            'paid_amount' => $this->paid_amount,
            'payment_at' => $this->payment_at,
            'notes_payment' => $this->notes_payment,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user' => UserResource::make($this->whenLoaded('user')),
            'lead' => LeadResource::make($this->whenLoaded('lead')),
            'sales' => SalesResource::make($this->whenLoaded('sales')),
        ];
    }
}
