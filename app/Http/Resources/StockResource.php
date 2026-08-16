<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockResource extends JsonResource
{
    /**
     * Преобразовать ресурс в массив.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'warehouse_id' => $this->warehouse_id,
            'product_id'   => $this->product_id,
            'stock'        => $this->stock,
            'warehouse'    => $this->whenLoaded('warehouse', function () {
                return [
                    'id'   => $this->warehouse->id,
                    'name' => $this->warehouse->name,
                ];
            }),
            'product'      => $this->whenLoaded('product', function () {
                return [
                    'id'    => $this->product->id,
                    'name'  => $this->product->name,
                    'price' => $this->product->price,
                ];
            }),
            'created_at'   => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'   => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}