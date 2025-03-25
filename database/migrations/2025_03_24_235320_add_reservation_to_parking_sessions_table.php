<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('parking_sessions', function (Blueprint $table) {
            $table->foreignId('reservation_id')->nullable()->after('status')
                ->constrained('parking_reservations')->nullOnDelete();
            $table->boolean('is_late_checkout')->default(false)->after('reservation_id');
            $table->decimal('late_fee', 10, 2)->default(0)->after('is_late_checkout');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parking_sessions', function (Blueprint $table) {
            $table->dropForeign(['reservation_id']);
            $table->dropColumn(['reservation_id', 'is_late_checkout', 'late_fee']);
        });
    }
};
