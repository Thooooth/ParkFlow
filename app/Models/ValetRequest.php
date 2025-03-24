<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ValetRequest extends Model
{
    protected $fillable = [
        'parking_lot_id',
        'user_id',
        'parking_session_id',
        'plate_number',
        'requester_name',
        'requester_type',
        'requester_reference',
        'notes',
        'status',
        'requested_at',
        'processing_at',
        'completed_at',
        'assigned_to',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'processing_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Obter todos os pedidos pendentes para um estacionamento.
     */
    public static function getPendingRequests(int $parkingLotId)
    {
        return self::where('parking_lot_id', $parkingLotId)
            ->where('status', 'pending')
            ->orderBy('requested_at')
            ->get();
    }

    /**
     * Marcar solicitação como em processamento.
     */
    public function markAsProcessing(int $operatorId): void
    {
        $this->status = 'processing';
        $this->processing_at = now();
        $this->assigned_to = $operatorId;
        $this->save();
    }

    /**
     * Marcar solicitação como concluída.
     */
    public function markAsCompleted(): void
    {
        $this->status = 'completed';
        $this->completed_at = now();
        $this->save();
    }

    /**
     * Marcar solicitação como cancelada.
     */
    public function markAsCanceled(): void
    {
        $this->status = 'canceled';
        $this->save();
    }

    public function parkingLot(): BelongsTo
    {
        return $this->belongsTo(ParkingLot::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parkingSession(): BelongsTo
    {
        return $this->belongsTo(ParkingSession::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function handover(): BelongsTo
    {
        return $this->belongsTo(VehicleHandover::class, 'id', 'valet_request_id');
    }
}
