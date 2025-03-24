<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class MonthlySubscriber extends Model
{
    protected $fillable = [
        'parking_lot_id',
        'user_id',
        'name',
        'email',
        'phone',
        'document_number',
        'monthly_fee',
        'start_date',
        'end_date',
        'next_payment_date',
        'payment_status',
        'vehicle_plate',
        'vehicle_model',
        'vehicle_color',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_payment_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // Quando um mensalista é criado ou status é alterado
        static::saved(function (MonthlySubscriber $subscriber) {
            // Atualiza a contagem de vagas disponíveis para mensalistas
            $subscriber->parkingLot->updateAvailableMonthlySpots();
        });

        // Quando um mensalista é removido
        static::deleted(function (MonthlySubscriber $subscriber) {
            // Atualiza a contagem de vagas disponíveis para mensalistas
            $subscriber->parkingLot->updateAvailableMonthlySpots();
        });
    }

    public function parkingLot(): BelongsTo
    {
        return $this->belongsTo(ParkingLot::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(MonthlyPayment::class);
    }
}
