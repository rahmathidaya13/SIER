<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_sales' => $this->id_sales,
            'invoice' => $this->invoice,
            'id_lead' => $this->id_lead,
            'id_user' => $this->id_user,
            'id_branch' => $this->id_branch,
            'deal_price' => $this->deal_price,
            'paid_amount' => $this->paid_amount,
            'remaining_bill' => $this->remaining_bill,
            'quantity' => $this->quantity,
            'payment_status' => $this->payment_status,
            'status' => $this->status,
            'deal_at' => $this->deal_at,
            'notes' => $this->notes,
            'cancelled_at' => $this->cancelled_at,
            'cancelled_by' => $this->cancelled_by,
            'cancelled_reason' => $this->cancelled_reason,
            'cancelled_notes' => $this->cancelled_notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'lead' => $this->whenLoaded('lead', function () {
                return [
                    'id_lead' => $this->lead->id_lead,
                    'name' => $this->lead->name,
                    'phone' => $this->lead->phone,
                ];
            }),
            'products' => $this->whenLoaded('salesProduct', function () {
                return SalesProductResource::collection($this->salesProduct);
            }),
            'product_count' => $this->sales_product_count,
            'user' => UserResource::make($this->whenLoaded('user')),
            'cancelledBy' => UserResource::make($this->whenLoaded('cancelledBy')),
        ];
    }
}
