<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика для генерации тестовых данных продукта.
 */
class ProductFactory extends Factory
{
    /**
     * Модель, для которой создаются тестовые записи.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Определяет начальное состояние модели продукта.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Генерируем уникальные данные продукта для тестов и сидеров
        return [
            'name' => fake()->words(3, true),
            'price' => fake()->randomFloat(2, 100, 50000),
        ];
    }
}