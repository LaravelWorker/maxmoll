<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Класс ресурса для преобразования модели Product (товар) в JSON-представление.
 * Отвечает за форматирование основных данных о товаре и информации о его наличии на складах.
 */
class ProductResource extends JsonResource
{
    /**
     * Преобразовать ресурс в массив для последующей сериализации в JSON.
     *
     * @param Request $request Текущий HTTP-запрос
     * @return array Ассоциативный массив с отформатированными данными товара
     */
    public function toArray(Request $request): array
    {
        return [
            // Уникальный идентификатор товара в базе данных
            'id'     => $this->id,
            
            // Наименование (название) товара
            'name'   => $this->name,
            
            // Базовая стоимость (цена) товара
            'price'  => $this->price,
            
            // Коллекция данных об остатках товара по различным складам.
            // Связь warehouses() — belongsToMany через таблицу stocks, поэтому остаток лежит в pivot.
            // Формируем корректную структуру вручную (передавать модели Warehouse в StockResource нельзя:
            // у них нет полей warehouse_id/product_id/stock, что давало бы null в ответе).
            // whenLoaded предотвращает проблему N+1 — блок добавляется только при предзагрузке связи.
            'stocks' => $this->whenLoaded('warehouses', fn () => $this->warehouses->map(fn ($warehouse) => [
                'warehouse_id' => $warehouse->id,
                'warehouse'    => $warehouse->name,
                'stock'        => (int) $warehouse->pivot->stock,
            ])->values()),
        ];
    }
}