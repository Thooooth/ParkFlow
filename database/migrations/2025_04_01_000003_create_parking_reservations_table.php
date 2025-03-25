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
        Schema::create('parking_reservations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parking_lot_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->string('vehicle_plate')->nullable();
            $table->string('vehicle_model')->nullable();
            $table->string('vehicle_color')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed', 'no_show'])->default('pending');
            $table->string('confirmation_code')->unique();
            $table->string('qr_code')->nullable();
            $table->decimal('reservation_fee', 10, 2);
            $table->decimal('estimated_total', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->string('discount_code')->nullable();
            $table->boolean('is_paid')->default(false);
            $table->string('payment_id')->nullable();
            $table->string('payment_method')->nullable();
            $table->dateTime('check_in_time')->nullable();
            $table->dateTime('check_out_time')->nullable();
            $table->foreignId('parking_session_id')->nullable()->constrained();
            $table->boolean('reminder_sent')->default(false);
            $table->dateTime('reminder_sent_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parking_reservations');
    }
};
