<?php

use App\Http\Controllers\Api\AcademicYearController;
use App\Http\Controllers\Api\AuditController;
use App\Http\Controllers\Api\CategoryController;
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
    // Public routes
    Route::get('statistics', [SchoolController::class, 'statistics'])->name('statistics');

    // Protected routes
    Route::middleware(['auth:sanctum', 'permission:view_schools'])->group(function () {
        // Standard resource routes
        Route::get('/', [SchoolController::class, 'index'])->name('index');
        Route::get('{school}', [SchoolController::class, 'show'])
            ->middleware('owner-or-permission:view_schools,school')
            ->name('show');

        Route::middleware('permission:create_schools')->group(function () {
            Route::post('/', [SchoolController::class, 'store'])->name('store');
        });

        Route::middleware('owner-or-permission:edit_schools,school')->group(function () {
            Route::put('{school}', [SchoolController::class, 'update'])->name('update');
        });

        Route::middleware('permission:delete_schools')->group(function () {
            Route::delete('{school}', [SchoolController::class, 'destroy'])->name('destroy');
        });

        // Additional routes for soft deletes
        Route::get('with-trashed/list', [SchoolController::class, 'withTrashed'])->name('with_trashed');
        Route::get('trashed/list', [SchoolController::class, 'onlyTrashed'])->name('only_trashed');

        Route::middleware('permission:restore_schools')->group(function () {
            Route::post('{id}/restore', [SchoolController::class, 'restore'])->name('restore');
        });

        Route::middleware('permission:force_delete_schools')->group(function () {
            Route::delete('{id}/force-delete', [SchoolController::class, 'forceDelete'])->name('force_delete');
        });

        // Enhanced Bulk Operations with Security Controls
        Route::middleware([
            'permission:bulk_edit_schools',
            'throttle:10,60',
            'audit-action:bulk_school_operation',
            'time-based-access:business_hours',
        ])->group(function () {
            Route::post('bulk/update-status', [SchoolController::class, 'bulkUpdateStatus'])->name('bulk_update_status');
            Route::post('bulk/delete', [SchoolController::class, 'bulkDelete'])->name('bulk_delete');
            Route::post('bulk/restore', [SchoolController::class, 'bulkRestore'])
                ->middleware('permission:restore_schools')
                ->name('bulk_restore');
            Route::post('bulk/force-delete', [SchoolController::class, 'bulkForceDelete'])
                ->middleware(['permission:force_delete_schools', 'role:Super Admin'])
                ->name('bulk_force_delete');
            Route::post('bulk/assign-users', [SchoolController::class, 'bulkAssignUsers'])
                ->middleware('permission:assign_school_users')
                ->name('bulk_assign_users');
            Route::post('bulk/export', [SchoolController::class, 'bulkExport'])
                ->middleware('permission:export_schools')
                ->name('bulk_export');
        });

        // School Classes routes
        Route::middleware('owner-or-permission:view_schools,school')->group(function () {
            Route::get('{school}/classes', [SchoolClassController::class, 'index'])->name('classes.index');
            Route::get('{school}/classes/{class}', [SchoolClassController::class, 'show'])->name('classes.show');

            Route::middleware('permission:manage_school_classes')->group(function () {
                Route::post('{school}/classes', [SchoolClassController::class, 'store'])->name('classes.store');
                Route::put('{school}/classes/{class}', [SchoolClassController::class, 'update'])->name('classes.update');
                Route::delete('{school}/classes/{class}', [SchoolClassController::class, 'destroy'])->name('classes.destroy');
            });
        });

        // Academic Years routes
        Route::middleware('owner-or-permission:view_schools,school')->group(function () {
            Route::get('{school}/academic-years', [AcademicYearController::class, 'index'])->name('academic_years.index');
            Route::get('{school}/academic-years/{academicYear}', [AcademicYearController::class, 'show'])->name('academic_years.show');

            Route::middleware('permission:manage_academic_years')->group(function () {
                Route::post('{school}/academic-years', [AcademicYearController::class, 'store'])->name('academic_years.store');
                Route::put('{school}/academic-years/{academicYear}', [AcademicYearController::class, 'update'])->name('academic_years.update');
                Route::delete('{school}/academic-years/{academicYear}', [AcademicYearController::class, 'destroy'])->name('academic_years.destroy');
                Route::post('{school}/academic-years/{academicYear}/set-current', [AcademicYearController::class, 'setCurrent'])->name('academic_years.set_current');
            });
        });
    });
});

