<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('outlets', function (Blueprint $table) {
            $table->foreignId('manager_id')->nullable()->after('receipt_footer')->constrained('users')->nullOnDelete();
            $table->index('manager_id', 'idx_outlet_manager');
        });
    }
    public function down(): void {
        Schema::table('outlets', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
            $table->dropIndex('idx_outlet_manager');
            $table->dropColumn('manager_id');
        });
    }
};
