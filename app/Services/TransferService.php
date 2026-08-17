<?php

namespace App\Services;

use App\Consts\OrderStatus;
use App\Models\Stock;
use App\Models\Transfer;
use Illuminate\Support\Facades\DB;

class TransferService
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Создать и провести документ перемещения со всеми позициями.
     *
     * @param array $data Валидированные данные из Request
     * @return Transfer
     * @throws \Exception
     */
    public function createAndExecute(array $data): Transfer
    {
        return DB::transaction(function () use ($data) {
            $fromWarehouseId = $data['from_warehouse_id'];
            $toWarehouseId = $data['to_warehouse_id'];

            // 1. Проверка доступности остатков и резервов (с блокировкой строк)
            $this->validateStockAvailability($fromWarehouseId, $data['items']);

            // 2. Создание документа перемещения
            $transfer = $this->createTransferDocument($fromWarehouseId, $toWarehouseId);

            // 3. Обработка позиций, проводок и обновление остатков
            $this->processTransferItems($transfer, $data['items']);

            return $transfer;
        });
    }

    /**
     * Проверка достаточности физических остатков и учета активных заказов с pessimistic locking.
     *
     * @param int $warehouseId
     * @param array $items
     * @throws \Exception
     */
    protected function validateStockAvailability(int $warehouseId, array $items): void
    {
        foreach ($items as $itemData) {
            $productId = $itemData['product_id'];
            $requestedCount = $itemData['count'];

            // Получаем физический остаток с блокировкой строки на чтение/запись
            $physicalStock = $this->getLockedPhysicalStock($warehouseId, $productId);

            if ($physicalStock < $requestedCount) {
                throw new \Exception("Недостаточно товара на складе-отправителе. Физический остаток: {$physicalStock}, запрошено: {$requestedCount}.");
            }

            $reservedInActiveOrders = $this->getReservedStock($warehouseId, $productId);
            $availableStock = $physicalStock - $reservedInActiveOrders;

            if ($availableStock < $requestedCount) {
                throw new \Exception("Невозможно переместить товар (ID: {$productId}): {$reservedInActiveOrders} ед. зарезервировано активными заказами клиентов. Доступно для перемещения: max {$availableStock} ед.");
            }
        }
    }

    /**
     * Получить физический остаток товара с блокировкой (lockForUpdate).
     *
     * @param int $warehouseId
     * @param int $productId
     * @return int
     */
    protected function getLockedPhysicalStock(int $warehouseId, int $productId): int
    {
        $stock = Stock::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        return $stock ? $stock->stock : 0;
    }

    /**
     * Получить количество товара, зарезервированного в активных заказах.
     *
     * @param int $warehouseId
     * @param int $productId
     * @return int
     */
    protected function getReservedStock(int $warehouseId, int $productId): int
    {
        return (int) DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.warehouse_id', $warehouseId)
            ->where('order_items.product_id', $productId)
            ->where('orders.status', OrderStatus::ACTIVE->value)
            ->sum('order_items.count');
    }

    /**
     * Создать запись документа перемещения.
     *
     * @param int $fromWarehouseId
     * @param int $toWarehouseId
     * @return Transfer
     */
    protected function createTransferDocument(int $fromWarehouseId, int $toWarehouseId): Transfer
    {
        return Transfer::create([
            'from_warehouse_id' => $fromWarehouseId,
            'to_warehouse_id'   => $toWarehouseId,
            'created_at'        => now(),
        ]);
    }

    /**
     * Обработать позиции перемещения, сформировать проводки и обновить остатки.
     *
     * @param Transfer $transfer
     * @param array $items
     * @return void
     */
    protected function processTransferItems(Transfer $transfer, array $items): void
    {
        foreach ($items as $itemData) {
            $productId = $itemData['product_id'];
            $count = $itemData['count'];

            // Создаем позицию в перемещении
            $transfer->items()->create([
                'product_id' => $productId,
                'count'      => $count,
            ]);

            // Списание со склада-отправителя
            $this->stockService->decrementStock($transfer->from_warehouse_id, $productId, $count, $transfer);

            // Зачисление на склад-получатель
            $this->stockService->incrementStock($transfer->to_warehouse_id, $productId, $count, $transfer);
        }
    }
}