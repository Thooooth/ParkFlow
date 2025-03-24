<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParkingLot extends Model
{
    protected $fillable = [
        'name',
        'address',
        'total_spots',
        'available_spots',
        'hourly_rate',
        'company_id'
    ];

    public function parkingSessions()
    {
        return $this->hasMany(ParkingSession::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
