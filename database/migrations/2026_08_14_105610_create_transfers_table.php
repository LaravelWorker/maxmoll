<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Метод создает структуру базы данных для таблицы 'transfers' (межскладские перемещения), 
     * определяя связи со складом-отправителем и складом-получателем, а также временную метку создания.
     *
     * @return void
     */
    public function up(): void
    {
        // Создаем новую таблицу 'transfers'
        Schema::create('transfers', function (Blueprint $table) {
            // Уникальный автоинкрементный первичный ключ (id, unsigned bigint)
            $table->id();
            
            // Внешний ключ на таблицу складов ('warehouses'), с которого списывается товар (отправитель). 
            // Модификатор ->cascadeOnDelete() автоматически удаляет документ перемещения при удалении склада.
            $table->foreignId('from_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            
            // Внешний ключ на таблицу складов ('warehouses'), на который поступает товар (получатель). 
            // При удалении склада-получателя связанный документ перемещения также будет удален.
            $table->foreignId('to_warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            
            // Дата и время создания (проведения) документа перемещения. 
            // Автоматически заполняется текущим системным временем базы данных при вставке записи.
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Метод вызывается при отмене (rollback) миграций и удаляет таблицу 'transfers', 
     * полностью очищая структуру базы данных от документов перемещений между складами.
     *
     * @return void
     */
    public function down(): void
    {
        // Безопасное удаление таблицы: выполняется только в том случае, если она физически существует в БД
        Schema::dropIfExists('transfers');
    }
};