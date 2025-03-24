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
        // Adicionar campos relacionados a mensalistas na tabela de estacionamentos
        Schema::table('parking_lots', function (Blueprint $table): void {
            $table->decimal('additional_hour_rate', 8, 2)->after('hourly_rate')->default(0);
            $table->decimal('daily_rate', 8, 2)->after('additional_hour_rate')->default(0);
            $table->decimal('monthly_rate', 8, 2)->after('daily_rate')->default(0);
            $table->time('opening_time')->after('monthly_rate')->default('08:00');
            $table->time('closing_time')->after('opening_time')->default('18:00');
            $table->integer('monthly_spots')->after('total_spots')->default(0);
            $table->integer('available_monthly_spots')->after('monthly_spots')->default(0);
        });

        // Criar tabela de mensalistas
        Schema::create('monthly_subscribers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parking_lot_id')->constrained();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->string('document_number')->nullable(); // CPF ou CNPJ
            $table->decimal('monthly_fee', 8, 2);
            $table->date('start_date');
            $table->date('end_date');
            $table->date('next_payment_date');
            $table->enum('payment_status', ['paid', 'pending', 'overdue'])->default('pending');
            $table->string('vehicle_plate');
            $table->string('vehicle_model');
            $table->string('vehicle_color');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Criar tabela de pagamentos de mensalistas
        Schema::create('monthly_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('monthly_subscriber_id')->constrained();
            $table->foreignId('parking_lot_id')->constrained();
            $table->decimal('amount', 8, 2);
            $table->enum('payment_method', ['credit_card', 'debit_card', 'bank_transfer', 'cash', 'pix']);
            $table->string('reference_period'); // Ex: "2024-03" para Março de 2024
            $table->date('payment_date');
            $table->date('due_date');
            $table->enum('status', ['paid', 'pending', 'overdue', 'cancelled'])->default('pending');
            $table->string('invoice_number')->nullable();
            $table->string('transaction_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_payments');
        Schema::dropIfExists('monthly_subscribers');

        Schema::table('parking_lots', function (Blueprint $table): void {
            $table->dropColumn([
                'additional_hour_rate',
                'daily_rate',
                'monthly_rate',
                'opening_time',
                'closing_time',
                'monthly_spots',
                'available_monthly_spots',
            ]);
        });
    }
};
