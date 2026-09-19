<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesProductResource extends JsonResource
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
            'id_product' => $this->id_product,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'product' => $this->whenLoaded('product', function () {
                return [
                    'id_product' => $this->product?->id_product,
                    'name' => $this->product?->name,
                ];
            }),
        ];
    }
}
