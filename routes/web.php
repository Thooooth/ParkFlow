<?php

declare(strict_types=1);

use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn() => Inertia::render('welcome'))->name('home');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', fn() => Inertia::render('dashboard'))->name('dashboard');
});

Route::middleware(['auth'])->group(function (): void {
    Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::post('/subscriptions', [SubscriptionController::class, 'subscribe'])->name('subscriptions.subscribe');
    Route::post('/subscriptions/cancel', [SubscriptionController::class, 'cancel'])->name('subscriptions.cancel');
    Route::post('/subscriptions/resume', [SubscriptionController::class, 'resume'])->name('subscriptions.resume');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
