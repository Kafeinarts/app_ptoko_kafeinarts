<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('stock_opname_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained('stock_opnames')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants');
            $table->integer('system_qty');
            $table->integer('physical_qty');
            $table->integer('difference_qty');
            $table->decimal('difference_value', 15, 2)->default(0);
        });
    }
    public function down(): void { Schema::dropIfExists('stock_opname_details'); }
};
