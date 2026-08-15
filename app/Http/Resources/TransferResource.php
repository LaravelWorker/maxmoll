<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read int $id
 * @property-read int $from_warehouse_id
 * @property-read int $to_warehouse_id
 * @property-read \Illuminate\Support\Carbon|null $created_at
 * @property-read \App\Models\Warehouse|null $fromWarehouse
 * @property-read \App\Models\Warehouse|null $toWarehouse
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\TransferItem[] $items
 */
class TransferResource extends JsonResource
{
    /**
     * Преобразовать ресурс в массив для JSON-ответа.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'from_warehouse_id' => $this->from_warehouse_id,
            'to_warehouse_id'   => $this->to_warehouse_id,
            'created_at'        => $this->created_at?->toIso8601String(),
            
            // Если отношение загружено, отдаем информацию о складах и позициях
            'from_warehouse'    => new WarehouseResource($this->whenLoaded('fromWarehouse')),
            'to_warehouse'      => new WarehouseResource($this->whenLoaded('toWarehouse')),
            'items'             => TransferItemResource::collection($this->whenLoaded('items')),
        ];
    }
}