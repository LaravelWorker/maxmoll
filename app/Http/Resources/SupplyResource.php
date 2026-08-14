<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'warehouse'  => new WarehouseResource($this->whenLoaded('warehouse')),
            'items'      => SupplyItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}