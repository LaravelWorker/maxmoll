<?php

namespace Database\Factories;

use App\Models\Supply;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplyFactory extends Factory
{
    protected $model = Supply::class;

    public function definition(): array
    {
        return [
            'warehouse_id' => Warehouse::factory(),
            'created_at' => fake()->dateTimeBetween('-2 months', 'now'),
        ];
    }
}