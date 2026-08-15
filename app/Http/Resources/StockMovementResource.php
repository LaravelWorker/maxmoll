<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Класс ресурса для преобразования модели StockMovement (движение товаров) в JSON-представление.
 * Отвечает за форматирование записей истории изменений складских остатков (Ledger) для выдачи через API.
 */
class StockMovementResource extends JsonResource
{
    /**
     * Преобразовать ресурс в массив для последующей сериализации в JSON.
     *
     * @param Request $request Текущий HTTP-запрос
     * @return array Ассоциативный массив с отформатированными данными о движении товара
     */
    public function toArray(Request $request): array
    {
        return [
            // Уникальный идентификатор записи транзакции (движения) в базе данных
            'id'           => $this->id,
            
            // Идентификатор склада, на котором произошло изменение остатков
            'warehouse_id' => $this->warehouse_id,
            
            // Название склада: включается в ответ только если отношение 'warehouse' было предварительно загружено.
            // Это позволяет избежать дополнительных запросов к БД, если название склада не требуется.
            'warehouse'    => $this->whenLoaded('warehouse', fn() => $this->warehouse->name),
            
            // Идентификатор товара, по которому зафиксировано движение
            'product_id'   => $this->product_id,
            
            // Наименование товара: подгружается аналогично складу, только при наличии предзагруженной связи 'product'
            'product_name' => $this->whenLoaded('product', fn() => $this->product->name),
            
            // Величина изменения остатка (положительное число для прихода, отрицательное для списания)
            'quantity'     => $this->quantity,
            
            // Тип документа-основания, который инициировал данное движение (например: order, supply, transfer)
            'doc_type'     => $this->doc_type,
            
            // Уникальный идентификатор (ID) документа-основания в соответствующей таблице
            'doc_id'       => $this->doc_id,
            
            // Дата и время фиксации движения (создания записи). 
            // Применяется nullsafe-оператор (?->) для безопасного приведения к строке.
            'created_at'   => $this->created_at?->toDateTimeString(),
        ];
    }
}