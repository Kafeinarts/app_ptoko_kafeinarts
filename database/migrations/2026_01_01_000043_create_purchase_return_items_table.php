<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained('purchase_returns')->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->constrained('purchase_order_items');
            $table->integer('qty');
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('subtotal', 15, 2);
        });
    }
    public function down(): void { Schema::dropIfExists('purchase_return_items'); }
};
