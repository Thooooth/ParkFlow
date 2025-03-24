<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class VehicleHandover extends Model
{
    protected $fillable = [
        'parking_lot_id',
        'valet_request_id',
        'parking_session_id',
        'operator_id',
        'handover_time',
        'vehicle_condition_ok',
        'issues_reported',
        'damage_photos',
        'signature',
        'customer_name',
        'customer_document',
        'customer_email',
        'customer_phone',
        'handover_type',
        'customer_confirmed',
        'confirmation_time',
        'notes',
    ];

    protected $casts = [
        'handover_time' => 'datetime',
        'confirmation_time' => 'datetime',
        'vehicle_condition_ok' => 'boolean',
        'customer_confirmed' => 'boolean',
        'damage_photos' => 'array',
    ];

    /**
     * Confirmar recebimento pelo cliente.
     */
    public function confirmReceiptByCustomer(bool $conditionOk = true, ?string $issues = null, ?array $photos = null): void
    {
        $this->customer_confirmed = true;
        $this->confirmation_time = now();

        if (!$conditionOk) {
            $this->vehicle_condition_ok = false;
            $this->issues_reported = $issues;

            if ($photos && is_array($photos)) {
                $this->damage_photos = $photos;
            }
        }

        $this->save();
    }

    /**
     * Registrar novos problemas reportados pelo cliente.
     */
    public function reportIssues(string $issues, ?array $photos = null): void
    {
        $this->vehicle_condition_ok = false;
        $this->issues_reported = $issues;

        if ($photos && is_array($photos)) {
            $currentPhotos = $this->damage_photos ?? [];
            $this->damage_photos = array_merge($currentPhotos, $photos);
        }

        $this->save();
    }

    public function parkingLot(): BelongsTo
    {
        return $this->belongsTo(ParkingLot::class);
    }

    public function valetRequest(): BelongsTo
    {
        return $this->belongsTo(ValetRequest::class);
    }

    public function parkingSession(): BelongsTo
    {
        return $this->belongsTo(ParkingSession::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(ValetOperator::class, 'operator_id');
    }
}
