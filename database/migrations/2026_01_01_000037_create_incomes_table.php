<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained('outlets');
            $table->foreignId('income_category_id')->constrained('income_categories');
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('amount', 15, 2);
            $table->text('notes')->nullable();
            $table->date('income_date');
            $table->timestamps();
            $table->index(['outlet_id', 'income_date'], 'idx_income_search');
        });
    }
    public function down(): void { Schema::dropIfExists('incomes'); }
};
