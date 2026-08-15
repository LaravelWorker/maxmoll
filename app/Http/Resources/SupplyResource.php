<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Класс ресурса для преобразования модели Supply (поставка) в JSON-представление.
 * Отвечает за структурирование общих данных документа поставки, включая безопасную 
 * подгрузку связанных сущностей (информация о складе и перечень товаров).
 */
class SupplyResource extends JsonResource
{
    /**
     * Преобразовать ресурс в массив для последующей сериализации в JSON.
     *
     * @param Request $request Текущий HTTP-запрос
     * @return array Ассоциативный массив с отформатированными данными поставки
     */
    public function toArray(Request $request): array
    {
        return [
            // Уникальный внутренний идентификатор документа поставки в базе данных
            'id'         => $this->id,
            
            // Данные о складе, на который осуществлена поставка. 
            // Оборачиваются во вложенный ресурс WarehouseResource только в том случае, 
            // если связь 'warehouse' была предварительно загружена (предотвращение проблемы N+1).
            'warehouse'  => new WarehouseResource($this->whenLoaded('warehouse')),
            
            // Список товарных позиций, поступивших в рамках данной поставки. 
            // Преобразуется через ресурсную коллекцию SupplyItemResource, также 
            // только при условии предварительной загрузки связи 'items'.
            'items'      => SupplyItemResource::collection($this->whenLoaded('items')),
            
            // Дата и время создания (фактического проведения) поставки. 
            // Применяется nullsafe-оператор (?->) для предотвращения ошибок вызова метода 
            // toDateTimeString(), если дата по какой-то причине равна null.
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}