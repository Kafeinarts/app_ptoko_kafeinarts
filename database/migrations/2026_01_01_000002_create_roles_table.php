<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('guard_name', 50)->default('web');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('roles'); }
};
