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
        Schema::create('parking_spots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parking_lot_id')->constrained()->onDelete('cascade');
            $table->string('spot_identifier', 20); // Ex: A1, B5, 101, etc.
            $table->string('zone', 20)->nullable(); // Setor/Área, ex: Azul, Térreo, etc.
            $table->string('floor', 10)->nullable(); // Andar, se aplicável
            $table->boolean('is_reserved_for_disabled')->default(false); // Vaga PCD
            $table->boolean('is_reserved_for_electric')->default(false); // Vaga p/ veículos elétricos
            $table->integer('size')->default(1); // Tamanho da vaga: 1-Normal, 2-Grande, 3-Extra Grande
            $table->string('status', 20)->default('available'); // available, occupied, maintenance, reserved
            $table->foreignId('current_session_id')->nullable()->constrained('parking_sessions')->nullOnDelete();
            $table->foreignId('current_reservation_id')->nullable()->constrained('parking_reservations')->nullOnDelete();
            $table->text('notes')->nullable(); // Observações, instruções especiais, etc.
            $table->timestamp('occupied_since')->nullable(); // Quando a vaga foi ocupada
            $table->timestamps();

            // Índices para melhorar performance
            $table->index(['parking_lot_id', 'status']);
            $table->index(['parking_lot_id', 'zone', 'floor']);
            $table->unique(['parking_lot_id', 'spot_identifier']); // Identificador deve ser único no estacionamento
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parking_spots');
    }
};
