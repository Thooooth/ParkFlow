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
        Schema::create('vehicle_handovers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parking_lot_id')->constrained();
            $table->foreignId('valet_request_id')->nullable()->constrained();
            $table->foreignId('parking_session_id')->constrained();
            $table->foreignId('operator_id')->nullable(); // Manobrista que entregou o veículo
            $table->dateTime('handover_time');
            $table->boolean('vehicle_condition_ok')->default(true);
            $table->text('issues_reported')->nullable();
            $table->json('damage_photos')->nullable(); // Array de URLs de fotos
            $table->string('signature')->nullable(); // URL da assinatura digital
            $table->string('customer_name')->nullable();
            $table->string('customer_document')->nullable(); // CPF ou outro documento
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->enum('handover_type', ['check_in', 'check_out']); // Entrada ou saída do veículo
            $table->boolean('customer_confirmed')->default(false);
            $table->dateTime('confirmation_time')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_handovers');
    }
};
