<?php

namespace Tests\Feature;

use App\Consts\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_order_if_stock_is_available(): void
    {
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        Stock::create([
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'stock'        => 100,
        ]);

        $payload = [
            'customer_id'  => $customer->id,
            'warehouse_id' => $warehouse->id,
            'items' => [
                ['product_id' => $product->id, 'count' => 5],
            ],
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'active');

        $this->assertDatabaseHas('orders', [
            'customer_id'  => $customer->id,
            'warehouse_id' => $warehouse->id,
            'status'       => OrderStatus::ACTIVE->value,
        ]);
    }

    public function test_cannot_create_order_if_stock_is_insufficient(): void
    {
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        Stock::create([
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'stock'        => 2,
        ]);

        $payload = [
            'customer_id'  => $customer->id,
            'warehouse_id' => $warehouse->id,
            'items' => [
                ['product_id' => $product->id, 'count' => 10],
            ],
        ];

        $response = $this->postJson('/api/orders', $payload);

        $response->assertStatus(422); // Или 500/400 в зависимости от вашего ExceptionHandler
    }

    public function test_completing_order_deducts_stock_and_changes_status(): void
    {
        $customer = Customer::factory()->create();
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        Stock::create([
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'stock'        => 50,
        ]);

        $order = Order::factory()->create([
            'customer_id'  => $customer->id,
            'warehouse_id' => $warehouse->id,
            'status'       => OrderStatus::ACTIVE->value,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'count'      => 15,
        ]);

        $response = $this->postJson("/api/orders/{$order->id}/complete");

        $response->assertStatus(200);

        // Проверяем, что остаток списался: 50 - 15 = 35
        $this->assertDatabaseHas('stocks', [
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'stock'        => 35,
        ]);

        // Проверяем, что статус заказа изменился
        $this->assertDatabaseHas('orders', [
            'id'     => $order->id,
            'status' => OrderStatus::COMPLETED->value,
        ]);
    }
}