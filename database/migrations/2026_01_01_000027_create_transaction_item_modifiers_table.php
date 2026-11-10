<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('transaction_item_modifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_item_id')->constrained('transaction_items')->cascadeOnDelete();
            $table->foreignId('product_modifier_id')->constrained('product_modifiers');
            $table->decimal('unit_price', 15, 2);
        });
    }
    public function down(): void { Schema::dropIfExists('transaction_item_modifiers'); }
};
