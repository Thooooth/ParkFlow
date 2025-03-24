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
        Schema::create('valet_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parking_lot_id')->constrained();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->foreignId('parking_session_id')->nullable()->constrained();
            $table->string('plate_number');
            $table->string('requester_name')->nullable();
            $table->string('requester_type')->default('client'); // client, company, etc.
            $table->string('requester_reference')->nullable(); // ex: número do quarto de hospital
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'canceled'])->default('pending');
            $table->dateTime('requested_at');
            $table->dateTime('processing_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->foreignId('assigned_to')->nullable(); // ID do operador de valet
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('valet_requests');
    }
};