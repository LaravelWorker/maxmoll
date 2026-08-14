<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'warehouse_id' => Warehouse::factory(),
            'status' => 'active',
            'created_at' => now(),
        ];
    }
}