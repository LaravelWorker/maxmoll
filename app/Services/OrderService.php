<?php

namespace App\Services;

use App\Consts\OrderStatus;
use App\Models\Order;
use App\Models\Stock;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class OrderService
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Создать новый заказ с проверкой наличия товаров на складе.
     *
     * @param array $data Валидированные данные (customer_id, warehouse_id, items)
     * @return Order
     * @throws \Exception
     */
    public function create(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $warehouseId = $data['warehouse_id'];
            $items = $data['items'];

            // 1. Проверяем наличие товаров на складе
            $this->validateStockAvailability($warehouseId, $items);

            // 2. Создаем заказ
            $order = Order::create([
                'customer_id'  => $data['customer_id'],
                'warehouse_id' => $warehouseId,
                'status'       => OrderStatus::ACTIVE->value,
                'created_at'   => now(),
            ]);

            // 3. Создаем позиции заказа
            $order->items()->createMany($items);

            return $order;
        });
    }

    /**
     * Обновить существующий заказ с проверкой наличия товаров на складе.
     *
     * @param Order $order
     * @param array $data Валидированные изменения
     * @return Order
     * @throws \Exception
     */
    public function update(Order $order, array $data): Order
    {
        if ($order->status !== OrderStatus::ACTIVE) {
            throw new \Exception('Нельзя редактировать выполненный или отмененный заказ.');
        }

        return DB::transaction(function () use ($order, $data) {
            $warehouseId = $data['warehouse_id'] ?? $order->warehouse_id;
            $items = $data['items'] ?? null;

            // Если меняются позиции или склад — проверяем остатки
            if ($items !== null) {
                $this->validateStockAvailability($warehouseId, $items);
            }

            // Обновляем основные поля
            $order->update($data);

            // Если переданы новые позиции — заменяем старые
            if ($items !== null) {
                $order->items()->delete();
                $order->items()->createMany($items);
            }

            return $order;
        });
    }

    /**
     * Проверка достаточности физических остатков на складе для списка позиций.
     *
     * @param int $warehouseId
     * @param array $items
     * @throws \Exception
     */
    protected function validateStockAvailability(int $warehouseId, array $items): void
    {
        foreach ($items as $item) {
            $productId = $item['product_id'];
            $requestedCount = $item['count'];

            $stock = Stock::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->first();

            $availableStock = $stock ? $stock->stock : 0;

            // Проверяем, достаточно ли товара на складе
            if ($availableStock < $requestedCount) {
                $productName = Product::find($productId)?->name ?? "ID {$productId}";
                throw new \Exception("Недостаточно товара {$productName}. Доступно: {$availableStock}, требуется: {$requestedCount}.");
            }
        }
    }

    /**
     * Завершить заказ и списать товары.
     *
     * @param Order $order
     * @return Order
     * @throws \Exception
     */
    public function complete(Order $order): Order
    {
        if ($order->status !== OrderStatus::ACTIVE) {
            throw new \Exception('Завершить можно только заказ в статусе "active".');
        }

        return DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                $this->stockService->decrementStock(
                    $order->warehouse_id,
                    $item->product_id,
                    $item->count,
                    $order
                );
            }

            $order->update([
                'status'       => OrderStatus::COMPLETED->value,
                'completed_at' => now(),
            ]);

            return $order;
        });
    }
}