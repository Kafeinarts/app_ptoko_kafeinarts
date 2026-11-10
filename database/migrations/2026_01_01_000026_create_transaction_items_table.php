<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants');
            $table->decimal('cost_price', 15, 2);
            $table->decimal('selling_price', 15, 2);
            $table->integer('qty');
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->integer('returned_qty')->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->index(['transaction_id', 'product_variant_id'], 'idx_trx_items');
        });
    }
    public function down(): void { Schema::dropIfExists('transaction_items'); }
};
