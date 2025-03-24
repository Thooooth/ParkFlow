<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MonthlyPayment extends Model
{
    protected $fillable = [
        'monthly_subscriber_id',
        'parking_lot_id',
        'amount',
        'payment_method',
        'reference_period',
        'payment_date',
        'due_date',
        'status',
        'invoice_number',
        'transaction_id',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'due_date' => 'date',
    ];

    public function monthlySubscriber(): BelongsTo
    {
        return $this->belongsTo(MonthlySubscriber::class);
    }

    public function parkingLot(): BelongsTo
    {
        return $this->belongsTo(ParkingLot::class);
    }
}
