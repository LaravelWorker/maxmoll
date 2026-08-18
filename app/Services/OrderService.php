<?php

namespace App\Services;

use App\Consts\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Stock;
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
                $this->validateStockAvailability($warehouseId, $items, $order->id);
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
     * Отменить активный заказ.
     *
     * @param Order $order
     * @return Order
     * @throws \Exception
     */
    public function cancel(Order $order): Order
    {
        if ($order->status !== OrderStatus::ACTIVE) {
            throw new \Exception('Отменить можно только активный заказ.');
        }

        return DB::transaction(function () use ($order) {
            $order->update([
                'status' => OrderStatus::CANCELED->value,
            ]);

            return $order;
        });
    }

    /**
     * Возобновить отмененный заказ с проверкой наличия товаров на складе.
     *
     * @param Order $order
     * @return Order
     * @throws \Exception
     */
    public function restore(Order $order): Order
    {
        if ($order->status !== OrderStatus::CANCELED) {
            throw new \Exception('Возобновить можно только заказ в статусе "canceled".');
        }

        return DB::transaction(function () use ($order) {
            // Формируем список товаров заказа для проверки остатков
            $items = $order->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'count'      => $item->count,
            ])->toArray();

            // 1. Проверяем доступность товаров на складе
            $this->validateStockAvailability($order->warehouse_id, $items, $order->id);

            // 2. Меняем статус заказа на active
            $order->update([
                'status' => OrderStatus::ACTIVE->value,
            ]);

            return $order;
        });
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

    /**
     * Удалить заказ из базы данных.
     *
     * @param Order $order
     * @return bool
     * @throws \Exception
     */
    public function destroy(Order $order): bool
    {
        if ($order->status !== OrderStatus::ACTIVE) {
            throw new \Exception('Выполненный или отменённый заказ удалить нельзя.');
        }

        return DB::transaction(function () use ($order) {
            $order->items()->delete();
            return (bool) $order->delete();
        });
    }

    /**
     * Проверка достаточности свободного остатка на складе с учетом пессимистической блокировки и резерва активных заказов.
     *
     * @param int $warehouseId Идентификатор склада
     * @param array $items Массив позиций ['product_id' => int, 'count' => int]
     * @param int|null $exceptOrderId Исключить ID текущего заказа при расчете резерва (для update)
     * @throws \Exception
     */
    protected function validateStockAvailability(int $warehouseId, array $items, ?int $exceptOrderId = null): void
    {
        foreach ($items as $item) {
            $productId = $item['product_id'];
            $requestedCount = $item['count'];

            // 1. Получаем физический остаток с пессимистической блокировкой строки на время транзакции
            $stockModel = Stock::where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            $physicalStock = $stockModel ? $stockModel->stock : 0;

            // 2. Расчет зарезервированного товара в других активных заказах
            $reservedQuery = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.warehouse_id', $warehouseId)
                ->where('order_items.product_id', $productId)
                ->where('orders.status', OrderStatus::ACTIVE->value);

            // При редактировании исключаем позиции самого обновляемого заказа
            if ($exceptOrderId) {
                $reservedQuery->where('orders.id', '!=', $exceptOrderId);
            }

            $reservedStock = (int) $reservedQuery->sum('order_items.count');
            $availableStock = $physicalStock - $reservedStock;

            // 3. Проверка доступного баланса (Физический остаток - Резерв)
            if ($availableStock < $requestedCount) {
                $productName = Product::find($productId)?->name ?? "ID {$productId}";
                throw new \Exception("Невозможно оформить заказ на товар {$productName}: {$reservedStock} ед. зарезервировано активными заказами. Доступно: {$availableStock} ед., требуется: {$requestedCount} ед.");
            }
        }
    }
}