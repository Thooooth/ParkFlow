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
            // Remove colunas que agora são referenciadas do veículo
            $table->dropColumn(['plate', 'model', 'color']);

            // Adiciona a referência ao veículo
            $table->foreignId('vehicle_id')->after('user_id')->constrained();

            // Adiciona a referência ao estacionamento
            $table->foreignId('parking_lot_id')->after('id')->constrained();

            // Adiciona campos de data e hora de entrada/saída
            $table->dateTime('check_in')->after('vehicle_id');
            $table->dateTime('check_out')->after('check_in')->nullable();

            // Adiciona campo de valor total
            $table->decimal('total_amount', 10, 2)->after('check_out')->nullable();

            // Adiciona campo de status
            $table->string('status')->after('total_amount')->default('active');

            // Adiciona campo para vinculação com reserva
            $table->foreignId('reservation_id')->after('status')->nullable()
                ->constrained('parking_reservations')->nullOnDelete();

            // Adiciona campo para vinculação com vaga específica
            $table->foreignId('parking_spot_id')->after('reservation_id')->nullable()
                ->constrained('parking_spots')->nullOnDelete();

            // Adiciona campos para controle de cobrança adicional
            $table->boolean('is_late_checkout')->after('parking_spot_id')->default(false);
            $table->decimal('late_fee', 10, 2)->after('is_late_checkout')->default(0);

            // Adiciona informações de tarifação específica
            $table->decimal('base_rate', 10, 2)->after('late_fee')->nullable()->comment('Tarifa base aplicada');
            $table->decimal('vehicle_size_surcharge', 10, 2)->after('base_rate')->default(0)->comment('Adicional por tamanho do veículo');
            $table->decimal('special_spot_surcharge', 10, 2)->after('vehicle_size_surcharge')->default(0)->comment('Adicional por tipo especial de vaga');
            $table->decimal('discount', 10, 2)->after('special_spot_surcharge')->default(0)->comment('Desconto aplicado');
            $table->string('discount_reason')->after('discount')->nullable()->comment('Motivo do desconto');

            // Adiciona campos para informações adicionais
            $table->json('notes')->after('discount_reason')->nullable()->comment('Notas adicionais');
            $table->json('metadata')->after('notes')->nullable()->comment('Metadados adicionais');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parking_sessions', function (Blueprint $table) {
            // Remover as colunas adicionadas
            $table->dropForeign(['vehicle_id']);
            $table->dropForeign(['parking_lot_id']);
            $table->dropForeign(['reservation_id']);
            $table->dropForeign(['parking_spot_id']);

            $table->dropColumn([
                'vehicle_id',
                'parking_lot_id',
                'check_in',
                'check_out',
                'total_amount',
                'status',
                'reservation_id',
                'parking_spot_id',
                'is_late_checkout',
                'late_fee',
                'base_rate',
                'vehicle_size_surcharge',
                'special_spot_surcharge',
                'discount',
                'discount_reason',
                'notes',
                'metadata'
            ]);

            // Recria as colunas originais
            $table->string('plate');
            $table->string('model');
            $table->string('color');
        });
    }
};
