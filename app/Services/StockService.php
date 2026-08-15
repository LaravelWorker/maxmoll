<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;

/**
 * Сервисный класс для управления складскими остатками.
 * Инкапсулирует логику изменения количества товаров на складах 
 * и ведения истории движений (Ledger) для аудита.
 */
class StockService
{
    /**
     * Изменить остаток товара на складе и зафиксировать движение в истории.
     *
     * Метод выполняет инкремент или декремент остатков (в зависимости от знака $quantityChange)
     * и создает связанную запись в таблице истории движений (stock_movements) с привязкой 
     * к документу-основанию (Заказ, Поставка, Перемещение).
     *
     * @param int $warehouseId Идентификатор целевого склада
     * @param int $productId Идентификатор товара, остатки которого изменяются
     * @param int $quantityChange Величина изменения: положительное число для прихода, отрицательное для списания
     * @param Model $doc Документ-основание (Eloquent-модель), инициировавший движение (Order, Supply, Transfer)
     * @return void
     */
    public function changeStock(
        int $warehouseId,
        int $productId,
        int $quantityChange,
        Model $doc
    ): void {
        // Если изменение равно нулю, прерываем выполнение, 
        // чтобы не плодить пустые транзакции в базе данных
        if ($quantityChange === 0) {
            return;
        }

        // 1. Находим текущую запись остатка для пары склад-товар. 
        // Если товара на этом складе еще не было, атомарно создаем новую запись с базовым остатком 0.
        $stock = Stock::firstOrCreate(
            ['warehouse_id' => $warehouseId, 'product_id' => $productId],
            ['stock' => 0]
        );

        // Атомарно изменяем остаток непосредственно в базе данных. 
        // Если $quantityChange отрицательный, произойдет списание (декремент).
        $stock->increment('stock', $quantityChange);

        // 2. Регистрируем запись истории (Ledger) для прозрачного аудита движений
        StockMovement::create([
            'warehouse_id' => $warehouseId,
            'product_id'   => $productId,
            'quantity'     => $quantityChange,
            
            // Получаем полиморфный тип документа (например, 'order', 'supply' 
            // или полный класс в зависимости от настроек morph map)
            'doc_type'     => $doc->getMorphClass(),
            
            // Получаем первичный ключ документа-основания
            'doc_id'       => $doc->getKey(),
            
            'created_at'   => now(),
        ]);
    }
}