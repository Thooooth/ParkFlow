<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Enums\StatusParkingSessionsEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ParkingSession extends Model
{
    protected $fillable = [
        'parking_lot_id',
        'vehicle_id',
        'user_id',
        'check_in',
        'check_out',
        'total_amount',
        'status',
        'reservation_id',
        'is_late_checkout',
        'late_fee',
        'parking_spot_id',
    ];

    protected $casts = [
        'status' => StatusParkingSessionsEnum::class,
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'total_amount' => 'float',
        'is_late_checkout' => 'boolean',
        'late_fee' => 'float',
    ];

    /**
     * Registra a saída do veículo e calcula o valor a ser cobrado.
     */
    public function checkOut(): void
    {
        $this->check_out = now();
        $parkingLot = $this->parkingLot;
        $reservation = $this->reservation;

        // Calcula o valor a ser cobrado, considerando a reserva se existir
        $this->total_amount = $parkingLot->calculateParkingFee(
            $this->check_in,
            $this->check_out,
            $reservation
        );

        // Verifica se houve atraso na saída em relação à reserva
        if ($reservation && $this->check_out > $reservation->end_time) {
            $this->is_late_checkout = true;

            // Calcula a taxa da reserva (já paga ou a ser paga)
            $reservationFee = $parkingLot->calculateStandardParkingFee(
                $reservation->start_time,
                $reservation->end_time
            );

            // Calcula a taxa adicional pelo tempo excedido
            $this->late_fee = $this->total_amount - $reservationFee;
        }

        $this->status = StatusParkingSessionsEnum::COMPLETED;
        $this->save();

        // Libera a vaga ocupada
        if ($this->parkingSpot) {
            $this->parkingSpot->release();
        }

        // Atualiza a contagem de vagas disponíveis
        $parkingLot->checkOutVehicle();

        // Atualiza a reserva, se existir
        if ($reservation) {
            $reservation->checkOut();
        }
    }

    /**
     * Cria uma nova sessão de estacionamento.
     */
    public static function createSession(
        int $parkingLotId,
        int $vehicleId,
        int $userId,
        ?int $reservationId = null,
        ?int $parkingSpotId = null,
        array $vehicleDetails = []
    ): self {
        $parkingLot = ParkingLot::findOrFail($parkingLotId);

        // Verifica se há vagas disponíveis
        if (!$parkingLot->checkInVehicle()) {
            throw new \Exception('Não há vagas disponíveis no momento.');
        }

        // Verifica se foi especificada uma vaga ou se precisa encontrar uma
        if (!$parkingSpotId) {
            // Recupera informações do veículo se necessário
            $vehicle = null;
            $isDisabled = false;
            $isElectric = false;

            if (!empty($vehicleId)) {
                $vehicle = Vehicle::find($vehicleId);
                if ($vehicle) {
                    $isDisabled = $vehicle->is_disabled_adapted ?? false;
                    $isElectric = $vehicle->is_electric ?? false;
                }
            }

            // Busca uma vaga disponível adequada
            $parkingSpot = ParkingSpot::findAvailableSpot(
                $parkingLotId,
                $vehicleDetails,
                $isDisabled,
                $isElectric
            );

            if (!$parkingSpot) {
                throw new \Exception('Não foi possível encontrar uma vaga adequada disponível.');
            }

            $parkingSpotId = $parkingSpot->id;
        }

        // Cria a sessão
        $session = self::create([
            'parking_lot_id' => $parkingLotId,
            'vehicle_id' => $vehicleId,
            'user_id' => $userId,
            'check_in' => now(),
            'status' => StatusParkingSessionsEnum::ACTIVE,
            'reservation_id' => $reservationId,
            'parking_spot_id' => $parkingSpotId,
        ]);

        // Ocupa a vaga especificada
        $parkingSpot = ParkingSpot::findOrFail($parkingSpotId);
        $parkingSpot->occupy($session->id);

        // Se houver reserva, atualiza seu status
        if ($reservationId) {
            $reservation = ParkingReservation::findOrFail($reservationId);
            $reservation->checkIn($session->id);
        }

        return $session;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parkingLot(): BelongsTo
    {
        return $this->belongsTo(ParkingLot::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(VehicleIncident::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(ParkingReservation::class);
    }

    public function ticketValidation(): HasOne
    {
        return $this->hasOne(ParkingTicketValidation::class);
    }

    public function chargingSession(): HasOne
    {
        return $this->hasOne(ChargingSession::class);
    }

    public function parkingSpot(): BelongsTo
    {
        return $this->belongsTo(ParkingSpot::class);
    }
}
