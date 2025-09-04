<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Frontend\SchoolController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// School Frontend Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/schools/dashboard', [SchoolController::class, 'dashboard'])->name('schools.dashboard');
    Route::resource('schools', SchoolController::class);
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
