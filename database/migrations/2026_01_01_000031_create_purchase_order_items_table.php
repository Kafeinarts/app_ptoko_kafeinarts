<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants');
            $table->decimal('unit_cost', 15, 2);
            $table->integer('qty_ordered');
            $table->integer('qty_received')->default(0);
            $table->decimal('subtotal', 15, 2);
        });
    }
    public function down(): void { Schema::dropIfExists('purchase_order_items'); }
};
