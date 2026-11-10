<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('variant_name', 100)->default('Default');
            $table->string('sku_variant', 50)->unique();
            $table->timestamps();
            $table->index('sku_variant', 'idx_variant_sku');
        });
    }
    public function down(): void { Schema::dropIfExists('product_variants'); }
};
