<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

final class ParkingSpot extends Model
{
    protected $fillable = [
        'parking_lot_id',
        'spot_identifier',
        'zone',
        'floor',
        'is_reserved_for_disabled',
        'is_reserved_for_electric',
        'size',
        'status',
        'current_session_id',
        'current_reservation_id',
        'notes',
        'occupied_since',
    ];

    protected $casts = [
        'is_reserved_for_disabled' => 'boolean',
        'is_reserved_for_electric' => 'boolean',
        'size' => 'integer',
        'occupied_since' => 'datetime',
    ];

    /**
     * Retorna o relacionamento com o estacionamento.
     */
    public function parkingLot(): BelongsTo
    {
        return $this->belongsTo(ParkingLot::class);
    }

    /**
     * Retorna o relacionamento com a sessão atual.
     */
    public function currentSession(): BelongsTo
    {
        return $this->belongsTo(ParkingSession::class, 'current_session_id');
    }

    /**
     * Retorna o relacionamento com a reserva atual.
     */
    public function currentReservation(): BelongsTo
    {
        return $this->belongsTo(ParkingReservation::class, 'current_reservation_id');
    }

    /**
     * Ocupa esta vaga com um veículo e associa com uma sessão de estacionamento.
     */
    public function occupy(int $sessionId): bool
    {
        if ($this->status !== 'available' && $this->status !== 'reserved') {
            return false;
        }

        return DB::transaction(function () use ($sessionId) {
            $this->status = 'occupied';
            $this->current_session_id = $sessionId;
            $this->occupied_since = now();

            // Se havia uma reserva para esta vaga específica, mantém essa informação
            // ou deixa null se não havia reserva específica

            $session = ParkingSession::findOrFail($sessionId);
            $session->parking_spot_id = $this->id;
            $session->save();

            return $this->save();
        });
    }

    /**
     * Libera esta vaga quando um veículo sai.
     */
    public function release(): bool
    {
        if ($this->status !== 'occupied') {
            return false;
        }

        $this->status = 'available';
        $this->current_session_id = null;
        $this->occupied_since = null;

        // Se havia uma reserva específica para esta vaga, pode remover também
        // ou manter para análise posterior
        // $this->current_reservation_id = null;

        return $this->save();
    }

    /**
     * Reserva esta vaga para uma reserva específica.
     */
    public function reserve(int $reservationId): bool
    {
        if ($this->status !== 'available') {
            return false;
        }

        $this->status = 'reserved';
        $this->current_reservation_id = $reservationId;
        return $this->save();
    }

    /**
     * Marca a vaga como em manutenção.
     */
    public function setMaintenance(string $notes = null): bool
    {
        if ($this->status === 'occupied') {
            return false;
        }

        $this->status = 'maintenance';

        if ($notes) {
            $this->notes = $notes;
        }

        return $this->save();
    }

    /**
     * Retorna a vaga para disponível após manutenção.
     */
    public function setAvailable(): bool
    {
        if ($this->status === 'occupied') {
            return false;
        }

        $this->status = 'available';
        return $this->save();
    }

    /**
     * Recupera uma vaga disponível adequada para o veículo especificado.
     */
    public static function findAvailableSpot(
        int $parkingLotId,
        array $vehicleDetails = [],
        bool $isDisabled = false,
        bool $isElectric = false
    ): ?self {
        $query = self::where('parking_lot_id', $parkingLotId)
                    ->where('status', 'available');

        // Se o cliente é PCD, busca vagas reservadas para PCD ou vagas normais
        if ($isDisabled) {
            $query->where('is_reserved_for_disabled', true);
        }

        // Se é veículo elétrico, busca vagas com carregamento
        if ($isElectric) {
            $query->where('is_reserved_for_electric', true);
        }

        // Considerando tamanho do veículo, se informado
        if (!empty($vehicleDetails['size'])) {
            $vehicleSize = (int) $vehicleDetails['size'];
            $query->where('size', '>=', $vehicleSize);
        }

        // Busca primeiro no piso/setor preferencial, se informado
        if (!empty($vehicleDetails['preferred_zone'])) {
            $spot = (clone $query)
                ->where('zone', $vehicleDetails['preferred_zone'])
                ->first();

            if ($spot) {
                return $spot;
            }
        }

        // Busca no piso preferencial, se informado
        if (!empty($vehicleDetails['preferred_floor'])) {
            $spot = (clone $query)
                ->where('floor', $vehicleDetails['preferred_floor'])
                ->first();

            if ($spot) {
                return $spot;
            }
        }

        // Se não encontrar com as preferências, retorna qualquer vaga disponível adequada
        return $query->first();
    }

    /**
     * Retorna um array com a descrição legível da localização da vaga.
     */
    public function getLocationDetails(): array
    {
        $details = [
            'identifier' => $this->spot_identifier,
        ];

        if ($this->zone) {
            $details['zone'] = $this->zone;
        }

        if ($this->floor) {
            $details['floor'] = $this->floor;
        }

        if ($this->is_reserved_for_disabled) {
            $details['accessibility'] = 'Vaga acessível (PCD)';
        }

        if ($this->is_reserved_for_electric) {
            $details['special'] = 'Vaga com carregador elétrico';
        }

        return $details;
    }

    /**
     * Retorna uma string com a localização completa da vaga.
     */
    public function getFullLocationAttribute(): string
    {
        $parts = [];

        if ($this->floor) {
            $parts[] = "Piso {$this->floor}";
        }

        if ($this->zone) {
            $parts[] = "Setor {$this->zone}";
        }

        $parts[] = "Vaga {$this->spot_identifier}";

        return implode(', ', $parts);
    }
}
