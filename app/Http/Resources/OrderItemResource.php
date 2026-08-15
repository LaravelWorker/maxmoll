<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Класс ресурса для преобразования модели OrderItem (позиция заказа) в JSON-представление.
 * Отвечает за форматирование данных о конкретном товаре и его количестве в рамках заказа.
 */
class OrderItemResource extends JsonResource
{
    /**
     * Преобразовать ресурс в массив для последующей сериализации в JSON.
     *
     * @param Request $request Текущий HTTP-запрос
     * @return array Ассоциативный массив с отформатированными данными позиции заказа
     */
    public function toArray(Request $request): array
    {
        return [
            // Уникальный идентификатор позиции внутри таблицы позиций заказов
            'id'           => $this->id,
            
            // Идентификатор связанного товара (внешний ключ)
            'product_id'   => $this->product_id,
            
            // Название товара: включается в ответ только если отношение 'product' было предварительно загружено.
            // Использование whenLoaded предотвращает скрытую проблему N+1 запросов к базе данных.
            'product_name' => $this->whenLoaded('product', fn() => $this->product->name),
            
            // Текущая цена товара: также добавляется в ответ только при наличии предзагруженной модели товара
            'price'        => $this->whenLoaded('product', fn() => $this->product->price),
            
            // Количество единиц данного товара, добавленного в заказ
            'count'        => $this->count,
        ];
    }
}