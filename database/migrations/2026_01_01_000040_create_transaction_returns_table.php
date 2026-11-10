<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('transaction_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number', 50)->unique();
            $table->foreignId('transaction_id')->constrained('transactions');
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('reason')->nullable();
            $table->dateTime('return_date');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('transaction_returns'); }
};
