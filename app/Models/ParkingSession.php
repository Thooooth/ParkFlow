<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Enums\StatusParkingSessionsEnum;
use Illuminate\Database\Eloquent\Model;

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
    ];

    protected $casts = [
        'status' => StatusParkingSessionsEnum::class,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parkingLot()
    {
        return $this->belongsTo(ParkingLot::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
}
