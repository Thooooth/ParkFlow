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
        'monthly_rate',
        'opening_time',
        'closing_time',
        'company_id',
    ];

    protected $casts = [
        'opening_time' => 'datetime:H:i',
        'closing_time' => 'datetime:H:i',
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
}
