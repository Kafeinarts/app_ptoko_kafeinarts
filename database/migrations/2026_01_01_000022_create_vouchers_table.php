<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->enum('type', ['PERCENTAGE', 'FIXED_AMOUNT']);
            $table->decimal('value', 15, 2);
            $table->decimal('min_purchase', 15, 2)->default(0);
            $table->decimal('max_discount', 15, 2)->default(0);
            $table->dateTime('valid_from');
            $table->dateTime('valid_until');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['code', 'is_active'], 'idx_voucher_code');
        });
    }
    public function down(): void { Schema::dropIfExists('vouchers'); }
};
