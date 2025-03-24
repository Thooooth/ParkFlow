<?php

declare(strict_types=1);

namespace App\Models\Enums;

enum StatusParkingSessionsEnum: string
{
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
