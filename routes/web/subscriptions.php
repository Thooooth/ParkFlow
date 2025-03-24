<?php

use App\Http\Controllers\Subscription\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->controller(SubscriptionController::class)->prefix('subscriptions')->group(function (): void {
    Route::get('/', 'index')->name('subscriptions.index');
    Route::post('/', 'subscribe')->name('subscriptions.subscribe');
    Route::post('/cancel', 'cancel')->name('subscriptions.cancel');
    Route::post('/resume', 'resume')->name('subscriptions.resume');
});