// User Management API Routes - System Admin Access
Route::prefix('users')->name('api.users.')->group(function () {
    // Public routes
    Route::get('statistics', [UserController::class, 'statistics'])->name('statistics');

    // Protected routes
    Route::middleware(['auth:sanctum', 'system-user', 'permission:view_users'])->group(function () {
        // Standard resource routes
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('{user}', [UserController::class, 'show'])->name('show');

        Route::middleware('permission:create_users')->group(function () {
            Route::post('/', [UserController::class, 'store'])->name('store');
        });

        Route::middleware('permission:edit_users')->group(function () {
            Route::put('{user}', [UserController::class, 'update'])->name('update');
        });

        Route::middleware('permission:delete_users')->group(function () {
            Route::delete('{user}', [UserController::class, 'destroy'])->name('destroy');
        });

        // Additional routes for soft deletes
        Route::get('with-trashed/list', [UserController::class, 'withTrashed'])->name('with_trashed');
        Route::get('trashed/list', [UserController::class, 'onlyTrashed'])->name('only_trashed');

        Route::middleware('permission:restore_users')->group(function () {
            Route::post('{id}/restore', [UserController::class, 'restore'])->name('restore');
        });

        Route::middleware('permission:force_delete_users')->group(function () {
            Route::delete('{id}/force-delete', [UserController::class, 'forceDelete'])->name('force_delete');
        });

        // Enhanced Bulk User Operations with Security Controls
        Route::middleware([
            'permission:bulk_edit_users',
            'throttle:5,60',
            'audit-action:bulk_user_operation',
            'time-based-access:business_hours',
            'ip-restricted:office_only',
        ])->group(function () {
            Route::post('bulk/update-status', [UserController::class, 'bulkUpdateStatus'])->name('bulk_update_status');
            Route::post('bulk/deactivate', [UserController::class, 'bulkDeactivate'])->name('bulk_deactivate');
            Route::post('bulk/activate', [UserController::class, 'bulkActivate'])->name('bulk_activate');
            Route::post('bulk/assign-roles', [UserController::class, 'bulkAssignRoles'])
                ->middleware('permission:assign_roles')
                ->name('bulk_assign_roles');
            Route::post('bulk/remove-roles', [UserController::class, 'bulkRemoveRoles'])
                ->middleware('permission:assign_roles')
                ->name('bulk_remove_roles');
            Route::post('bulk/export', [UserController::class, 'bulkExport'])
                ->middleware('permission:export_users')
                ->name('bulk_export');
        });

        // Critical Bulk Operations (Super Admin Only)
        Route::middleware([
            'role:Super Admin',
            'throttle:2,60',
            'audit-action:critical_bulk_operation',
            'ip-restricted:strict',
            'time-based-access:business_hours',
        ])->group(function () {
            Route::post('bulk/delete', [UserController::class, 'bulkDelete'])->name('bulk_delete');
            Route::post('bulk/force-delete', [UserController::class, 'bulkForceDelete'])->name('bulk_force_delete');
            Route::post('bulk/reset-passwords', [UserController::class, 'bulkResetPasswords'])->name('bulk_reset_passwords');
        });

        // Role management for users
        Route::middleware('permission:assign_roles')->group(function () {
            Route::post('{user}/assign-role', [UserController::class, 'assignRole'])->name('assign_role');
            Route::post('{user}/remove-role', [UserController::class, 'removeRole'])->name('remove_role');
        });
    });
});

