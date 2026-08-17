<?php

namespace Tests\Unit\Services;

use App\Consts\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Transfer;
use App\Models\Warehouse;
use App\Services\TransferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferServiceTest extends TestCase
{
    use RefreshDatabase;

    private TransferService $transferService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transferService = app(TransferService::class);
    }

    public function test_successful_transfer_between_warehouses(): void
    {
        $fromWarehouse = Warehouse::factory()->create();
        $toWarehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        // Устанавливаем начальный остаток 100 ед. на складе-отправителе
        Stock::create([
            'warehouse_id' => $fromWarehouse->id,
            'product_id'   => $product->id,
            'stock'        => 100,
        ]);

        $transfer = $this->transferService->createAndExecute([
            'from_warehouse_id' => $fromWarehouse->id,
            'to_warehouse_id'   => $toWarehouse->id,
            'items'             => [
                ['product_id' => $product->id, 'count' => 30],
            ],
        ]);

        $this->assertInstanceOf(Transfer::class, $transfer);

        // 1. Проверяем остаток на складе-отправителе (100 - 30 = 70)
        $this->assertDatabaseHas('stocks', [
            'warehouse_id' => $fromWarehouse->id,
            'product_id'   => $product->id,
            'stock'        => 70,
        ]);

        // 2. Проверяем остаток на складе-получателе (0 + 30 = 30)
        $this->assertDatabaseHas('stocks', [
            'warehouse_id' => $toWarehouse->id,
            'product_id'   => $product->id,
            'stock'        => 30,
        ]);

        // 3. Проверяем документ перемещения и позицию
        $this->assertDatabaseHas('transfers', [
            'id'                => $transfer->id,
            'from_warehouse_id' => $fromWarehouse->id,
            'to_warehouse_id'   => $toWarehouse->id,
        ]);

        $this->assertDatabaseHas('transfer_items', [
            'transfer_id' => $transfer->id,
            'product_id'  => $product->id,
            'count'       => 30,
        ]);

        // 4. Проверяем парные записи в журнале движений
        $this->assertDatabaseHas('stock_movements', [
            'warehouse_id' => $fromWarehouse->id,
            'product_id'   => $product->id,
            'quantity'     => -30,
            'doc_type'     => (new Transfer())->getMorphClass(),
            'doc_id'       => $transfer->id,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'warehouse_id' => $toWarehouse->id,
            'product_id'   => $product->id,
            'quantity'     => 30,
            'doc_type'     => (new Transfer())->getMorphClass(),
            'doc_id'       => $transfer->id,
        ]);
    }

    public function test_transfer_fails_when_physical_stock_is_insufficient(): void
    {
        $fromWarehouse = Warehouse::factory()->create();
        $toWarehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        Stock::create([
            'warehouse_id' => $fromWarehouse->id,
            'product_id'   => $product->id,
            'stock'        => 10,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Недостаточно товара на складе-отправителе');

        $this->transferService->createAndExecute([
            'from_warehouse_id' => $fromWarehouse->id,
            'to_warehouse_id'   => $toWarehouse->id,
            'items'             => [
                ['product_id' => $product->id, 'count' => 20],
            ],
        ]);
    }

    public function test_transfer_fails_when_stock_is_reserved_by_active_orders(): void
    {
        $fromWarehouse = Warehouse::factory()->create();
        $toWarehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();
        $customer = Customer::factory()->create();

        // Физический остаток = 50 ед.
        Stock::create([
            'warehouse_id' => $fromWarehouse->id,
            'product_id'   => $product->id,
            'stock'        => 50,
        ]);

        // Создаем активный заказ, который резервирует 40 ед.
        $order = Order::factory()->create([
            'customer_id'  => $customer->id,
            'warehouse_id' => $fromWarehouse->id,
            'status'       => OrderStatus::ACTIVE->value,
        ]);

        OrderItem::create([
            'order_id'   => $order->id,
            'product_id' => $product->id,
            'count'      => 40,
        ]);

        // Свободный остаток: 50 - 40 = 10 ед. Запрос на 20 ед. должен отклоняться.
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('зарезервировано активными заказами клиентов');

        $this->transferService->createAndExecute([
            'from_warehouse_id' => $fromWarehouse->id,
            'to_warehouse_id'   => $toWarehouse->id,
            'items'             => [
                ['product_id' => $product->id, 'count' => 20],
            ],
        ]);
    }
}