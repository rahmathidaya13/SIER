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
            'name'           => $this->name,
            'phone'          => $this->phone,
            'email'          => $this->email,
            'status'         => $this->status,
            'temperature'    => $this->temperature,
            'customer_type'  => $this->customer_type,
            'notes'          => $this->notes,
            'follow_up_date' => $this->follow_up_date,

            // 2. Manipulasi Alias Langsung (Tidak perlu "as creator_name" di query DB)
            // whenLoaded() memastikan data ini HANYA dirender jika relasinya dipanggil (menghindari error N+1)
            'creator_name' => $this->whenLoaded('user', function () {
                return $this->user->name;
            }),

            'channel' => $this->whenLoaded('channel', function () {
                return [
                    'id_channel' => $this->channel->id_channel,
                    'name' => $this->channel->name,
                    'icon' => $this->channel->icon
                ];
            }),

            'products' => $this->whenLoaded('leadProduct', function () {
                return $this->leadProduct->map(function ($lp) {
                    return [
                        'id_product'   => $lp->product->id_product ?? null,
                        'product_name' => $lp->product->name ?? null,
                        'image_link'   => $lp->product->image_link ?? null,
                        'image_path'   => $this->product->image_path ?? null,
                    ];
                });
            }),

            'activity' => $this->whenLoaded('activity', function () {
                return $this->activity->map(function ($act) {
                    return [
                        'id_lead_activity' => $act->id_lead_activity,
                        'description'      => $act->description,
                        'id_user'           => $act->id_user,
                        'created_at'       => $act->created_at,
                        'updated_at'       => $act->updated_at,
                        'updated_by'       => $act->updated_by ?? null,

                        'user' => $act->relationLoaded('user') ? [
                            'activity_creator_name' => $act->user->name ?? 'Sistem',
                            // Ekstrak nama role pertama saja agar Vue tidak perlu melooping array role
                            'roles' => $act->user->roles->first()->name ?? 'No Role'

                        ] : null,

                        'user_update' => $act->relationLoaded('userUpdate') && $act->userUpdate ? [
                            'name' => $act->userUpdate->name,
                            'roles' => $act->userUpdate->roles->first()->name ?? 'No Role'
                        ] : null,

                    ];
                });
            }),
        ];
    }
}
