<?php

namespace Database\Factories;

use App\Models\Supply;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика для генерации тестовых данных поставки.
 */
class SupplyFactory extends Factory
{
    /**
     * Модель поставки.
     *
     * @var string
     */
    protected $model = Supply::class;

    /**
     * Определяет начальное состояние модели поставки.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Привязываем поставку к случайному складу и задаём дату в диапазоне последних 2 месяцев
        return [
            'warehouse_id' => Warehouse::factory(),
            'created_at' => fake()->dateTimeBetween('-2 months', 'now'),
        ];
    }
}