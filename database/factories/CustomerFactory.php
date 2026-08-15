<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика для генерации тестовых данных клиента.
 */
class CustomerFactory extends Factory
{
    /**
     * Модель клиента.
     *
     * @var string
     */
    protected $model = Customer::class;

    /**
     * Определяет начальное состояние модели клиента.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Генерируем случайные данные клиента для сидов и тестов
        return [
            'name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'created_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}