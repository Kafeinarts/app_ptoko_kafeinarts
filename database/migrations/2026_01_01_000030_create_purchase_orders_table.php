<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number', 50)->unique();
            $table->foreignId('outlet_id')->constrained('outlets');
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->enum('status', ['PENDING', 'RECEIVED', 'CANCELLED'])->default('PENDING');
            $table->date('po_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['outlet_id', 'supplier_id', 'status'], 'idx_po_filter');
        });
    }
    public function down(): void { Schema::dropIfExists('purchase_orders'); }
};
