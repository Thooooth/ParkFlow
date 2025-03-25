<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ChargingSession extends Model
{
    protected $fillable = [
        'charging_station_id',
        'parking_session_id',
        'user_id',
        'started_at',
        'ended_at',
        'initial_battery',
        'final_battery',
        'energy_consumed',
        'charging_fee',
        'payment_status',
        'payment_method',
        'payment_reference',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'initial_battery' => 'float',
        'final_battery' => 'float',
        'energy_consumed' => 'float',
        'charging_fee' => 'float',
    ];

    /**
     * Finaliza a sessão de carregamento.
     */
    public function endSession(float $finalBattery, float $energyConsumed): void
    {
        $this->ended_at = now();
        $this->final_battery = $finalBattery;
        $this->energy_consumed = $energyConsumed;

        // Calcula o tempo decorrido em minutos
        $minutes = $this->started_at->diffInMinutes($this->ended_at);

        // Obtém a estação de carregamento
        $station = $this->chargingStation;

        // Calcula o valor a ser cobrado
        $this->charging_fee = $station->calculateEstimatedChargingCost($energyConsumed, $minutes);

        $this->save();

        // Marca a estação como disponível novamente
        $station->markAsAvailable();
    }

    /**
     * Registra o pagamento da sessão de carregamento.
     */
    public function markAsPaid(string $paymentMethod, string $reference): void
    {
        $this->payment_status = 'paid';
        $this->payment_method = $paymentMethod;
        $this->payment_reference = $reference;
        $this->save();
    }

    /**
     * Calcula a duração da sessão em minutos.
     */
    public function getDurationInMinutes(): int
    {
        $endTime = $this->ended_at ?? now();
        return $this->started_at->diffInMinutes($endTime);
    }

    /**
     * Calcula a porcentagem de bateria carregada.
     */
    public function getBatteryPercentageCharged(): ?float
    {
        if ($this->initial_battery === null || $this->final_battery === null) {
            return null;
        }

        return $this->final_battery - $this->initial_battery;
    }

    public function chargingStation(): BelongsTo
    {
        return $this->belongsTo(ChargingStation::class);
    }

    public function parkingSession(): BelongsTo
    {
        return $this->belongsTo(ParkingSession::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
