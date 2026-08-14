<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'product_id'   => $this->product_id,
            'product_name' => $this->whenLoaded('product', fn() => $this->product->name),
            'price'        => $this->whenLoaded('product', fn() => $this->product->price),
            'count'        => $this->count,
        ];
    }
}