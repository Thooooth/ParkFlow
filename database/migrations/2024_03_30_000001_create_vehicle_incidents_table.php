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
        Schema::create('vehicle_incidents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parking_lot_id')->constrained();
            $table->foreignId('parking_session_id')->constrained();
            $table->foreignId('reported_by')->nullable()->constrained('users');
            $table->foreignId('valet_operator_id')->nullable()->constrained('valet_operators');
            $table->enum('incident_type', ['pre_parking', 'during_parking', 'post_parking']);
            $table->string('title');
            $table->text('description');
            $table->json('media_files')->nullable(); // URLs para fotos, vídeos, áudios
            $table->json('damage_locations')->nullable(); // Localizações dos danos no veículo
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->boolean('customer_notified')->default(false);
            $table->boolean('customer_acknowledged')->default(false);
            $table->dateTime('customer_acknowledgment_time')->nullable();
            $table->text('customer_comments')->nullable();
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
            $table->text('resolution_notes')->nullable();
            $table->dateTime('resolution_time')->nullable();
            $table->string('resolution_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_incidents');
    }
};
