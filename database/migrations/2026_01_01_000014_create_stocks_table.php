<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->integer('min_stock')->default(5);
            $table->timestamps();
            $table->unique(['outlet_id', 'product_variant_id'], 'idx_outlet_variant_stock');
            $table->index(['outlet_id', 'quantity', 'min_stock'], 'idx_stock_alert');
        });
    }
    public function down(): void { Schema::dropIfExists('stocks'); }
};
