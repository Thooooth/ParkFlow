<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ParkingLot extends Model
{
    protected $fillable = [
        'name',
        'address',
        'total_spots',
        'monthly_spots',
        'available_monthly_spots',
        'available_spots',
        'hourly_rate',
        'additional_hour_rate',
        'daily_rate',
        'daily_period',
        'monthly_rate',
        'opening_time',
        'closing_time',
        'company_id',
    ];

    protected $casts = [
        'opening_time' => 'datetime:H:i',
        'closing_time' => 'datetime:H:i',
        'daily_period' => 'integer',
    ];

    /**
     * Calcula o total de vagas disponíveis para o público geral.
     * As vagas não ocupadas por mensalistas são adicionadas às vagas regulares.
     */
    public function getTotalAvailableSpotsAttribute(): int
    {
        $regularSpots = $this->total_spots;
        $unusedMonthlySpots = $this->available_monthly_spots;

        return $regularSpots + $unusedMonthlySpots;
    }

    /**
     * Atualiza as vagas disponíveis para mensalistas baseado na contagem atual.
     */
    public function updateAvailableMonthlySpots(): void
    {
        $usedMonthlySpots = $this->monthlySubscribers()->where('is_active', true)->count();
        $this->available_monthly_spots = $this->monthly_spots - $usedMonthlySpots;
        $this->save();
    }

    public function parkingSessions(): HasMany
    {
        return $this->hasMany(ParkingSession::class);
    }

    public function monthlySubscribers(): HasMany
    {
        return $this->hasMany(MonthlySubscriber::class);
    }

    public function valetRequests(): HasMany
    {
        return $this->hasMany(ValetRequest::class);
    }

    public function valetOperators(): HasMany
    {
        return $this->hasMany(ValetOperator::class);
    }

    public function vehicleHandovers(): HasMany
    {
        return $this->hasMany(VehicleHandover::class);
    }

    public function vehicleIncidents(): HasMany
    {
        return $this->hasMany(VehicleIncident::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Registra a entrada de um veículo e atualiza o contador de vagas disponíveis.
     */
    public function checkInVehicle(): bool
    {
        if ($this->available_spots > 0) {
            $this->available_spots -= 1;
            $this->save();
            return true;
        }

        return false; // Não há vagas disponíveis
    }

    /**
     * Registra a saída de um veículo e atualiza o contador de vagas disponíveis.
     */
    public function checkOutVehicle(): void
    {
        $this->available_spots += 1;
        $this->save();
    }

    /**
     * Calcula o valor a ser cobrado com base no tempo de permanência.
     *
     * @param \DateTimeInterface $checkIn Data e hora de entrada
     * @param \DateTimeInterface $checkOut Data e hora de saída
     * @return float Valor total a ser cobrado
     */
    public function calculateParkingFee(\DateTimeInterface $checkIn, \DateTimeInterface $checkOut): float
    {
        // Calcula a duração em horas (arredondando para cima)
        $duration = ceil($checkOut->getTimestamp() - $checkIn->getTimestamp()) / 3600;

        // Calcula o número de períodos diários completos
        $dailyPeriod = $this->daily_period ?: 24; // Se não definido, assume 24 horas
        $fullDays = floor($duration / $dailyPeriod);

        // Calcula as horas restantes após os períodos diários completos
        $remainingHours = $duration - ($fullDays * $dailyPeriod);

        // Valor base para os períodos diários completos
        $fee = $fullDays * $this->daily_rate;

        // Adiciona o valor para as horas restantes
        if ($remainingHours > 0) {
            // Primeira hora
            $remainingFee = $this->hourly_rate;

            // Horas adicionais
            if ($remainingHours > 1) {
                $remainingFee += min($this->additional_hour_rate * ($remainingHours - 1),
                                    $this->daily_rate - $this->hourly_rate);
            }

            // Limite o valor das horas restantes ao valor da diária
            $remainingFee = min($remainingFee, $this->daily_rate);

            $fee += $remainingFee;
        }

        return $fee;
    }
}
