<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('shift_cash_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
            $table->enum('type', ['CASH_IN', 'CASH_OUT']);
            $table->decimal('amount', 15, 2);
            $table->text('reason');
            $table->timestamp('created_at')->nullable();
        });
    }
    public function down(): void { Schema::dropIfExists('shift_cash_logs'); }
};
