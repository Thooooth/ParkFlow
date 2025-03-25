<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ChargingStation extends Model
{
    protected $fillable = [
        'parking_lot_id',
        'identifier',
        'location',
        'connector_type',
        'power_output',
        'charging_rate',
        'charging_hourly_rate',
        'avg_charging_time',
        'is_available',
        'is_operational',
        'last_maintenance_at',
        'next_maintenance_at',
        'maintenance_notes',
    ];

    protected $casts = [
        'power_output' => 'float',
        'charging_rate' => 'float',
        'charging_hourly_rate' => 'float',
        'is_available' => 'boolean',
        'is_operational' => 'boolean',
        'last_maintenance_at' => 'datetime',
        'next_maintenance_at' => 'datetime',
    ];

    /**
     * Encontra estações de carregamento disponíveis em um estacionamento.
     */
    public static function findAvailable(int $parkingLotId, ?string $connectorType = null)
    {
        $query = self::where('parking_lot_id', $parkingLotId)
            ->where('is_available', true)
            ->where('is_operational', true);

        if ($connectorType) {
            $query->where('connector_type', $connectorType);
        }

        return $query->get();
    }

    /**
     * Inicia uma sessão de carregamento.
     */
    public function startChargingSession(int $parkingSessionId, int $userId, ?float $initialBattery = null): ChargingSession
    {
        // Marca a estação como indisponível
        $this->is_available = false;
        $this->save();

        // Cria uma nova sessão de carregamento
        return ChargingSession::create([
            'charging_station_id' => $this->id,
            'parking_session_id' => $parkingSessionId,
            'user_id' => $userId,
            'started_at' => now(),
            'initial_battery' => $initialBattery,
        ]);
    }

    /**
     * Registra manutenção realizada.
     */
    public function recordMaintenance(string $notes, int $daysUntilNextMaintenance = 90): void
    {
        $this->last_maintenance_at = now();
        $this->next_maintenance_at = now()->addDays($daysUntilNextMaintenance);
        $this->maintenance_notes = $notes;
        $this->is_operational = true;
        $this->save();
    }

    /**
     * Marca a estação como fora de operação.
     */
    public function markAsNonOperational(string $reason): void
    {
        $this->is_operational = false;
        $this->is_available = false;
        $this->maintenance_notes = $this->maintenance_notes
            ? $this->maintenance_notes . "\n" . now()->format('d/m/Y H:i') . " - " . $reason
            : now()->format('d/m/Y H:i') . " - " . $reason;
        $this->save();
    }

    /**
     * Marca a estação como disponível.
     */
    public function markAsAvailable(): void
    {
        $this->is_available = true;
        $this->save();
    }

    /**
     * Calcula o custo estimado de carregamento.
     */
    public function calculateEstimatedChargingCost(float $kwh, int $minutes): float
    {
        $energyCost = $kwh * $this->charging_rate;

        if ($this->charging_hourly_rate) {
            $hourlyFee = ($minutes / 60) * $this->charging_hourly_rate;
            return $energyCost + $hourlyFee;
        }

        return $energyCost;
    }

    public function parkingLot(): BelongsTo
    {
        return $this->belongsTo(ParkingLot::class);
    }

    public function chargingSessions(): HasMany
    {
        return $this->hasMany(ChargingSession::class);
    }

    public function activeSession()
    {
        return $this->chargingSessions()
            ->whereNull('ended_at')
            ->first();
    }
}
