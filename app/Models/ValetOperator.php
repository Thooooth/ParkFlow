<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ValetOperator extends Model
{
    protected $fillable = [
        'user_id',
        'parking_lot_id',
        'registration_number',
        'status',
        'started_at',
        'ended_at',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'date',
        'ended_at' => 'date',
    ];

    /**
     * Obter todos os operadores ativos de um estacionamento.
     */
    public static function getActiveOperators(int $parkingLotId)
    {
        return self::where('parking_lot_id', $parkingLotId)
            ->where('status', 'active')
            ->get();
    }

    /**
     * Marcar operador como inativo.
     */
    public function deactivate(string $reason = null): void
    {
        $this->status = 'inactive';
        $this->ended_at = now();

        if ($reason) {
            $this->notes = $this->notes
                ? $this->notes . "\n" . "Desativado em " . now()->format('d/m/Y') . ": " . $reason
                : "Desativado em " . now()->format('d/m/Y') . ": " . $reason;
        }

        $this->save();
    }

    /**
     * Reativar operador.
     */
    public function reactivate(): void
    {
        $this->status = 'active';
        $this->ended_at = null;
        $this->save();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parkingLot(): BelongsTo
    {
        return $this->belongsTo(ParkingLot::class);
    }

    public function handovers(): HasMany
    {
        return $this->hasMany(VehicleHandover::class, 'operator_id');
    }
}
