<?php

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
    // });
});