// Role Management API Routes
Route::prefix('roles')->name('api.roles.')->group(function () {
    // Public routes
    Route::get('statistics', [RoleController::class, 'statistics'])->name('statistics');

    // Protected routes
    Route::middleware(['auth:sanctum', 'system-user', 'permission:view_roles'])->group(function () {
        // Standard resource routes
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::get('{role}', [RoleController::class, 'show'])->name('show');

        Route::middleware('permission:create_roles')->group(function () {
            Route::post('/', [RoleController::class, 'store'])->name('store');
        });

        Route::middleware('permission:edit_roles')->group(function () {
            Route::put('{role}', [RoleController::class, 'update'])->name('update');
        });

        Route::middleware('permission:delete_roles')->group(function () {
            Route::delete('{role}', [RoleController::class, 'destroy'])->name('destroy');
        });

        // Permission management for roles
        Route::middleware('permission:assign_permissions_to_roles')->group(function () {
            Route::post('{role}/assign-permission', [RoleController::class, 'assignPermission'])->name('assign_permission');
            Route::post('{role}/remove-permission', [RoleController::class, 'removePermission'])->name('remove_permission');
            Route::post('{role}/sync-permissions', [RoleController::class, 'syncPermissions'])->name('sync_permissions');
        });

        // User management for roles
        Route::middleware('permission:assign_roles')->group(function () {
            Route::post('{role}/assign-users', [RoleController::class, 'assignUsers'])
                ->middleware(['audit-action:role_user_assignment', 'throttle:20,1'])
                ->name('assign_users');
            Route::post('{role}/remove-users', [RoleController::class, 'removeUsers'])
                ->middleware(['audit-action:role_user_removal', 'throttle:20,1'])
                ->name('remove_users');
        });

        // Bulk Role Operations
        Route::middleware([
            'permission:bulk_manage_roles',
            'throttle:5,60',
            'audit-action:bulk_role_operation',
            'time-based-access:business_hours',
        ])->group(function () {
            Route::post('bulk/create', [RoleController::class, 'bulkCreate'])->name('bulk_create');
            Route::post('bulk/update-permissions', [RoleController::class, 'bulkUpdatePermissions'])->name('bulk_update_permissions');
            Route::post('bulk/sync-permissions', [RoleController::class, 'bulkSyncPermissions'])->name('bulk_sync_permissions');
        });

        // Get all permissions
        Route::get('permissions', [RoleController::class, 'permissions'])->name('permissions');

        // Get all guard names
        Route::get('guard-names', [RoleController::class, 'guardNames'])->name('guard_names');
    });
});

// Permission Management API Routes
Route::prefix('permissions')->name('api.permissions.')->group(function () {
    // Public routes
    Route::get('statistics', [PermissionController::class, 'statistics'])->name('statistics');
    Route::get('categories', [PermissionController::class, 'categories'])->name('categories');

    // Protected routes
    Route::middleware(['auth:sanctum', 'system-user', 'permission:view_permissions'])->group(function () {
        // Standard resource routes
        Route::get('/', [PermissionController::class, 'index'])->name('index');
        Route::get('grouped', [PermissionController::class, 'grouped'])->name('grouped');
        Route::get('{permission}', [PermissionController::class, 'show'])->name('show');

        // Role management for permissions
        Route::middleware('permission:assign_permissions_to_roles')->group(function () {
            Route::post('{permission}/sync-roles', [PermissionController::class, 'syncRoles'])
                ->middleware(['audit-action:permission_role_sync', 'throttle:30,1'])
                ->name('sync_roles');
        });

        // Bulk Permission Operations (Super Admin Only)
        Route::middleware([
            'role:Super Admin',
            'permission:manage_permissions',
            'throttle:3,60',
            'audit-action:bulk_permission_operation',
            'ip-restricted:strict',
        ])->group(function () {
            Route::post('bulk/create', [PermissionController::class, 'bulkCreate'])->name('bulk_create');
            Route::post('bulk/update', [PermissionController::class, 'bulkUpdate'])->name('bulk_update');
            Route::post('bulk/assign-to-roles', [PermissionController::class, 'bulkAssignToRoles'])->name('bulk_assign_to_roles');
        });

        // Permission queries by role/user
        Route::get('by-role/{role}', [PermissionController::class, 'byRole'])->name('by_role');
        Route::get('by-user/{user}', [PermissionController::class, 'byUser'])->name('by_user');
        Route::get('check-user/{user}', [PermissionController::class, 'checkUserPermission'])->name('check_user');

        // Get all guard names for permissions
        Route::get('guard-names', [PermissionController::class, 'guardNames'])->name('guard_names');
    });
});

