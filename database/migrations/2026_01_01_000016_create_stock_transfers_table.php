<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number', 50)->unique();
            $table->foreignId('from_outlet_id')->constrained('outlets');
            $table->foreignId('to_outlet_id')->constrained('outlets');
            $table->foreignId('user_id')->constrained('users');
            $table->enum('status', ['PENDING', 'SENT', 'RECEIVED', 'CANCELLED'])->default('PENDING');
            $table->date('transfer_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('stock_transfers'); }
};
