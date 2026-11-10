<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->string('opname_number', 50)->unique();
            $table->foreignId('outlet_id')->constrained('outlets');
            $table->foreignId('user_id')->constrained('users');
            $table->date('opname_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('stock_opnames'); }
};
