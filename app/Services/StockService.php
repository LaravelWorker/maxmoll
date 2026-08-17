<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Атомарное списание товара с проверкой наличия, пессимистичной блокировкой и записью в аудит.
     *
     * @param int $warehouseId
     * @param int $productId
     * @param int $quantity
     * @param \Illuminate\Database\Eloquent\Model $document
     * @throws \Exception
     * @return void
     */
    public function decrementStock(int $warehouseId, int $productId, int $quantity, Model $document): void
    {
        // Оборачиваем операцию в транзакцию: только внутри неё пессимистическая блокировка
        // lockForUpdate() удерживается до фиксации изменений. При вызове из OrderService/TransferService,
        // которые уже открыли транзакцию, это создаёт вложенный savepoint и не нарушает целостность.
        DB::transaction(function () use ($warehouseId, $productId, $quantity, $document) {
            // 1. Блокируем и проверяем физическую запись остатка
            $stock = Stock::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            $available = $stock ? $stock->stock : 0;

            if (!$stock || $available < $quantity) {
                throw new \Exception("Недостаточно товара (ID: {$productId}) на складе #{$warehouseId}. В наличии: {$available}, требуется: {$quantity}");
            }

            // 2. Атомарное списание на уровне базы данных (выполняет UPDATE stocks SET stock = stock - N)
            $stock->decrement('stock', $quantity);

            // 3. Фиксация проводки в журнале движений
            $this->recordMovement($warehouseId, $productId, -$quantity, $document);
        });
    }

    /**
     * Увеличить остаток товара на складе и зафиксировать движение.
     *
     * @param int $warehouseId
     * @param int $productId
     * @param int $quantity
     * @param \Illuminate\Database\Eloquent\Model $document
     * @return void
     */
    public function incrementStock(int $warehouseId, int $productId, int $quantity, Model $document): void
    {
        DB::transaction(function () use ($warehouseId, $productId, $quantity, $document) {
            // Находим строку с пессимистической блокировкой на чтение/запись
            $stock = Stock::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            // Если записи об остатке ещё нет — безопасно создаем с 0
            if (!$stock) {
                $stock = Stock::create([
                    'warehouse_id' => $warehouseId,
                    'product_id'   => $productId,
                    'stock'        => 0,
                ]);
            }

            // Атомарно увеличиваем значение в БД
            $stock->increment('stock', $quantity);

            // Фиксируем запись в истории движений
            $this->recordMovement($warehouseId, $productId, $quantity, $document);
        });
    }

    /**
     * Запись проведения документа в журнал аудита.
     */
    public function recordMovement(int $warehouseId, int $productId, int $quantity, Model $model): StockMovement
    {
        return StockMovement::create([
            'warehouse_id' => $warehouseId,
            'product_id'   => $productId,
            'quantity'     => $quantity,
            'doc_type'     => $model->getMorphClass(),
            'doc_id'       => $model->id,
            'created_at'   => now(),
        ]);
    }
}