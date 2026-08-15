<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Метод создает структуру базы данных для таблицы 'products' (товары), 
     * определяя её колонки и типы данных.
     *
     * @return void
     */
    public function up(): void
    {
        // Создаем новую таблицу 'products'
        Schema::create('products', function (Blueprint $table) {
            // Уникальный автоинкрементный первичный ключ (id, unsigned bigint)
            $table->id();
            
            // Название (наименование) товара: строковый тип с ограничением длины в 255 символов
            $table->string('name', 255);
            
            // Базовая цена товара: число с плавающей точкой
            // Ограничение: максимум 8 цифр всего, из которых 2 цифры после запятой
            $table->float('price', 8, 2);
        });
    }

    /**
     * Метод вызывается при отмене (rollback) миграций и удаляет таблицу 'products', 
     * очищая структуру базы данных.
     *
     * @return void
     */
    public function down(): void
    {
        // Безопасное удаление таблицы: выполняется только в том случае, если она существует в БД
        Schema::dropIfExists('products');
    }
};