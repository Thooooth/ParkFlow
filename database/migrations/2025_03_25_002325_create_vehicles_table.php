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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('plate');
            $table->string('model');
            $table->string('color');
            $table->foreignId('user_id')->constrained();
            $table->string('vehicle_type')->default('car'); // car, motorcycle, bus, truck, van, etc.
            $table->tinyInteger('size')->default(2); // 1-small, 2-normal, 3-large, 4-extra_large, 5-special
            $table->decimal('length', 5, 2)->nullable(); // comprimento em metros
            $table->decimal('width', 4, 2)->nullable(); // largura em metros
            $table->decimal('height', 4, 2)->nullable(); // altura em metros
            $table->decimal('weight', 8, 2)->nullable(); // peso em kg
            $table->boolean('is_electric')->default(false);
            $table->boolean('is_gnv')->default(false);
            $table->boolean('is_disabled_adapted')->default(false);
            $table->string('connector_type')->nullable(); // tipo de conector para veículos elétricos
            $table->string('license_type')->nullable(); // tipo de CNH necessária (A, B, C, D, E)
            $table->tinyInteger('axles')->nullable(); // número de eixos
            $table->text('special_requirements')->nullable(); // requisitos especiais
            $table->string('preferred_zone')->nullable(); // zona preferencial
            $table->string('preferred_floor')->nullable(); // andar preferencial
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
