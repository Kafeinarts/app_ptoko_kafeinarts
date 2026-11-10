<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('transaction_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_return_id')->constrained('transaction_returns')->cascadeOnDelete();
            $table->foreignId('transaction_item_id')->constrained('transaction_items');
            $table->integer('qty');
            $table->decimal('amount', 15, 2);
        });
    }
    public function down(): void { Schema::dropIfExists('transaction_return_items'); }
};
