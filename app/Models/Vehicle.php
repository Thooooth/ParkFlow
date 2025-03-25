<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Vehicle extends Model
{
    protected $fillable = [
        'plate',
        'model',
        'color',
        'user_id',
        'vehicle_type', // car, motorcycle, bus, truck, van, etc.
        'size', // 1-small, 2-normal, 3-large, 4-extra_large, 5-special
        'length', // comprimento em metros
        'width', // largura em metros
        'height', // altura em metros
        'weight', // peso em kg
        'is_electric', // se é veículo elétrico
        'is_gnv', // se possui GNV
        'is_disabled_adapted', // se é adaptado para PCD
        'connector_type', // tipo de conector para veículos elétricos
        'license_type', // tipo de CNH necessária para dirigir
        'axles', // número de eixos
        'special_requirements', // requisitos especiais (texto)
        'preferred_zone', // zona preferencial (se mensalista)
        'preferred_floor', // andar preferencial (se mensalista)
    ];

    protected $casts = [
        'size' => 'integer',
        'length' => 'float',
        'width' => 'float',
        'height' => 'float',
        'weight' => 'float',
        'is_electric' => 'boolean',
        'is_gnv' => 'boolean',
        'is_disabled_adapted' => 'boolean',
        'axles' => 'integer',
    ];

    /**
     * Retorna o tipo do veículo em formato legível
     */
    public function getVehicleTypeNameAttribute(): string
    {
        $types = [
            'car' => 'Automóvel',
            'motorcycle' => 'Motocicleta',
            'bus' => 'Ônibus',
            'minibus' => 'Micro-ônibus',
            'truck' => 'Caminhão',
            'van' => 'Van/Utilitário',
            'suv' => 'SUV',
            'pickup' => 'Picape',
            'other' => 'Outro',
        ];

        return $types[$this->vehicle_type] ?? $this->vehicle_type;
    }

    /**
     * Retorna o tamanho do veículo em formato legível
     */
    public function getSizeNameAttribute(): string
    {
        $sizes = [
            1 => 'Pequeno',
            2 => 'Normal',
            3 => 'Grande',
            4 => 'Extra grande',
            5 => 'Especial',
        ];

        return $sizes[$this->size] ?? 'Não especificado';
    }

    /**
     * Retorna o número de vagas que o veículo ocupa
     */
    public function getSpotCountAttribute(): int
    {
        // Para veículos normais, ocupa 1 vaga
        if ($this->size <= 2) {
            return 1;
        }

        // Para veículos grandes, ocupa 2 vagas
        if ($this->size == 3) {
            return 2;
        }

        // Para veículos extra-grandes, ocupa 3 vagas
        if ($this->size == 4) {
            return 3;
        }

        // Para veículos especiais (ônibus, caminhões longos)
        return $this->size == 5 ? 4 : 1;
    }

    /**
     * Determina se o veículo precisa de uma vaga especial
     */
    public function needsSpecialSpot(): bool
    {
        return $this->is_disabled_adapted ||
               $this->is_electric ||
               $this->size >= 3 ||
               $this->height > 2.10; // altura padrão para estacionamentos cobertos
    }

    /**
     * Retorna os detalhes do veículo para reserva ou alocação de vaga
     */
    public function getVehicleDetailsForSpotAllocation(): array
    {
        return [
            'size' => $this->size,
            'is_electric' => $this->is_electric,
            'is_disabled' => $this->is_disabled_adapted,
            'height' => $this->height,
            'width' => $this->width,
            'length' => $this->length,
            'preferred_zone' => $this->preferred_zone,
            'preferred_floor' => $this->preferred_floor,
            'special_requirements' => $this->special_requirements,
        ];
    }

    /**
     * Calcula o número de vagas necessárias com base nas dimensões reais
     */
    public function calculateRequiredSpots(ParkingLot $parkingLot): int
    {
        // Se não tivermos as dimensões reais, usamos o atributo spot_count
        if (!$this->length || !$this->width) {
            return $this->spot_count;
        }

        // Obtém as dimensões padrão de uma vaga no estacionamento
        $standardSpotLength = $parkingLot->standard_spot_length ?? 5.0; // metros
        $standardSpotWidth = $parkingLot->standard_spot_width ?? 2.5; // metros

        // Calcula o número de vagas necessárias com base no comprimento
        $lengthMultiplier = ceil($this->length / $standardSpotLength);

        // Calcula o número de vagas necessárias com base na largura
        $widthMultiplier = ceil($this->width / $standardSpotWidth);

        // O número total de vagas é o produto dos multiplicadores
        // Por exemplo, um veículo que precisa de 2 vagas em comprimento e 1 em largura = 2 vagas
        // Um veículo que precisa de 2 vagas em comprimento e 2 em largura = 4 vagas
        return $lengthMultiplier * $widthMultiplier;
    }

    /**
     * Verifica se o veículo requer vagas adjacentes (devido ao tamanho)
     */
    public function requiresAdjacentSpots(): bool
    {
        return $this->spot_count > 1 || $this->size >= 3;
    }

    /**
     * Retorna o tipo de layout de vagas necessário para este veículo
     */
    public function getRequiredSpotLayout(): string
    {
        // Se precisar de apenas uma vaga
        if ($this->spot_count <= 1) {
            return 'single';
        }

        // Se as dimensões indicarem que precisa de vagas em linha (como um ônibus)
        if ($this->length > ($this->width * 2)) {
            return 'linear';
        }

        // Se as dimensões indicarem que precisa de vagas lado a lado (veículo largo)
        if ($this->width > ($this->length / 2)) {
            return 'side_by_side';
        }

        // Para veículos que precisam de um bloco de vagas (ex: caminhões grandes)
        if ($this->spot_count >= 4) {
            return 'block';
        }

        // Padrão para veículos grandes que não se encaixam nas categorias acima
        return 'linear';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parkingSessions(): HasMany
    {
        return $this->hasMany(ParkingSession::class);
    }

    public function monthlySubscriptions(): HasMany
    {
        return $this->hasMany(MonthlySubscriber::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(ParkingReservation::class);
    }
}
