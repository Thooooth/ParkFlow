<?php

declare(strict_types=1);

use App\Http\Controllers\ParkingReservationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas para o Sistema de Reservas
|--------------------------------------------------------------------------
*/

Route::prefix('reservations')->name('reservations.')->group(function () {
    // Listagem e criação de reservas
    Route::get('/', [ParkingReservationController::class, 'index'])->name('index');
    Route::get('/create', [ParkingReservationController::class, 'create'])->name('create');
    Route::post('/', [ParkingReservationController::class, 'store'])->name('store');

    // API para verificar disponibilidade
    Route::post('/check-availability', [ParkingReservationController::class, 'checkAvailability'])
        ->name('check-availability');

    // Visualizar e gerenciar reserva específica
    Route::get('/{reservation}', [ParkingReservationController::class, 'show'])->name('show');
    Route::post('/{reservation}/cancel', [ParkingReservationController::class, 'cancel'])->name('cancel');
});

// Rotas para uso interno/API (protegidas por middleware adicional)
Route::prefix('api/reservations')->name('api.reservations.')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/{reservation}/check-in', [ParkingReservationController::class, 'checkIn'])->name('check-in');
});