<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Метод создает структуру базы данных для таблицы 'customers' (покупатели/клиенты), 
     * определяя колонки для хранения базовой контактной информации.
     *
     * @return void
     */
    public function up(): void
    {
        // Создаем новую таблицу 'customers'
        Schema::create('customers', function (Blueprint $table) {
            // Уникальный автоинкрементный первичный ключ (id, unsigned bigint)
            $table->id();
            
            // Имя (или ФИО) покупателя: обязательное строковое поле с ограничением длины в 255 символов
            $table->string('name', 255);
            
            // Контактный номер телефона: строковое поле до 255 символов.
            // Модификатор ->nullable() разрешает сохранять значение null, если телефон не указан.
            $table->string('phone', 255)->nullable();
            
            // Адрес электронной почты: строковое поле до 255 символов.
            // Также может быть пустым (nullable), если email не был предоставлен клиентом.
            $table->string('email', 255)->nullable();
            
            // Дата и время создания записи о клиенте.
            // Модификатор ->useCurrent() указывает базе данных автоматически подставлять 
            // текущее системное время (CURRENT_TIMESTAMP) при вставке новой строки.
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Метод вызывается при отмене (rollback) миграций и удаляет таблицу 'customers', 
     * полностью очищая структуру базы данных от профилей покупателей.
     *
     * @return void
     */
    public function down(): void
    {
        // Безопасное удаление таблицы: выполняется только в том случае, если она физически существует в БД
        Schema::dropIfExists('customers');
    }
};