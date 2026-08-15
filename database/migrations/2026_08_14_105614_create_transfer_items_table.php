<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Метод создает структуру базы данных для таблицы 'transfer_items' (позиции перемещения), 
     * связывающую документы межскладских перемещений с конкретными товарами и содержащую количество единиц каждого товара.
     *
     * @return void
     */
    public function up(): void
    {
        // Создаем новую таблицу 'transfer_items'
        Schema::create('transfer_items', function (Blueprint $table) {
            // Уникальный автоинкрементный первичный ключ (id, unsigned bigint)
            $table->id();
            
            // Внешний ключ на таблицу перемещений ('transfers'). 
            // Модификатор ->cascadeOnDelete() гарантирует каскадное удаление позиций при удалении родительского документа перемещения.
            $table->foreignId('transfer_id')->constrained('transfers')->cascadeOnDelete();
            
            // Внешний ключ на таблицу товаров ('products'). 
            // При удалении товара из каталога связанные строки позиций перемещений также будут удалены.
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            
            // Количество единиц перемещаемого товара в рамках данной позиции
            $table->integer('count');
        });
    }

    /**
     * Метод вызывается при отмене (rollback) миграций и удаляет таблицу 'transfer_items', 
     * полностью очищая структуру базы данных от информации о составе межскладских перемещений.
     *
     * @return void
     */
    public function down(): void
    {
        // Безопасное удаление таблицы: выполняется только в том случае, если она физически существует в БД
        Schema::dropIfExists('transfer_items');
    }
};