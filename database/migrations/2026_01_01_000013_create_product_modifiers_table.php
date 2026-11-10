<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('product_modifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modifier_group_id')->constrained('product_modifier_groups')->cascadeOnDelete();
            $table->string('name', 100);
            $table->decimal('price', 15, 2)->default(0);
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('product_modifiers'); }
};
