<?php

namespace Database\Factories;

use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика для генерации тестовых данных склада.
 */
class WarehouseFactory extends Factory
{
    /**
     * Модель склада.
     *
     * @var string
     */
    protected $model = Warehouse::class;

    /**
     * Определяет начальное состояние модели склада.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Создаём случайное имя склада для тестового окружения
        return [
            'name' => fake()->city(),
        ];
    }
}