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
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'id_lead' => $this->id_lead,
            'payment_method' => $this->payment_method,
            'bank_name' => $this->bank_name,
            'paid_amount' => $this->paid_amount,
            'payment_at' => $this->payment_at,
            'notes_payment' => $this->notes_payment,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'creator' => UserResource::make($this->whenLoaded('creator')),
            'updater' => UserResource::make($this->whenLoaded('updater')),
            'lead' => LeadResource::make($this->whenLoaded('lead')),
            'sales' => SalesResource::make($this->whenLoaded('sales')),
        ];
    }
}
