<?php

namespace Database\Seeders;

use App\Consts\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Supply;
use App\Models\SupplyItem;
use App\Models\Warehouse;
use App\Services\TransferService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Запуск наполнения базы данных тестовыми данными.
     *
     * @return void
     */
    public function run(): void
    {
        $transferService = new TransferService();

        // 1. Создаем основные сущности
        $products = Product::factory(20)->create();
        $customers = Customer::factory(10)->create();
        $warehouses = Warehouse::factory(4)->create();

        // 2. Генерируем остатки на складах (stocks) с запасом, чтобы хватало на перемещения
        foreach ($warehouses as $warehouse) {
            foreach ($products->random(15) as $product) {
                Stock::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $warehouse->id,
                    'stock' => rand(200, 1000), // Увеличиваем начальный объем для тестов
                ]);
            }
        }

        // 3. Генерируем поставки (supplies + supply_items + stock_movements)
        foreach ($warehouses as $warehouse) {
            $supplies = Supply::factory(3)->create([
                'warehouse_id' => $warehouse->id,
            ]);

            foreach ($supplies as $supply) {
                $randomProducts = $products->random(rand(2, 5));
                foreach ($randomProducts as $product) {
                    $count = rand(50, 200);

                    SupplyItem::create([
                        'supply_id' => $supply->id,
                        'product_id' => $product->id,
                        'count' => $count,
                    ]);

                    // Фиксируем движение товара (приход)
                    StockMovement::create([
                        'warehouse_id' => $warehouse->id,
                        'product_id' => $product->id,
                        'quantity' => $count,
                        'doc_type' => Supply::class,
                        'doc_id' => $supply->id,
                        'created_at' => $supply->created_at,
                    ]);

                    // Пополняем остаток в таблице stocks
                    $transferService->changeStock($warehouse->id, $product->id, $count);
                }
            }
        }

        // 4. Генерируем заказы (orders + order_items + stock_movements)
        foreach ($customers as $customer) {
            $orders = Order::factory(3)->create([
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouses->random()->id,
                'status' => fake()->randomElement(OrderStatus::values()),
                'created_at' => fake()->dateTimeBetween('-1 month', 'now'),
            ]);

            foreach ($orders as $order) {
                $randomProducts = $products->random(rand(1, 4));
                foreach ($randomProducts as $product) {
                    $count = rand(1, 5);

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'count' => $count,
                    ]);

                    // Если заказ завершен сразу при сидинге, списываем со склада и пишем движение
                    if ($order->status === OrderStatus::COMPLETED) {
                        StockMovement::create([
                            'warehouse_id' => $order->warehouse_id,
                            'product_id' => $product->id,
                            'quantity' => -$count,
                            'doc_type' => Order::class,
                            'doc_id' => $order->id,
                            'created_at' => $order->created_at,
                        ]);

                        $transferService->changeStock($order->warehouse_id, $product->id, -$count);
                    }
                }

                if ($order->status === OrderStatus::COMPLETED) {
                    $order->update(['completed_at' => now()]);
                }
            }
        }

        // 5. Генерируем межскладские перемещения через обновленный TransferService
        foreach (range(1, 5) as $i) {
            $warehouseIds = Warehouse::inRandomOrder()->limit(2)->pluck('id');
            if ($warehouseIds->count() < 2) {
                continue;
            }

            // Формируем массив позиций для сервиса
            $items = [];
            $randomProducts = $products->random(rand(1, 3));
            foreach ($randomProducts as $product) {
                $items[] = [
                    'product_id' => $product->id,
                    'count' => rand(5, 15), // Небольшое количество, чтобы точно хватило остатка
                ];
            }

            try {
                // Передаем данные в метод createAndExecute, который проверяет остатки,
                // резервы, создает документ, проводки и обновляет таблицу stocks
                $transferService->createAndExecute([
                    'from_warehouse_id' => $warehouseIds[0],
                    'to_warehouse_id'   => $warehouseIds[1],
                    'items'             => $items,
                ]);
            } catch (\Exception $e) {
                continue;
            }
        }
    }
}