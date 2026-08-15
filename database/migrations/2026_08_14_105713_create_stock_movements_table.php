<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Метод создает структуру базы данных для таблицы 'stock_movements' (история движений товаров / Ledger), 
     * фиксирующую все приходы, списания и перемещения остатков с привязкой к складам, товарам 
     * и полиморфным документам-основаниям.
     *
     * @return void
     */
    public function up(): void
    {
        // Создаем новую таблицу 'stock_movements'
        Schema::create('stock_movements', function (Blueprint $table) {
            // Уникальный автоинкрементный первичный ключ (id, unsigned bigint)
            $table->id();
            
            // Внешний ключ на таблицу складов ('warehouses'). 
            // При удалении склада каскадно удаляется история движений, связанная с ним.
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            
            // Внешний ключ на таблицу товаров ('products'). 
            // При удалении товара каскадно удаляются все связанные записи из журнала аудита.
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            
            // Величина изменения остатка: 
            // положительное значение означает приход/зачисление, отрицательное — списание.
            $table->integer('quantity');
            
            // Полиморфная связь на документ-источник: тип документа (например, класс модели или псевдоним)
            $table->morphs('doc');

            // Дата и время фиксации движения (создания записи в журнале). 
            // Автоматически заполняется текущим системным временем базы данных.
            $table->timestamp('created_at')->useCurrent();

            // Индексы для быстрой фильтрации и оптимизации поисковых запросов
            // Составной индекс по складу и товару для ускорения выборки истории движений конкретной позиции
            $table->index(['warehouse_id', 'product_id']);
            
        });
    }

    /**
     * Метод вызывается при отмене (rollback) миграций и удаляет таблицу 'stock_movements', 
     * полностью очищая журнал аудита складских операций.
     *
     * @return void
     */
    public function down(): void
    {
        // Безопасное удаление таблицы: выполняется только в том случае, если она физически существует в БД
        Schema::dropIfExists('stock_movements');
    }
};