<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories');
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->foreignId('unit_id')->constrained('units');
            $table->string('sku', 50)->unique();
            $table->string('barcode', 100)->nullable()->unique();
            $table->string('name', 150);
            $table->string('image_path')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['sku', 'barcode', 'name', 'is_active'], 'idx_product_search');
        });
    }
    public function down(): void { Schema::dropIfExists('products'); }
};
