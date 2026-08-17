<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Consts\OrderStatus;

return new class extends Migration
{
    /**
     *
     * Метод создает структуру базы данных для таблицы 'orders' (заказы), 
     * определяя связи с покупателями и складами, а также статусы и временные метки.
     *
     * @return void
     */
    public function up(): void
    {
        // Создаем новую таблицу 'orders'
        Schema::create('orders', function (Blueprint $table) {
            // Уникальный автоинкрементный первичный ключ (id, unsigned bigint)
            $table->id();
            
            // Внешний ключ на таблицу клиентов ('customers'). 
            // Модификатор ->cascadeOnDelete() автоматически удаляет заказы при удалении связанного клиента.
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            
            // Дата и время создания заказа. Автоматически заполняется текущим временем базы данных.
            $table->timestamp('created_at')->useCurrent();
            
            // Дата и время фактического завершения (проведения/закрытия) заказа. 
            // Может быть null, пока заказ находится в активном состоянии.
            $table->timestamp('completed_at')->nullable();
            
            // Внешний ключ на таблицу складов ('warehouses'), с которого производится отгрузка по заказу.
            // При удалении склада каскадно удаляются связанные с ним заказы.
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            
            // Статус заказа. Согласно ТЗ хранится как varchar(255) ("active", "completed", "canceled").
            // Значение по умолчанию при создании — ACTIVE. Ограничение допустимых значений
            // обеспечивается на уровне приложения (Enum-каст в модели Order и валидация в Request).
            $table->string('status', 255)->default(OrderStatus::ACTIVE->value);

            // Индекс по статусу ускоряет частые выборки активных заказов и расчёт резервов.
            $table->index('status');
        });
    }

    /**
     *
     * Метод вызывается при отмене (rollback) миграций и удаляет таблицу 'orders', 
     * полностью очищая структуру базы данных от документов заказов.
     *
     * @return void
     */
    public function down(): void
    {
        // Безопасное удаление таблицы: выполняется только в том случае, если она физически существует в БД
        Schema::dropIfExists('orders');
    }
};