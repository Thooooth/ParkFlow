<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $fillable = [
        'plate',
        'model',
        'color',
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parkingSessions()
    {
        return $this->hasMany(ParkingSession::class);
    }
}
