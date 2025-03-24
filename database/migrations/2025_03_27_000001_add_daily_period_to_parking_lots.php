<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('parking_lots', function (Blueprint $table): void {
            $table->integer('daily_period')->after('daily_rate')->default(24);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parking_lots', function (Blueprint $table): void {
            $table->dropColumn('daily_period');
        });
    }
};
