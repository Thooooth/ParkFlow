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
        Schema::create('corporate_partners', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('cnpj')->unique();
            $table->string('address')->nullable();
            $table->string('contact_person');
            $table->string('contact_email');
            $table->string('contact_phone');
            $table->enum('status', ['active', 'inactive', 'pending'])->default('pending');
            $table->date('contract_start_date');
            $table->date('contract_end_date')->nullable();
            $table->text('contract_terms')->nullable();
            $table->decimal('discount_percentage', 5, 2)->default(0)->comment('Desconto aplicado aos tickets');
            $table->integer('monthly_ticket_limit')->nullable()->comment('Limite mensal de tickets');
            $table->string('api_key')->nullable()->unique();
            $table->string('validation_method')->default('portal')->comment('portal, api, qrcode');
            $table->json('custom_settings')->nullable();
            $table->timestamps();
        });

        Schema::create('parking_ticket_validations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('corporate_partner_id')->constrained();
            $table->foreignId('parking_session_id')->constrained();
            $table->foreignId('validated_by')->nullable()->constrained('users');
            $table->dateTime('validation_date');
            $table->integer('validated_minutes')->comment('Minutos de estacionamento validados');
            $table->decimal('original_amount', 10, 2);
            $table->decimal('discounted_amount', 10, 2);
            $table->string('validation_code')->unique();
            $table->string('customer_reference')->nullable()->comment('Referência do cliente, como número de compra');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('corporate_invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('corporate_partner_id')->constrained();
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->decimal('total_amount', 10, 2);
            $table->integer('total_validations');
            $table->integer('total_minutes_validated');
            $table->enum('status', ['pending', 'paid', 'overdue', 'canceled'])->default('pending');
            $table->date('payment_date')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('corporate_invoice_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('corporate_invoice_id')->constrained();
            $table->foreignId('parking_ticket_validation_id')->constrained();
            $table->decimal('amount', 10, 2);
            $table->string('description');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('corporate_invoice_items');
        Schema::dropIfExists('corporate_invoices');
        Schema::dropIfExists('parking_ticket_validations');
        Schema::dropIfExists('corporate_partners');
    }
};
