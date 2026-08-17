<?php

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockService $stockService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stockService = app(StockService::class);
    }

    public function test_increment_stock_creates_stock_record_and_movement(): void
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();
        $order = Order::factory()->create(['warehouse_id' => $warehouse->id]);

        $this->stockService->incrementStock($warehouse->id, $product->id, 50, $order);

        $this->assertDatabaseHas('stocks', [
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'stock'        => 50,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => 50,
            'doc_type'     => (new Order())->getMorphClass(),
            'doc_id'       => $order->id,
        ]);
    }

    public function test_decrement_stock_successfully_reduces_quantity(): void
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();
        $order = Order::factory()->create(['warehouse_id' => $warehouse->id]);

        Stock::create([
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'stock'        => 30,
        ]);

        $this->stockService->decrementStock($warehouse->id, $product->id, 10, $order);

        $this->assertDatabaseHas('stocks', [
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'stock'        => 20,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'quantity' => -10,
        ]);
    }

    public function test_decrement_stock_throws_exception_when_stock_insufficient(): void
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();
        $order = Order::factory()->create(['warehouse_id' => $warehouse->id]);

        Stock::create([
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'stock'        => 5,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Недостаточно товара");

        $this->stockService->decrementStock($warehouse->id, $product->id, 10, $order);
    }
}