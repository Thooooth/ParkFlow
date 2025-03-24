<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ParkingLot;
class ParkingLotPolicy
{
    public function viewAny(User $user)
    {
        return true; // Todos os usuários da empresa podem ver os estacionamentos
    }

    public function create(User $user)
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function update(User $user, ParkingLot $parkingLot)
    {
        return $user->company_id === $parkingLot->company_id &&
               ($user->isAdmin() || $user->isManager());
    }
}