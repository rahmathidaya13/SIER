<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // $this merujuk pada instance model LeadModel
        return [
            // 1. Data Dasar
            'id_lead'        => $this->id_lead,
            'id_customer' => $this->id_customer,
            'name'           => $this->name,
            'phone'          => $this->phone,
            'email'          => $this->email,
            'status'         => $this->status,
            'temperature'    => $this->temperature,
            'customer_type'  => $this->customer_type,
            'notes'          => $this->notes,
            'follow_up_date' => $this->follow_up_date,
            'closed_at'      => $this->closed_at,

            'user' => UserResource::make($this->whenLoaded('user')),
            // 2. Manipulasi Alias Langsung (Tidak perlu "as creator_name" di query DB)
            // whenLoaded() memastikan data ini HANYA dirender jika relasinya dipanggil (menghindari error N+1)
            'creator_name' => $this->whenLoaded('user', function () {
                return $this->user?->name;
            }),

            'channel' => $this->whenLoaded('channel', function () {
                return [
                    'id_channel' => $this->channel?->id_channel,
                    'name' => $this->channel?->name,
                    'icon' => $this->channel?->icon
                ];
            }),
            'branch' => $this->whenLoaded('branch', function () {
                return [
                    'id_branch' => $this->branch?->id_branch,
                    'name' => $this->branch?->name,
                ];
            }),
            'product_count' => $this->lead_product_count,
            'products' => $this->whenLoaded('leadProduct', function () {
                return $this->leadProduct->map(function ($lp) {
                    return [
                        'id_product'   => $lp->product?->id_product,
                        'product_name' => $lp->product?->name,
                        'image_link'   => $lp->product?->image_link,
                        'image_path'   => $lp->product?->image_path,
                    ];
                });
            }),

            'customer' => $this->whenLoaded('customer', function () {
                return [
                    'id_customer' => $this->customer?->id_customer,
                    'name' => $this->customer?->name,
                    'phone' => $this->customer?->phone,
                    'email' => $this->customer?->email,
                    'address' => $this->customer?->address,
                    'customer_tag' => $this->customer?->customer_tag
                ];
            })

        ];
    }
}
