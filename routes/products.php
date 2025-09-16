<?php

use App\Http\Controllers\Api\ProductController as ApiProductController;
use App\Http\Controllers\Frontend\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Product Routes
|--------------------------------------------------------------------------
|
| Here are the routes for product management, both frontend and API endpoints.
| These routes are organized with proper permissions and security controls.
|
*/

// Frontend Product Routes
Route::middleware(['auth', 'verified', 'audit-action:user_access'])->group(function () {
    Route::middleware(['permission:view_products'])->group(function () {
        Route::get('/products/form-data', [ProductController::class, 'getFormData'])->name('products.form-data');

        // Product resource routes with specific permissions
        Route::get('products', [ProductController::class, 'index'])->name('products.index');
        Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');
        Route::get('products/{product}/edit', [ProductController::class, 'edit'])
            ->middleware('permission:edit_products')
            ->name('products.edit');

        Route::post('products', [ProductController::class, 'store'])
            ->middleware('permission:create_products')
            ->name('products.store');

        Route::put('products/{product}', [ProductController::class, 'update'])
            ->middleware('permission:edit_products')
            ->name('products.update');

        Route::delete('products/{product}', [ProductController::class, 'destroy'])
            ->middleware('permission:delete_products')
            ->name('products.destroy');

        // Image upload routes
        Route::post('products/{product}/image', [ProductController::class, 'uploadImage'])
            ->middleware('permission:edit_products')
            ->name('products.upload-image');

        Route::delete('products/{product}/image', [ProductController::class, 'deleteImage'])
            ->middleware('permission:edit_products')
            ->name('products.delete-image');

        // Product Management Pages - Variants, Pricing, Inventory
        Route::get('products/{product}/variants', [ProductController::class, 'variants'])
            ->name('products.variants');

        Route::get('products/{product}/pricing', [ProductController::class, 'pricing'])
            ->name('products.pricing');

        Route::get('products/{product}/inventory', [ProductController::class, 'inventory'])
            ->name('products.inventory');

        // Variant CRUD Routes
        Route::post('products/{product}/variants', [ProductController::class, 'storeVariant'])
            ->middleware('permission:create_products')
            ->name('products.variants.store');

        Route::put('products/{product}/variants/{variant}', [ProductController::class, 'updateVariant'])
            ->middleware('permission:edit_products')
            ->name('products.variants.update');

        Route::delete('products/{product}/variants/{variant}', [ProductController::class, 'destroyVariant'])
            ->middleware('permission:delete_products')
            ->name('products.variants.destroy');

        // Variant image upload routes
        Route::post('products/{product}/variants/upload-image', [ProductController::class, 'uploadVariantImage'])
            ->middleware('permission:edit_products')
            ->name('products.variants.upload-image');

        // Price Management Routes
        Route::post('products/{product}/prices', [ProductController::class, 'storePrice'])
            ->middleware('permission:edit_products')
            ->name('products.prices.store');

        Route::get('products/{product}/prices/pending', [ProductController::class, 'getPendingPrices'])
            ->middleware('permission:approve_pricing')
            ->name('products.prices.pending');

        Route::put('products/{product}/prices/{productPrice}/approve', [ProductController::class, 'approvePrice'])
            ->middleware('permission:approve_pricing')
            ->name('products.prices.approve');

        Route::put('products/{product}/prices/{productPrice}/reject', [ProductController::class, 'rejectPrice'])
            ->middleware('permission:approve_pricing')
            ->name('products.prices.reject');
    });
});

// API Product Routes
Route::prefix('api/products')->name('api.products.')->group(function () {
    // Public routes
    Route::get('statistics', [ApiProductController::class, 'statistics'])->name('statistics');
    Route::get('search', [ApiProductController::class, 'search'])->name('search');

    // Protected routes
    Route::middleware(['auth:sanctum', 'permission:view_products'])->group(function () {
        // Standard resource routes
        Route::get('/', [ApiProductController::class, 'index'])->name('index');
        Route::get('{product}', [ApiProductController::class, 'show'])->name('show');

        Route::middleware('permission:create_products')->group(function () {
            Route::post('/', [ApiProductController::class, 'store'])->name('store');
        });

        Route::middleware('permission:edit_products')->group(function () {
            Route::put('{product}', [ApiProductController::class, 'update'])->name('update');
        });

        Route::middleware('permission:delete_products')->group(function () {
            Route::delete('{product}', [ApiProductController::class, 'destroy'])->name('destroy');
        });

        // Additional routes for soft deletes
        Route::get('with-trashed/list', [ApiProductController::class, 'withTrashed'])->name('with_trashed');
        Route::get('trashed/list', [ApiProductController::class, 'onlyTrashed'])->name('only_trashed');

        Route::middleware('permission:restore_products')->group(function () {
            Route::post('{id}/restore', [ApiProductController::class, 'restore'])->name('restore');
        });

        Route::middleware('permission:force_delete_products')->group(function () {
            Route::delete('{id}/force-delete', [ApiProductController::class, 'forceDelete'])->name('force_delete');
        });

        // Enhanced Bulk Operations with Security Controls
        Route::middleware([
            'permission:bulk_edit_products',
            'throttle:10,60',
            'audit-action:bulk_product_operation',
            'time-based-access:business_hours',
        ])->group(function () {
            Route::post('bulk/update-status', [ApiProductController::class, 'bulkUpdateStatus'])->name('bulk_update_status');
            Route::post('bulk/delete', [ApiProductController::class, 'bulkDelete'])->name('bulk_delete');
        });
    });
});
