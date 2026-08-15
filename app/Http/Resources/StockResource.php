<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Класс ресурса для преобразования информации об остатках товара на складе в JSON-представление.
 * Обычно используется при загрузке связи Many-to-Many (например, когда товары подгружают свои склады),
 * где информация об остатке хранится в промежуточной таблице.
 */
class StockResource extends JsonResource
{
    /**
     * Преобразовать ресурс в массив для последующей сериализации в JSON.
     *
     * @param Request $request Текущий HTTP-запрос
     * @return array Ассоциативный массив с отформатированными данными об остатке товара на складе
     */
    public function toArray(Request $request): array
    {
        return [
            // Уникальный идентификатор склада, на котором находится товар
            'warehouse_id'   => $this->id,
            
            // Название (наименование) данного склада
            'warehouse_name' => $this->name,
            
            // Количество единиц товара на этом складе.
            // Свойство pivot доступно при работе с отношениями Many-to-Many (BelongsToMany)
            // и содержит данные из промежуточной (связующей) таблицы базы данных (в данном случае, колонку stock).
            'stock'          => $this->pivot->stock,
        ];
    }
}