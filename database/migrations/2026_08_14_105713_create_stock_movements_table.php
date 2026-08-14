<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            
            // Количество изменения (положительное: приход/зачисление, отрицательное: списание)
            $table->integer('quantity');
            
            // Полиморфная связь на документ-источник (doc_type, doc_id)
            $table->string('doc_type', 255);
            $table->unsignedBigInteger('doc_id');

            $table->timestamp('created_at')->useCurrent();

            // Индексы для быстрой фильтрации
            $table->index(['warehouse_id', 'product_id']);
            $table->index(['doc_type', 'doc_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};