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
        Schema::create('charging_stations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parking_lot_id')->constrained();
            $table->string('identifier')->comment('Identificador único da estação de carregamento');
            $table->string('location')->comment('Localização dentro do estacionamento');
            $table->enum('connector_type', ['type1', 'type2', 'chademo', 'ccs', 'tesla', 'gbdt'])->comment('Tipo de conector');
            $table->decimal('power_output', 10, 2)->comment('Potência em kW');
            $table->decimal('charging_rate', 10, 2)->comment('Taxa de cobrança por kWh (R$)');
            $table->decimal('charging_hourly_rate', 10, 2)->nullable()->comment('Taxa de cobrança adicional por hora (R$)');
            $table->integer('avg_charging_time')->comment('Tempo médio de carregamento completo em minutos');
            $table->boolean('is_available')->default(true)->comment('Disponibilidade atual');
            $table->boolean('is_operational')->default(true)->comment('Estado operacional');
            $table->timestamp('last_maintenance_at')->nullable();
            $table->timestamp('next_maintenance_at')->nullable();
            $table->text('maintenance_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('charging_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('charging_station_id')->constrained();
            $table->foreignId('parking_session_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->datetime('started_at');
            $table->datetime('ended_at')->nullable();
            $table->decimal('initial_battery', 5, 2)->nullable()->comment('Percentual inicial da bateria');
            $table->decimal('final_battery', 5, 2)->nullable()->comment('Percentual final da bateria');
            $table->decimal('energy_consumed', 10, 2)->nullable()->comment('Energia consumida em kWh');
            $table->decimal('charging_fee', 10, 2)->nullable()->comment('Valor cobrado pelo carregamento');
            $table->string('payment_status')->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('charging_sessions');
        Schema::dropIfExists('charging_stations');
    }
};
