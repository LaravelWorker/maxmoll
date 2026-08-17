<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Supply;
use Illuminate\Support\Facades\DB;

class SupplyService
{
    /**
     * Создать и провести документ поставки со всеми позициями, 
     * обновить остатки и зафиксировать движения.
     *
     * @param array $data Валидированные данные из StoreSupplyRequest
     * @return Supply
     * @throws \Throwable
     */
    public function createAndExecute(array $data): Supply
    {
        return DB::transaction(function () use ($data) {
            $warehouseId = $data['warehouse_id'];

            // 1. Создание документа поставки
            $supply = $this->createSupplyDocument($warehouseId);

            // 2. Обработка позиций, обновление остатков
            $this->processSupplyItems($supply, $data['items']);

            return $supply;
        });
    }

    /**
     * Создать запись документа поставки.
     *
     * @param int $warehouseId
     * @return Supply
     */
    protected function createSupplyDocument(int $warehouseId): Supply
    {
        return Supply::create([
            'warehouse_id' => $warehouseId,
            'created_at'   => now(),
        ]);
    }

    /**
     * Обработать позиции поставки, сформировать проводки и увеличить остатки.
     *
     * @param Supply $supply
     * @param array $items
     * @return void
     */
    protected function processSupplyItems(Supply $supply, array $items): void
    {
        foreach ($items as $itemData) {
            $productId = $itemData['product_id'];
            $count = $itemData['count'];

            // Создаем позицию в поставке
            $supply->items()->create([
                'product_id' => $productId,
                'count'      => $count,
            ]);

            $transferService = new TransferService();
            $transferService->changeStock($supply->warehouse_id, $productId, $count);
            $transferService->recordMovement($supply->warehouse_id, $productId, $count, $supply);
        }
    }
}