<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('stock_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained('outlets');
            $table->foreignId('product_variant_id')->constrained('product_variants');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('type', ['IN', 'OUT', 'ADJUSTMENT', 'TRANSFER', 'VOID', 'RETURN']);
            $table->integer('qty_change');
            $table->integer('qty_balance');
            $table->string('reference_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['outlet_id', 'product_variant_id', 'type', 'created_at'], 'idx_stock_log_filter');
        });
    }
    public function down(): void { Schema::dropIfExists('stock_logs'); }
};
