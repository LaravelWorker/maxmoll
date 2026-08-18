<?php

namespace Tests\Unit\Services;

use App\Consts\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderService $orderService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orderService = app(OrderService::class);
    }

    public function test_create_order_successfully(): void
    {
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        Stock::create([
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'stock'        => 50,
        ]);

        $order = $this->orderService->create([
            'customer_id'  => $customer->id,
            'warehouse_id' => $warehouse->id,
            'items'        => [
                ['product_id' => $product->id, 'count' => 10],
            ],
        ]);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals(OrderStatus::ACTIVE, $order->status);

        $this->assertDatabaseHas('orders', [
            'id'           => $order->id,
            'customer_id'  => $customer->id,
            'warehouse_id' => $warehouse->id,
            'status'       => OrderStatus::ACTIVE->value,
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id'   => $order->id,
            'product_id' => $product->id,
            'count'      => 10,
        ]);
    }

    public function test_create_order_fails_when_stock_is_insufficient(): void
    {
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create(['name' => 'Тестовый товар']);

        Stock::create([
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'stock'        => 3,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Невозможно оформить заказ на товар Тестовый товар');

        $this->orderService->create([
            'customer_id'  => $customer->id,
            'warehouse_id' => $warehouse->id,
            'items'        => [
                ['product_id' => $product->id, 'count' => 10],
            ],
        ]);
    }

    public function test_update_active_order_replaces_items_successfully(): void
    {
        $warehouse = Warehouse::factory()->create();
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        Stock::create(['warehouse_id' => $warehouse->id, 'product_id' => $product1->id, 'stock' => 20]);
        Stock::create(['warehouse_id' => $warehouse->id, 'product_id' => $product2->id, 'stock' => 20]);

        $order = $this->orderService->create([
            'customer_id'  => Customer::factory()->create()->id,
            'warehouse_id' => $warehouse->id,
            'items'        => [
                ['product_id' => $product1->id, 'count' => 5],
            ],
        ]);

        $updatedOrder = $this->orderService->update($order, [
            'items' => [
                ['product_id' => $product2->id, 'count' => 15],
            ],
        ]);

        // Проверяем, что старая позиция удалена, а новая создана
        $this->assertDatabaseMissing('order_items', ['order_id' => $order->id, 'product_id' => $product1->id]);
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'product_id' => $product2->id, 'count' => 15]);
    }

    public function test_update_completed_or_canceled_order_throws_exception(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::COMPLETED->value,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Нельзя редактировать выполненный или отмененный заказ.');

        $this->orderService->update($order, ['customer_id' => Customer::factory()->create()->id]);
    }

    public function test_complete_active_order_deducts_stock_and_sets_status(): void
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        Stock::create([
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'stock'        => 30,
        ]);

        $order = $this->orderService->create([
            'customer_id'  => Customer::factory()->create()->id,
            'warehouse_id' => $warehouse->id,
            'items'        => [
                ['product_id' => $product->id, 'count' => 12],
            ],
        ]);

        $completedOrder = $this->orderService->complete($order);

        $this->assertEquals(OrderStatus::COMPLETED, $completedOrder->status);
        $this->assertNotNull($completedOrder->completed_at);

        // Проверяем списание остатка: 30 - 12 = 18
        $this->assertDatabaseHas('stocks', [
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'stock'        => 18,
        ]);

        // Проверяем фиксацию движения в аудите
        $this->assertDatabaseHas('stock_movements', [
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => -12,
            'doc_type'     => (new Order())->getMorphClass(),
            'doc_id'       => $order->id,
        ]);
    }

    public function test_complete_non_active_order_throws_exception(): void
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::COMPLETED->value,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Завершить можно только заказ в статусе "active".');

        $this->orderService->complete($order);
    }
}