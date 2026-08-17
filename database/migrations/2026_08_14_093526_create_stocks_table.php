<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Метод создает структуру базы данных для таблицы 'stocks' (складские остатки), 
     * реализующую связь Many-to-Many между товарами и складами с хранением текущего количества 
     * каждого товара на конкретном складе.
     *
     * @return void
     */
    public function up(): void
    {
        // Создаем новую таблицу 'stocks'
        Schema::create('stocks', function (Blueprint $table) {
            // Внешний ключ на таблицу товаров ('products'). 
            // При удалении товара каскадно удаляется связанная запись об остатках.
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            
            // Внешний ключ на таблицу складов ('warehouses'). 
            // При удалении склада каскадно удаляются все остатки, привязанные к нему.
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            
            // Текущее количество товара на данном складе
            $table->integer('stock');

            // Составной первичный ключ по требованию ТЗ. 
            // Гарантирует уникальность пары товар-склад и оптимизирует поисковые запросы по остаткам.
            $table->primary(['product_id', 'warehouse_id']);
        });
    }

    /**
     * Метод вызывается при отмене (rollback) миграций и удаляет таблицу 'stocks', 
     * полностью очищая информацию об остатках товаров на складах.
     *
     * @return void
     */
    public function down(): void
    {
        // Безопасное удаление таблицы: выполняется только в том случае, если она физически существует в БД
        Schema::dropIfExists('stocks');
    }
};