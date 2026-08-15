<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Метод создает структуру базы данных для таблицы 'supply_items' (позиции поставки), 
     * связывающую документы поставок с конкретными товарами и содержащую количество единиц каждого товара.
     *
     * @return void
     */
    public function up(): void
    {
        // Создаем новую таблицу 'supply_items'
        Schema::create('supply_items', function (Blueprint $table) {
            // Уникальный автоинкрементный первичный ключ (id, unsigned bigint)
            $table->id();
            
            // Внешний ключ на таблицу поставок ('supplies'). 
            // Модификатор ->cascadeOnDelete() гарантирует каскадное удаление позиций при удалении родительской поставки.
            $table->foreignId('supply_id')->constrained('supplies')->cascadeOnDelete();
            
            // Внешний ключ на таблицу товаров ('products'). 
            // При удалении товара из каталога связанные строки позиций поставок также будут удалены.
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            
            // Количество единиц поступившего товара в рамках данной позиции
            $table->integer('count');
        });
    }

    /**
     * Метод вызывается при отмене (rollback) миграций и удаляет таблицу 'supply_items', 
     * полностью очищая структуру базы данных от информации о составе поставок.
     *
     * @return void
     */
    public function down(): void
    {
        // Безопасное удаление таблицы: выполняется только в том случае, если она физически существует в БД
        Schema::dropIfExists('supply_items');
    }
};