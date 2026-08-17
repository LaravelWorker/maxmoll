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
     * @throws \Exception
     */
    public function decrementStock(int $warehouseId, int $productId, int $quantity, Model $document): void
    {
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
    }

    /**
     * Атомарное пополнение остатка на складе.
     */
    public function incrementStock(int $warehouseId, int $productId, int $quantity, Model $document): void
    {
        // atomic update / firstOrCreate с блокировкой
        $stock = Stock::firstOrCreate(
            ['warehouse_id' => $warehouseId, 'product_id' => $productId],
            ['stock' => 0]
        );

        Stock::where('id', $stock->id)->lockForUpdate()->first();
        $stock->increment('stock', $quantity);

        $this->recordMovement($warehouseId, $productId, $quantity, $document);
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
            'doc_type'     => $model::class,
            'doc_id'       => $model->id,
            'created_at'   => now(),
        ]);
    }
}