// Category Management API Routes
Route::prefix('categories')->name('api.categories.')->group(function () {
    // Public routes
    Route::get('statistics', [CategoryController::class, 'statistics'])->name('statistics');
    Route::get('tree', [CategoryController::class, 'tree'])->name('tree');

    // Protected routes
    Route::middleware(['auth:sanctum', 'permission:view_categories'])->group(function () {
        // Standard resource routes
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::get('{category}', [CategoryController::class, 'show'])->name('show');

        Route::middleware('permission:create_categories')->group(function () {
            Route::post('/', [CategoryController::class, 'store'])->name('store');
        });

        Route::middleware('permission:edit_categories')->group(function () {
            Route::put('{category}', [CategoryController::class, 'update'])->name('update');
        });

        Route::middleware('permission:delete_categories')->group(function () {
            Route::delete('{category}', [CategoryController::class, 'destroy'])->name('destroy');
        });

        // Additional routes for soft deletes
        Route::get('with-trashed/list', [CategoryController::class, 'withTrashed'])->name('with_trashed');
        Route::get('trashed/list', [CategoryController::class, 'onlyTrashed'])->name('only_trashed');

        Route::middleware('permission:restore_categories')->group(function () {
            Route::post('{id}/restore', [CategoryController::class, 'restore'])->name('restore');
        });

        Route::middleware('permission:force_delete_categories')->group(function () {
            Route::delete('{id}/force-delete', [CategoryController::class, 'forceDelete'])->name('force_delete');
        });

        // Category-specific routes
        Route::middleware('permission:manage_category_status')->group(function () {
            Route::post('{category}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('toggle_status');
        });

        Route::middleware('permission:manage_category_hierarchy')->group(function () {
            Route::post('{category}/move', [CategoryController::class, 'move'])->name('move');
            Route::post('reorder', [CategoryController::class, 'reorder'])->name('reorder');
        });

        // Bulk Category Operations
        Route::middleware([
            'permission:bulk_edit_categories',
            'throttle:10,60',
            'audit-action:bulk_category_operation',
            'time-based-access:business_hours',
        ])->group(function () {
            Route::post('bulk/update-status', [CategoryController::class, 'bulkUpdateStatus'])->name('bulk_update_status');
        });
    });
});

// Audit Management API Routes
Route::prefix('audits')->name('api.audits.')->group(function () {
    // Protected routes
    Route::middleware(['auth:sanctum', 'role:System Admin,Auditor,School Admin'])->group(function () {
        // Standard resource routes
        Route::middleware('permission:view_audit_trail')->group(function () {
            Route::get('/', [AuditController::class, 'index'])->name('index');
            Route::get('{audit}', [AuditController::class, 'show'])->name('show');

            // Get audits for specific model/user
            Route::get('model/{modelType}/{modelId}', [AuditController::class, 'forModel'])->name('for_model');
            Route::get('user/{userId}', [AuditController::class, 'forUser'])->name('for_user');

            // Timeline view
            Route::get('timeline/{modelType}/{modelId}', [AuditController::class, 'timeline'])->name('timeline');
        });

        // Statistics and analytics
        Route::middleware('permission:view_audit_statistics')->group(function () {
            Route::get('statistics/summary', [AuditController::class, 'statistics'])->name('statistics');
        });

        // Get available models
        Route::get('models/list', [AuditController::class, 'models'])->name('models');

        // Bulk Audit Operations (Auditor/System Admin Only)
        Route::middleware([
            'role:System Admin,Auditor',
            'permission:manage_audit_data',
            'throttle:5,60',
            'audit-action:bulk_audit_operation',
            'time-based-access:business_hours',
        ])->group(function () {
            Route::post('bulk/export', [AuditController::class, 'bulkExport'])->name('bulk_export');
            Route::post('bulk/archive', [AuditController::class, 'bulkArchive'])->name('bulk_archive');
            Route::delete('bulk/cleanup', [AuditController::class, 'bulkCleanup'])
                ->middleware(['role:Super Admin', 'ip-restricted:strict'])
                ->name('bulk_cleanup');
        });
    });
});
