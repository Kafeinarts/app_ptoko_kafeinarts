<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->foreignId('outlet_id')->constrained('outlets');
            $table->foreignId('shift_id')->constrained('shifts');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->nullOnDelete();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->enum('status', ['PAID', 'VOID', 'REFUND', 'PARTIAL_REFUND'])->default('PAID');
            $table->text('notes')->nullable();
            $table->dateTime('transaction_date');
            $table->timestamps();
            $table->index(['outlet_id', 'transaction_date', 'status'], 'idx_trx_search');
            $table->index('invoice_number', 'idx_trx_invoice');
        });
    }
    public function down(): void { Schema::dropIfExists('transactions'); }
};
