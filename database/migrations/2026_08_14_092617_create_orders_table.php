<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Consts\OrderStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->enum('status', OrderStatus::values())->default(OrderStatus::ACTIVE->value);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};