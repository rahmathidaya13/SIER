<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_lead_activity' => $this->id_lead_activity,
            'id_lead'          => $this->id_lead,
            'id_user'          => $this->id_user,
            'updated_by'       => $this->updated_by,
            'description'      => $this->description,
            'source'           => $this->source,
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,

            'user' => UserResource::make($this->whenLoaded('user')),

            'user_update' => UserResource::make($this->whenLoaded('userUpdate')),

        ];
    }
}
