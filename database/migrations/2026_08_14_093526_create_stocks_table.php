<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Выполнение миграции: создание таблицы 'stocks' (складские остатки).
     *
     * Таблица связывает товары (products) и склады (warehouses) с хранением текущего остатка.
     * Используется автоинкрементный ID для совместимости с Eloquent, уникальный составной индекс
     * для исключения дубликатов пар (warehouse_id, product_id) и CHECK-ограничение для защиты от отрицательных остатков.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            // Первичный ключ таблицы (необходим для работы $stock->id в StockService и StockResource)
            $table->id();

            // Внешний ключ на таблицу товаров ('products'). При удалении товара каскадно удаляются его остатки
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            // Внешний ключ на таблицу складов ('warehouses'). При удалении склада каскадно удаляются связанные остатки
            $table->foreignId('warehouse_id')
                ->constrained('warehouses')
                ->cascadeOnDelete();

            // Текущий физический остаток товара на данном складе
            $table->integer('stock')->default(0);

            // Метки времени создания и обновления записи (используются в StockResource)
            $table->timestamps();

            // Составной уникальный индекс: гарантирует, что для одной пары (склад + товар) существует строго одна запись
            $table->unique(['warehouse_id', 'product_id'], 'unique_warehouse_product');
        });

        // Блокирует попытки записать отрицательный остаток на уровне СУБД
        DB::statement('ALTER TABLE stocks ADD CONSTRAINT check_stock_positive CHECK (stock >= 0)');
    }

    /**
     * Откат миграции: удаление таблицы 'stocks'.
     *
     * @return void
     */
    public function down(): void
    {
        // Безопасное удаление таблицы вместе с ее индексами и ограничениями
        Schema::dropIfExists('stocks');
    }
};