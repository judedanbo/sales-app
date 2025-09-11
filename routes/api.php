<?php

use App\Http\Controllers\Api\AcademicYearController;
use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\PermissionController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SchoolClassController;
use App\Http\Controllers\Api\SchoolController;
use App\Http\Controllers\Api\UserController;
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

// User Management API Routes
Route::prefix('users')->name('api.users.')->group(function () {
    // Public routes (if any)
    Route::get('statistics', [UserController::class, 'statistics'])->name('statistics');

    // Protected routes (temporarily without auth for testing)
    // Route::middleware('auth:sanctum')->group(function () {
    // Standard resource routes
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::post('/', [UserController::class, 'store'])->name('store');
    Route::get('{user}', [UserController::class, 'show'])->name('show');
    Route::put('{user}', [UserController::class, 'update'])->name('update');
    Route::delete('{user}', [UserController::class, 'destroy'])->name('destroy');

    // Additional routes for soft deletes
    Route::get('with-trashed/list', [UserController::class, 'withTrashed'])->name('with_trashed');
    Route::get('trashed/list', [UserController::class, 'onlyTrashed'])->name('only_trashed');
    Route::post('{id}/restore', [UserController::class, 'restore'])->name('restore');
    Route::delete('{id}/force-delete', [UserController::class, 'forceDelete'])->name('force_delete');

    // Bulk operations
    Route::post('bulk/update-status', [UserController::class, 'bulkUpdateStatus'])->name('bulk_update_status');

    // Role management for users
    Route::post('{user}/assign-role', [UserController::class, 'assignRole'])->name('assign_role');
    Route::post('{user}/remove-role', [UserController::class, 'removeRole'])->name('remove_role');
    // });
});

// Role Management API Routes
Route::prefix('roles')->name('api.roles.')->group(function () {
    // Public routes (if any)
    Route::get('statistics', [RoleController::class, 'statistics'])->name('statistics');

    // Protected routes (temporarily without auth for testing)
    // Route::middleware('auth:sanctum')->group(function () {
    // Standard resource routes
    Route::get('/', [RoleController::class, 'index'])->name('index');
    Route::post('/', [RoleController::class, 'store'])->name('store');
    Route::get('{role}', [RoleController::class, 'show'])->name('show');
    Route::put('{role}', [RoleController::class, 'update'])->name('update');
    Route::delete('{role}', [RoleController::class, 'destroy'])->name('destroy');

    // Permission management for roles
    Route::post('{role}/assign-permission', [RoleController::class, 'assignPermission'])->name('assign_permission');
    Route::post('{role}/remove-permission', [RoleController::class, 'removePermission'])->name('remove_permission');
    Route::post('{role}/sync-permissions', [RoleController::class, 'syncPermissions'])->name('sync_permissions');

    // User management for roles
    Route::post('{role}/assign-users', [RoleController::class, 'assignUsers'])->name('assign_users');
    Route::post('{role}/remove-users', [RoleController::class, 'removeUsers'])->name('remove_users');

    // Get all permissions
    Route::get('permissions', [RoleController::class, 'permissions'])->name('permissions');

    // Get all guard names
    Route::get('guard-names', [RoleController::class, 'guardNames'])->name('guard_names');
    // });
});

// Permission Management API Routes
Route::prefix('permissions')->name('api.permissions.')->group(function () {
    // Public routes (if any)
    Route::get('statistics', [PermissionController::class, 'statistics'])->name('statistics');
    Route::get('categories', [PermissionController::class, 'categories'])->name('categories');

    // Protected routes (temporarily without auth for testing)
    // Route::middleware('auth:sanctum')->group(function () {
    // Standard resource routes
    Route::get('/', [PermissionController::class, 'index'])->name('index');
    Route::get('grouped', [PermissionController::class, 'grouped'])->name('grouped');
    Route::get('{permission}', [PermissionController::class, 'show'])->name('show');

    // Role management for permissions
    Route::post('{permission}/sync-roles', [PermissionController::class, 'syncRoles'])->name('sync_roles');

    // Permission queries by role/user
    Route::get('by-role/{role}', [PermissionController::class, 'byRole'])->name('by_role');
    Route::get('by-user/{user}', [PermissionController::class, 'byUser'])->name('by_user');
    Route::get('check-user/{user}', [PermissionController::class, 'checkUserPermission'])->name('check_user');

    // Get all guard names for permissions
    Route::get('guard-names', [PermissionController::class, 'guardNames'])->name('guard_names');
    // });
});

// Audit Management API Routes
Route::prefix('audits')->name('api.audits.')->group(function () {
    // Protected routes (temporarily without auth for testing)
    // Route::middleware('auth:sanctum')->group(function () {
    // Standard resource routes
    Route::get('/', [AuditController::class, 'index'])->name('index');
    Route::get('{audit}', [AuditController::class, 'show'])->name('show');

    // Statistics and analytics
    Route::get('statistics/summary', [AuditController::class, 'statistics'])->name('statistics');

    // Get audits for specific model/user
    Route::get('model/{modelType}/{modelId}', [AuditController::class, 'forModel'])->name('for_model');
    Route::get('user/{userId}', [AuditController::class, 'forUser'])->name('for_user');

    // Timeline view
    Route::get('timeline/{modelType}/{modelId}', [AuditController::class, 'timeline'])->name('timeline');

    // Get available models
    Route::get('models/list', [AuditController::class, 'models'])->name('models');
    // });
});
