<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Метод создает структуру базы данных для таблицы 'supplies' (поставки), 
     * определяя связь со складом назначения и временную метку создания документа.
     *
     * @return void
     */
    public function up(): void
    {
        // Создаем новую таблицу 'supplies'
        Schema::create('supplies', function (Blueprint $table) {
            // Уникальный автоинкрементный первичный ключ (id, unsigned bigint)
            $table->id();
            
            // Внешний ключ на таблицу складов ('warehouses'), на который поступает товар. 
            // Модификатор ->cascadeOnDelete() автоматически удаляет документ поставки при удалении связанного склада.
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            
            // Дата и время создания (проведения) поставки. 
            // Автоматически заполняется текущим системным временем базы данных при вставке записи.
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Метод вызывается при отмене (rollback) миграций и удаляет таблицу 'supplies', 
     * полностью очищая структуру базы данных от документов поставок.
     *
     * @return void
     */
    public function down(): void
    {
        // Безопасное удаление таблицы: выполняется только в том случае, если она физически существует в БД
        Schema::dropIfExists('supplies');
    }
};