<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained('outlets');
            $table->foreignId('expense_category_id')->constrained('expense_categories');
            $table->foreignId('user_id')->constrained('users');
            $table->decimal('amount', 15, 2);
            $table->text('notes')->nullable();
            $table->date('expense_date');
            $table->timestamps();
            $table->index(['outlet_id', 'expense_date'], 'idx_expense_search');
        });
    }
    public function down(): void { Schema::dropIfExists('expenses'); }
};
