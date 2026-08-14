<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'status'       => $this->status->value ?? $this->status,
            'customer'     => new CustomerResource($this->whenLoaded('customer')),
            'warehouse'    => new WarehouseResource($this->whenLoaded('warehouse')),
            'items'        => OrderItemResource::collection($this->whenLoaded('items')),
            'created_at'   => $this->created_at?->toDateTimeString(),
            'completed_at' => $this->completed_at?->toDateTimeString(),
        ];
    }
}