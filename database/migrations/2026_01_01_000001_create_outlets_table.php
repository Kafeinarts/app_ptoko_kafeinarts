<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('outlets', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('receipt_footer')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('code', 'idx_outlet_code');
        });
    }
    public function down(): void { Schema::dropIfExists('outlets'); }
};
