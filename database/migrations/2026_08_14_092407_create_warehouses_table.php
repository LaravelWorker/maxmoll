<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Метод создает структуру базы данных для таблицы 'warehouses' (склады), 
     * определяя её базовые колонки для хранения информации о складских помещениях.
     *
     * @return void
     */
    public function up(): void
    {
        // Создаем новую таблицу 'warehouses'
        Schema::create('warehouses', function (Blueprint $table) {
            // Уникальный автоинкрементный первичный ключ (id, unsigned bigint)
            $table->id();
            
            // Название (наименование) склада: обязательное строковое поле 
            // с ограничением длины в 255 символов
            $table->string('name', 255);
        });
    }

    /**
     * Метод вызывается при отмене (rollback) миграций и удаляет таблицу 'warehouses', 
     * полностью очищая структуру базы данных от информации о складах.
     *
     * @return void
     */
    public function down(): void
    {
        // Безопасное удаление таблицы: выполняется только в том случае, если она физически существует в БД
        Schema::dropIfExists('warehouses');
    }
};