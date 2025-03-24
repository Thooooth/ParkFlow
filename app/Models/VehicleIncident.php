<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class VehicleIncident extends Model
{
    protected $fillable = [
        'parking_lot_id',
        'parking_session_id',
        'reported_by',
        'valet_operator_id',
        'incident_type',
        'title',
        'description',
        'media_files',
        'damage_locations',
        'severity',
        'customer_notified',
        'customer_acknowledged',
        'customer_acknowledgment_time',
        'customer_comments',
        'status',
        'resolution_notes',
        'resolution_time',
        'resolution_by',
    ];

    protected $casts = [
        'media_files' => 'array',
        'damage_locations' => 'array',
        'customer_notified' => 'boolean',
        'customer_acknowledged' => 'boolean',
        'customer_acknowledgment_time' => 'datetime',
        'resolution_time' => 'datetime',
    ];

    /**
     * Adicionar arquivos de mídia ao incidente (fotos, vídeos, áudios).
     */
    public function addMediaFiles(array $files): void
    {
        $currentFiles = $this->media_files ?? [];
        $this->media_files = array_merge($currentFiles, $files);
        $this->save();
    }

    /**
     * Marcar a resolução do incidente.
     */
    public function resolve(string $notes, string $resolvedBy): void
    {
        $this->status = 'resolved';
        $this->resolution_notes = $notes;
        $this->resolution_time = now();
        $this->resolution_by = $resolvedBy;
        $this->save();
    }

    /**
     * Fechar o incidente.
     */
    public function close(string $notes = null): void
    {
        if ($notes) {
            $this->resolution_notes = $this->resolution_notes
                ? $this->resolution_notes . "\n" . $notes
                : $notes;
        }

        $this->status = 'closed';
        $this->save();
    }

    /**
     * Registrar reconhecimento do cliente.
     */
    public function acknowledgeByCustomer(string $comments = null): void
    {
        $this->customer_acknowledged = true;
        $this->customer_acknowledgment_time = now();

        if ($comments) {
            $this->customer_comments = $comments;
        }

        $this->save();
    }

    /**
     * Marcar cliente como notificado.
     */
    public function markCustomerAsNotified(): void
    {
        $this->customer_notified = true;
        $this->save();
    }

    /**
     * Atualizar o status do incidente para "em progresso".
     */
    public function markAsInProgress(): void
    {
        $this->status = 'in_progress';
        $this->save();
    }

    public function parkingLot(): BelongsTo
    {
        return $this->belongsTo(ParkingLot::class);
    }

    public function parkingSession(): BelongsTo
    {
        return $this->belongsTo(ParkingSession::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function valetOperator(): BelongsTo
    {
        return $this->belongsTo(ValetOperator::class, 'valet_operator_id');
    }
}
