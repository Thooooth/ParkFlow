<?php

declare(strict_types=1);

use App\Http\Controllers\ParkingSpotController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas para o Sistema de Gestão de Vagas
|--------------------------------------------------------------------------
*/

Route::prefix('parking-spots')->name('parking-spots.')->group(function () {
    // Listagem e gestão básica
    Route::get('/', [ParkingSpotController::class, 'index'])->name('index');
    Route::get('/create', [ParkingSpotController::class, 'create'])->name('create');
    Route::post('/', [ParkingSpotController::class, 'store'])->name('store');
    Route::get('/{parking_spot}', [ParkingSpotController::class, 'show'])->name('show');
    Route::get('/{parking_spot}/edit', [ParkingSpotController::class, 'edit'])->name('edit');
    Route::put('/{parking_spot}', [ParkingSpotController::class, 'update'])->name('update');
    Route::delete('/{parking_spot}', [ParkingSpotController::class, 'destroy'])->name('destroy');

    // Visualização de mapa
    Route::get('/map/{parking_lot_id}', [ParkingSpotController::class, 'map'])->name('map');

    // Operações específicas
    Route::post('/{parking_spot}/maintenance', [ParkingSpotController::class, 'setMaintenance'])->name('maintenance');
    Route::post('/{parking_spot}/available', [ParkingSpotController::class, 'setAvailable'])->name('available');

    // API para obter status em tempo real
    Route::get('/status/{parking_lot_id}', [ParkingSpotController::class, 'getSpotStatus'])->name('status');
});
