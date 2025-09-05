<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\Frontend\AcademicYearController;
use App\Http\Controllers\Frontend\SchoolClassController;
use App\Http\Controllers\Frontend\SchoolController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Documentation Route
Route::get('docs', [DocumentationController::class, 'index'])
    ->middleware(['auth'])
    ->name('documentation');

// School Frontend Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/schools/dashboard', [SchoolController::class, 'dashboard'])->name('schools.dashboard');
    Route::get('/schools/form-data', [SchoolController::class, 'getFormData'])->name('schools.form-data');
    Route::resource('schools', SchoolController::class)->except(['create', 'edit']);
    
    // School Classes Routes
    Route::prefix('schools/{school}')->name('schools.')->group(function () {
        Route::resource('classes', SchoolClassController::class);
        Route::post('classes/{classId}/restore', [SchoolClassController::class, 'restore'])
            ->name('classes.restore')
            ->withTrashed();
        Route::delete('classes/{classId}/force-delete', [SchoolClassController::class, 'forceDelete'])
            ->name('classes.force-delete')
            ->withTrashed();
    });
    
    // Academic Years Routes
    Route::prefix('schools/{school}')->name('schools.')->group(function () {
        Route::resource('academic-years', AcademicYearController::class)
            ->parameters(['academic-years' => 'academicYear']);
        Route::post('academic-years/{academicYear}/set-current', [AcademicYearController::class, 'setCurrent'])
            ->name('academic-years.set-current');
        Route::post('academic-years/{yearId}/restore', [AcademicYearController::class, 'restore'])
            ->name('academic-years.restore')
            ->withTrashed();
        Route::delete('academic-years/{yearId}/force-delete', [AcademicYearController::class, 'forceDelete'])
            ->name('academic-years.force-delete')
            ->withTrashed();
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
