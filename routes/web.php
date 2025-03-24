<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\File;

Route::get('/', fn() => Inertia::render('welcome'))->name('home');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', fn() => Inertia::render('dashboard'))->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| Grupo de rotas protegidas por autenticação e verificação
|-------------------------------------------------------------------------
*/
foreach (File::allFiles(base_path('routes/web')) as $file) {
    Route::middleware(['auth', 'verified'])
        ->group($file->getPathname());
}

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
