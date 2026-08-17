<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplyApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_supply_increments_stock_balance(): void
    {
        $warehouse = Warehouse::factory()->create();
        $product = Product::factory()->create();

        $payload = [
            'warehouse_id' => $warehouse->id,
            'items' => [
                ['product_id' => $product->id, 'count' => 100],
            ],
        ];

        $response = $this->postJson('/api/supplies', $payload);

        $response->assertStatus(201);

        $this->assertDatabaseHas('supplies', [
            'warehouse_id' => $warehouse->id,
        ]);

        $this->assertDatabaseHas('stocks', [
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'stock'        => 100,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'warehouse_id' => $warehouse->id,
            'product_id'   => $product->id,
            'quantity'     => 100,
        ]);
    }
}