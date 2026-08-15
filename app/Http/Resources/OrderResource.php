<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Класс ресурса для преобразования модели Order (заказ) в JSON-представление.
 * Отвечает за структурирование данных заказа, включая корректную обработку статусов 
 * и безопасную подгрузку связанных сущностей (клиент, склад, позиции).
 */
class OrderResource extends JsonResource
{
    /**
     * Преобразовать ресурс в массив для последующей сериализации в JSON.
     *
     * @param Request $request Текущий HTTP-запрос
     * @return array Ассоциативный массив с отформатированными данными заказа
     */
    public function toArray(Request $request): array
    {
        return [
            // Уникальный идентификатор заказа в базе данных
            'id'           => $this->id,
            
            // Статус заказа. Если атрибут приведен к Enum, безопасно извлекаем его скалярное значение (->value).
            // Если это обычная строка (fallback), возвращаем ее как есть.
            'status'       => $this->status->value ?? $this->status,
            
            // Вложенные данные покупателя. Оборачиваются в собственный ресурс CustomerResource.
            // whenLoaded гарантирует, что связь не будет подгружаться отдельным запросом (N+1), если она не была загружена ранее.
            'customer'     => new CustomerResource($this->whenLoaded('customer')),
            
            // Данные о складе отгрузки. Оборачиваются в WarehouseResource при условии предварительной загрузки связи.
            'warehouse'    => new WarehouseResource($this->whenLoaded('warehouse')),
            
            // Список позиций (товаров) заказа. Преобразуется через ресурсную коллекцию OrderItemResource,
            // также только в случае предзагруженного отношения 'items'.
            'items'        => OrderItemResource::collection($this->whenLoaded('items')),
            
            // Дата и время создания заказа. Используется nullsafe-оператор (?->),
            // чтобы избежать исключения, если дата по какой-то причине отсутствует.
            'created_at'   => $this->created_at?->toDateTimeString(),
            
            // Дата и время фактического завершения (проведения/отмены) заказа (может быть null).
            'completed_at' => $this->completed_at?->toDateTimeString(),
        ];
    }
}