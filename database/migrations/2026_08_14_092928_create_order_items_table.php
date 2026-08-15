<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Метод создает структуру базы данных для таблицы 'order_items' (позиции заказа), 
     * связывающую заказы с конкретными товарами и содержащую количество единиц каждого товара.
     *
     * @return void
     */
    public function up(): void
    {
        // Создаем новую таблицу 'order_items'
        Schema::create('order_items', function (Blueprint $table) {
            // Уникальный автоинкрементный первичный ключ (id, unsigned bigint)
            $table->id();
            
            // Внешний ключ на таблицу заказов ('orders'). 
            // Модификатор ->cascadeOnDelete() гарантирует каскадное удаление позиций при удалении родительского заказа.
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            
            // Внешний ключ на таблицу товаров ('products'). 
            // При удалении товара из каталога связанные строки позиций заказов также будут удалены.
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            
            // Количество единиц выбранного товара, входящего в состав заказа
            $table->unsignedInteger('count');
        });
    }

    /**
     * Метод вызывается при отмене (rollback) миграций и удаляет таблицу 'order_items', 
     * полностью очищая структуру базы данных от информации о составе заказов.
     *
     * @return void
     */
    public function down(): void
    {
        // Безопасное удаление таблицы: выполняется только в том случае, если она физически существует в БД
        Schema::dropIfExists('order_items');
    }
};