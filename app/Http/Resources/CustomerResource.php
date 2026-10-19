<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_customer' => $this->id_customer,
            'name' => $this->name,
            'pic_name' => $this->pic_name,
            'tax_number' => $this->tax_number,
            'status' => $this->status,
            'is_member' => $this->is_member,
            'city' => $this->city,
            'customer_code' => $this->customer_code,
            'customer_tag' => $this->customer_tag,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'customer_type' => $this->customer_type,
            'business_type' => $this->business_type,
            'created_of_branch' => $this->created_of_branch,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by' => UserResource::make($this->whenLoaded('creator')),
            'updated_by' => UserResource::make($this->whenLoaded('updater')),
            'branch' => $this->whenLoaded('branch', function () {
                return [
                    'id_branch' => $this->branch?->id_branch,
                    'name'      => $this->branch?->name,
                ];
            }),
        ];
    }
}
