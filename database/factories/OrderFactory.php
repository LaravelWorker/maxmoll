<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика для генерации тестовых данных заказа.
 */
class OrderFactory extends Factory
{
    /**
     * Модель заказа.
     *
     * @var string
     */
    protected $model = Order::class;

    /**
     * Определяет начальное состояние модели заказа.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Создаём заказ со случайным клиентом, складом и статусом "active"
        return [
            'customer_id' => Customer::factory(),
            'warehouse_id' => Warehouse::factory(),
            'status' => 'active',
            'created_at' => now(),
        ];
    }
}