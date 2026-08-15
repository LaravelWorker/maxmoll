<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ресурс TransferItemResource.
 * Преобразует модель позиции перемещения (TransferItem) в стандартизированный JSON-формат.
 *
 * @property-read int $id Уникальный идентификатор позиции
 * @property-read int $transfer_id Идентификатор документа перемещения
 * @property-read int $product_id Идентификатор товара
 * @property-read int $count Количество единиц товара
 * @property-read \App\Models\Product|null $product Связанная модель товара
 */
class TransferItemResource extends JsonResource
{
    /**
     * Преобразовать ресурс в массив для JSON-ответа.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'transfer_id' => $this->transfer_id,
            'product_id' => $this->product_id,
            'count'      => $this->count,
            
            // Если отношение товара загружено через eager loading, отдаем его через ProductResource
            // (при отсутствии ProductResource можно заменить на $this->whenLoaded('product'))
            'product'    => new ProductResource($this->whenLoaded('product')),
        ];
    }
}