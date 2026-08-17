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
     * Согласно ТЗ таблица содержит строго три поля (product_id, warehouse_id, stock), поэтому
     * первичным ключом служит составной ключ (warehouse_id, product_id) — он же гарантирует
     * уникальность пары «склад + товар». CHECK-ограничение защищает от отрицательных остатков.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
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

            // Составной первичный ключ (склад + товар) полностью соответствует схеме из ТЗ и
            // одновременно гарантирует, что для одной пары существует строго одна запись остатка.
            // Согласуется с моделью App\Models\Stock, где объявлен такой же составной ключ.
            $table->primary(['warehouse_id', 'product_id']);
        });

        // Блокирует попытки записать отрицательный остаток на уровне СУБД.
        // Синтаксис ALTER TABLE ... ADD CONSTRAINT ... CHECK поддерживается MySQL/PostgreSQL,
        // но не sqlite (используется в тестах), поэтому применяем его только для совместимых драйверов.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb', 'pgsql'], true)) {
            DB::statement('ALTER TABLE stocks ADD CONSTRAINT check_stock_positive CHECK (stock >= 0)');
        }
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