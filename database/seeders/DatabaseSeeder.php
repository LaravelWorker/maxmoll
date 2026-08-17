<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\OrderService;
use App\Services\SupplyService;
use App\Services\TransferService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Запуск наполнения базы данных тестовыми данными.
     *
     * @param SupplyService $supplyService
     * @param TransferService $transferService
     * @param OrderService $orderService
     * @return void
     */
    public function run(
        SupplyService $supplyService,
        TransferService $transferService,
        OrderService $orderService
    ): void {
        // 1. Создаем базовые справочники
        $products = Product::factory(20)->create();
        $customers = Customer::factory(10)->create();
        $warehouses = Warehouse::factory(4)->create();

        // 2. Первоначальный ввод остатков через SupplyService
        foreach ($warehouses as $warehouse) {
            $items = [];
            foreach ($products->random(15) as $product) {
                $items[] = [
                    'product_id' => $product->id,
                    'count'      => rand(200, 1000),
                ];
            }

            $supplyService->createAndExecute([
                'warehouse_id' => $warehouse->id,
                'items'        => $items,
            ]);
        }

        // 3. Генерируем дополнительные операционные поставки через SupplyService
        foreach ($warehouses as $warehouse) {
            for ($i = 0; $i < 2; $i++) {
                $items = [];
                foreach ($products->random(rand(2, 5)) as $product) {
                    $items[] = [
                        'product_id' => $product->id,
                        'count'      => rand(50, 200),
                    ];
                }

                $supplyService->createAndExecute([
                    'warehouse_id' => $warehouse->id,
                    'items'        => $items,
                ]);
            }
        }

        // 4. Генерируем заказы через OrderService
        foreach ($customers as $customer) {
            for ($i = 0; $i < 3; $i++) {
                $warehouse = $warehouses->random();
                $items = [];

                foreach ($products->random(rand(1, 4)) as $product) {
                    $items[] = [
                        'product_id' => $product->id,
                        'count'      => rand(1, 5),
                    ];
                }

                try {
                    // Создаем активный заказ
                    $order = $orderService->create([
                        'customer_id'  => $customer->id,
                        'warehouse_id' => $warehouse->id,
                        'items'        => $items,
                    ]);

                    // Завершаем или отменяем заказы строго через методы OrderService
                    if (fake()->boolean(60)) {
                        $orderService->complete($order);
                    } elseif (fake()->boolean(30)) {
                        $orderService->cancel($order);
                    }
                } catch (\Throwable $e) {
                    // Пропускаем в случае нехватки товара
                    continue;
                }
            }
        }

        // 5. Генерируем межскладские перемещения через TransferService
        for ($i = 0; $i < 5; $i++) {
            $warehouseIds = $warehouses->random(2)->pluck('id');

            $items = [];
            foreach ($products->random(rand(1, 3)) as $product) {
                $items[] = [
                    'product_id' => $product->id,
                    'count'      => rand(5, 15),
                ];
            }

            try {
                $transferService->createAndExecute([
                    'from_warehouse_id' => $warehouseIds[0],
                    'to_warehouse_id'   => $warehouseIds[1],
                    'items'             => $items,
                ]);
            } catch (\Throwable $e) {
                continue;
            }
        }
    }
}