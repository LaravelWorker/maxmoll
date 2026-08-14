<?php

namespace Database\Seeders;

use App\Consts\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Supply;
use App\Models\SupplyItem;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Создаем основные сущности
        $products = Product::factory(20)->create();
        $customers = Customer::factory(10)->create();
        $warehouses = Warehouse::factory(4)->create();

        // 2. Генерируем остатки на складах (stocks)
        foreach ($warehouses as $warehouse) {
            foreach ($products->random(10) as $product) {
                Stock::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'stock' => rand(10, 500),
                ]);
            }
        }

        // 3. Генерируем поставки (supplies + supply_items)
        foreach ($warehouses as $warehouse) {
            $supplies = Supply::factory(3)->create([
                'warehouse_id' => $warehouse->id,
            ]);

            foreach ($supplies as $supply) {
                // В каждой поставке от 2 до 5 товаров
                $randomProducts = $products->random(rand(2, 5));
                foreach ($randomProducts as $product) {
                    SupplyItem::create([
                        'supply_id' => $supply->id,
                        'product_id' => $product->id,
                        'count' => rand(50, 200),
                    ]);
                }
            }
        }

        // 4. Генерируем заказы (orders + order_items)
        foreach ($customers as $customer) {
            $orders = Order::factory(3)->create([
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouses->random()->id,
                'status' => fake()->randomElement(OrderStatus::values()),
                'created_at' => fake()->dateTimeBetween('-1 month', 'now'),
            ]);

            foreach ($orders as $order) {
                // Если заказ завершен, проставляем completed_at
                if ($order->status === OrderStatus::COMPLETED) {
                    $order->update(['completed_at' => now()]);
                }

                // Добавляем позиции в заказ
                $randomProducts = $products->random(rand(1, 4));
                foreach ($randomProducts as $product) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'count' => rand(1, 5),
                    ]);
                }
            }
        }
    }
}