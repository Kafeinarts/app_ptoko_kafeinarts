<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('customer_point_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->enum('type', ['EARNED', 'REDEEMED', 'EXPIRED']);
            $table->integer('points');
            $table->string('reference_number', 50)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }
    public function down(): void { Schema::dropIfExists('customer_point_logs'); }
};
