<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;

class StockService
{
    /**
     * Изменить остаток и записать движение товара
     */
    public function changeStock(
        int $warehouseId,
        int $productId,
        int $quantityChange,
        Model $doc
    ): void {
        if ($quantityChange === 0) {
            return;
        }

        // 1. Находим или создаем остаток и изменяем его
        $stock = Stock::firstOrCreate(
            ['warehouse_id' => $warehouseId, 'product_id' => $productId],
            ['stock' => 0]
        );

        $stock->increment('stock', $quantityChange);

        // 2. Регистрируем запись истории
        StockMovement::create([
            'warehouse_id' => $warehouseId,
            'product_id'   => $productId,
            'quantity'     => $quantityChange,
            'doc_type'     => $doc->getMorphClass(),
            'doc_id'       => $doc->getKey(),
            'created_at'   => now(),
        ]);
    }
}