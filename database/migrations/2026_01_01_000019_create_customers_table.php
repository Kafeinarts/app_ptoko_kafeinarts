<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_group_id')->nullable()->constrained('customer_groups')->nullOnDelete();
            $table->string('name', 100);
            $table->string('phone', 20)->unique();
            $table->string('email', 100)->nullable();
            $table->integer('loyalty_points')->default(0);
            $table->timestamps();
            $table->index('phone', 'idx_customer_phone');
        });
    }
    public function down(): void { Schema::dropIfExists('customers'); }
};
