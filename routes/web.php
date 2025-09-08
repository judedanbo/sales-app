<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\Frontend\AcademicYearController;
use App\Http\Controllers\Frontend\PermissionController;
use App\Http\Controllers\Frontend\RoleController;
use App\Http\Controllers\Frontend\SchoolClassController;
use App\Http\Controllers\Frontend\SchoolController;
use App\Http\Controllers\Frontend\UserController;
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

    // User Management Frontend Routes
    Route::resource('users', UserController::class);
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('{user}/roles', [UserController::class, 'roles'])->name('roles');
        Route::post('{user}/assign-role', [UserController::class, 'assignRole'])->name('assign-role');
        Route::delete('{user}/remove-role', [UserController::class, 'removeRole'])->name('remove-role');
        Route::post('{user}/activate', [UserController::class, 'activate'])->name('activate');
        Route::post('{user}/deactivate', [UserController::class, 'deactivate'])->name('deactivate');
        Route::post('{id}/restore', [UserController::class, 'restore'])->name('restore')->withTrashed();
    });

    // Role Management Frontend Routes
    Route::resource('roles', RoleController::class);
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('all-permissions', [RoleController::class, 'allPermissions'])->name('all-permissions');
        Route::get('{role}/permissions', [RoleController::class, 'permissions'])->name('permissions');
        Route::post('{role}/sync-permissions', [RoleController::class, 'syncPermissions'])->name('sync-permissions');
        Route::get('{role}/users', [RoleController::class, 'users'])->name('users');
        Route::get('{role}/available-users', [RoleController::class, 'availableUsers'])->name('available-users');
        Route::post('{role}/assign-users', [RoleController::class, 'assignUsers'])->name('assign-users');
        Route::delete('{role}/remove-users', [RoleController::class, 'removeUsers'])->name('remove-users');
    });

    // Permission Management Frontend Routes
    Route::prefix('permissions')->name('permissions.')->group(function () {
        Route::get('/', [PermissionController::class, 'index'])->name('index');
        Route::get('grouped', [PermissionController::class, 'grouped'])->name('grouped');
        Route::get('statistics', [PermissionController::class, 'statistics'])->name('statistics');
        Route::get('{permission}', [PermissionController::class, 'show'])->name('show');
        Route::get('by-role/{role}', [PermissionController::class, 'byRole'])->name('by-role');
        Route::get('by-user/{user}', [PermissionController::class, 'byUser'])->name('by-user');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
