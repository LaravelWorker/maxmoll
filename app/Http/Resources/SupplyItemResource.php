<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Класс ресурса для преобразования модели SupplyItem (позиция поставки) в JSON-представление.
 * Отвечает за форматирование данных о конкретном товаре и его количестве в рамках одной поставки.
 */
class SupplyItemResource extends JsonResource
{
    /**
     * Преобразовать ресурс в массив для последующей сериализации в JSON.
     *
     * @param Request $request Текущий HTTP-запрос
     * @return array Ассоциативный массив с отформатированными данными позиции поставки
     */
    public function toArray(Request $request): array
    {
        return [
            // Уникальный идентификатор позиции внутри таблицы позиций поставок
            'id'           => $this->id,
            
            // Идентификатор связанного товара (внешний ключ к таблице products)
            'product_id'   => $this->product_id,
            
            // Название товара: включается в ответ только если отношение 'product' было предварительно загружено.
            // Использование метода whenLoaded предотвращает скрытую проблему N+1 запросов к базе данных.
            'product_name' => $this->whenLoaded('product', fn() => $this->product->name),
            
            // Количество единиц данного товара, фактически поступившего на склад в рамках этой поставки
            'count'        => $this->count,
        ];
    }
}