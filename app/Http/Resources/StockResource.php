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
            'warehouse_id' => $this->warehouse_id,
            'product_id'   => $this->product_id,
            'stock'        => $this->stock,
            'warehouse'    => WarehouseResource::make($this->whenLoaded('warehouse')),
            'product'      => ProductResource::make($this->whenLoaded('product')),
        ];
    }
}