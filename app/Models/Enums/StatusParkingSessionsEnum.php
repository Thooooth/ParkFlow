<?php

namespace App\Models\Enums;

enum StatusParkingSessionsEnum: string
{
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
