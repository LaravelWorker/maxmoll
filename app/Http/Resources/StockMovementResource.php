<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'warehouse_id' => $this->warehouse_id,
            'warehouse'    => $this->whenLoaded('warehouse', fn() => $this->warehouse->name),
            'product_id'   => $this->product_id,
            'product_name' => $this->whenLoaded('product', fn() => $this->product->name),
            'quantity'     => $this->quantity,
            'doc_type'     => $this->doc_type,
            'doc_id'       => $this->doc_id,
            'created_at'   => $this->created_at?->toDateTimeString(),
        ];
    }
}