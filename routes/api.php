<?php

use App\Http\Controllers\Api\AcademicYearController;
use App\Http\Controllers\Api\SchoolClassController;
use App\Http\Controllers\Api\SchoolController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// School API Routes
Route::prefix('schools')->name('api.schools.')->group(function () {
    // Public routes (if any)
    Route::get('statistics', [SchoolController::class, 'statistics'])->name('statistics');
    
    // Protected routes (temporarily without auth for testing)
    // Route::middleware('auth:sanctum')->group(function () {
        // Standard resource routes
        Route::get('/', [SchoolController::class, 'index'])->name('index');
        Route::post('/', [SchoolController::class, 'store'])->name('store');
        Route::get('{school}', [SchoolController::class, 'show'])->name('show');
        Route::put('{school}', [SchoolController::class, 'update'])->name('update');
        Route::delete('{school}', [SchoolController::class, 'destroy'])->name('destroy');
        
        // Additional routes for soft deletes
        Route::get('with-trashed/list', [SchoolController::class, 'withTrashed'])->name('with_trashed');
        Route::get('trashed/list', [SchoolController::class, 'onlyTrashed'])->name('only_trashed');
        Route::post('{id}/restore', [SchoolController::class, 'restore'])->name('restore');
        Route::delete('{id}/force-delete', [SchoolController::class, 'forceDelete'])->name('force_delete');
        
        // Bulk operations
        Route::post('bulk/update-status', [SchoolController::class, 'bulkUpdateStatus'])->name('bulk_update_status');
        
        // School Classes routes
        Route::get('{school}/classes', [SchoolClassController::class, 'index'])->name('classes.index');
        Route::post('{school}/classes', [SchoolClassController::class, 'store'])->name('classes.store');
        Route::get('{school}/classes/{class}', [SchoolClassController::class, 'show'])->name('classes.show');
        Route::put('{school}/classes/{class}', [SchoolClassController::class, 'update'])->name('classes.update');
        Route::delete('{school}/classes/{class}', [SchoolClassController::class, 'destroy'])->name('classes.destroy');
        
        // Academic Years routes
        Route::get('{school}/academic-years', [AcademicYearController::class, 'index'])->name('academic_years.index');
        Route::post('{school}/academic-years', [AcademicYearController::class, 'store'])->name('academic_years.store');
        Route::get('{school}/academic-years/{academicYear}', [AcademicYearController::class, 'show'])->name('academic_years.show');
        Route::put('{school}/academic-years/{academicYear}', [AcademicYearController::class, 'update'])->name('academic_years.update');
        Route::delete('{school}/academic-years/{academicYear}', [AcademicYearController::class, 'destroy'])->name('academic_years.destroy');
        Route::post('{school}/academic-years/{academicYear}/set-current', [AcademicYearController::class, 'setCurrent'])->name('academic_years.set_current');
    // });
});