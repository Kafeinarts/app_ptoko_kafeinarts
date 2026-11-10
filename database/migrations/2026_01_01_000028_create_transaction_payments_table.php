<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('transaction_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->enum('payment_method', ['CASH', 'QRIS', 'TRANSFER', 'DEBIT', 'CREDIT']);
            $table->decimal('amount_paid', 15, 2);
            $table->decimal('change_returned', 15, 2)->default(0);
            $table->string('reference_note', 100)->nullable();
            $table->timestamps();
            $table->index('payment_method', 'idx_payment_method');
        });
    }
    public function down(): void { Schema::dropIfExists('transaction_payments'); }
};
