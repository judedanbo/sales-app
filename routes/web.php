<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\Frontend\AcademicYearController;
use App\Http\Controllers\Frontend\AuditController;
use App\Http\Controllers\Frontend\CategoryController;
use App\Http\Controllers\Frontend\PermissionController;
use App\Http\Controllers\Frontend\RoleController;
use App\Http\Controllers\Frontend\SaleController;
use App\Http\Controllers\Frontend\SchoolClassController;
use App\Http\Controllers\Frontend\SchoolController;
use App\Http\Controllers\Frontend\UserController;
use App\Http\Controllers\ReceiptVerificationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/test-alerts', function () {
    return Inertia::render('TestAlerts');
})->name('test-alerts');

// Guest receipt verification routes
Route::get('/receipt/{verificationToken}', [ReceiptVerificationController::class, 'show'])
    ->name('receipt.verify');

// Fallback route for temporary verification (using sale_number)
Route::get('/receipt/temp/{saleIdentifier}', [ReceiptVerificationController::class, 'showBySaleNumber'])
    ->name('receipt.verify.temp');


// Level 1: Basic authenticated access
Route::middleware(['auth'])->group(function () {
    Route::get('docs', [DocumentationController::class, 'index'])->name('documentation');
});

// Level 2: Verified user access with basic audit
Route::middleware(['auth', 'verified', 'audit-action:user_access'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // School Frontend Routes - Permission-based access
    Route::middleware(['permission:view_schools'])->group(function () {
        Route::get('/schools/dashboard', [SchoolController::class, 'dashboard'])->name('schools.dashboard');
        Route::get('/schools/form-data', [SchoolController::class, 'getFormData'])->name('schools.form-data');

        // School resource routes with specific permissions
        Route::get('schools', [SchoolController::class, 'index'])->name('schools.index');
        Route::get('schools/{school}', [SchoolController::class, 'show'])
            ->middleware('owner-or-permission:view_schools,school')
            ->name('schools.show');
        Route::post('schools', [SchoolController::class, 'store'])
            ->middleware('permission:create_schools')
            ->name('schools.store');
        Route::put('schools/{school}', [SchoolController::class, 'update'])
            ->middleware('owner-or-permission:edit_schools,school')
            ->name('schools.update');
        Route::delete('schools/{school}', [SchoolController::class, 'destroy'])
            ->middleware('permission:delete_schools')
            ->name('schools.destroy');

        // School Classes Routes - Enhanced with cascade validation
        Route::prefix('schools/{school}')->name('schools.')
            ->middleware(['owner-or-permission:view_schools,school', 'cascade-auth:school.classes,class'])
            ->group(function () {
                Route::resource('classes', SchoolClassController::class)
                    ->middleware(['permission:manage_school_classes']);
                Route::post('classes/{classId}/restore', [SchoolClassController::class, 'restore'])
                    ->name('classes.restore')
                    ->middleware('permission:restore_school_classes')
                    ->withTrashed();
                Route::delete('classes/{classId}/force-delete', [SchoolClassController::class, 'forceDelete'])
                    ->name('classes.force-delete')
                    ->middleware('permission:force_delete_school_classes')
                    ->withTrashed();
            });

        // Academic Years Routes - Enhanced with cascade validation
        Route::prefix('schools/{school}')->name('schools.')
            ->middleware(['owner-or-permission:view_schools,school', 'cascade-auth:school.academic_years,academicYear'])
            ->group(function () {
                Route::resource('academic-years', AcademicYearController::class)
                    ->parameters(['academic-years' => 'academicYear'])
                    ->middleware(['permission:manage_academic_years']);
                Route::post('academic-years/{academicYear}/set-current', [AcademicYearController::class, 'setCurrent'])
                    ->name('academic-years.set-current')
                    ->middleware('permission:manage_academic_years');
                Route::post('academic-years/{yearId}/restore', [AcademicYearController::class, 'restore'])
                    ->name('academic-years.restore')
                    ->middleware('permission:restore_academic_years')
                    ->withTrashed();
                Route::delete('academic-years/{yearId}/force-delete', [AcademicYearController::class, 'forceDelete'])
                    ->name('academic-years.force-delete')
                    ->middleware('permission:force_delete_academic_years')
                    ->withTrashed();
            });

        // Category Frontend Routes - Permission-based access
        Route::middleware(['permission:view_categories'])->group(function () {
            Route::get('/categories/dashboard', [CategoryController::class, 'dashboard'])->name('categories.dashboard');
            Route::get('/categories/tree', [CategoryController::class, 'tree'])->name('categories.tree');
            Route::get('/categories/form-data', [CategoryController::class, 'getFormData'])->name('categories.form-data');

            // Category resource routes with specific permissions
            Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
            Route::get('categories/{category}', [CategoryController::class, 'show'])->name('categories.show');
            Route::post('categories', [CategoryController::class, 'store'])
                ->middleware('permission:create_categories')
                ->name('categories.store');
            Route::put('categories/{category}', [CategoryController::class, 'update'])
                ->middleware('permission:edit_categories')
                ->name('categories.update');
            Route::delete('categories/{category}', [CategoryController::class, 'destroy'])
                ->middleware('permission:delete_categories')
                ->name('categories.destroy');

            // Category-specific actions
            Route::post('categories/{category}/toggle-status', [CategoryController::class, 'toggleStatus'])
                ->middleware('permission:manage_category_status')
                ->name('categories.toggle-status');
            Route::post('categories/{category}/move', [CategoryController::class, 'move'])
                ->middleware('permission:manage_category_hierarchy')
                ->name('categories.move');
        });

        // Sales Frontend Routes - Permission-based access
        Route::middleware(['permission:view_sales'])->group(function () {
            // Sales POS interface
            Route::get('/sales/pos', [SaleController::class, 'pos'])
                ->middleware('permission:process_sales')
                ->name('sales.pos');

            // Sales resource routes with specific permissions
            Route::get('sales', [SaleController::class, 'index'])->name('sales.index');
            Route::get('sales/{sale}', [SaleController::class, 'show'])->name('sales.show');

            // Sales creation route
            Route::post('sales', [SaleController::class, 'store'])
                ->middleware('permission:process_sales')
                ->name('sales.store');

            // Sales void route
            Route::post('sales/{sale}/void', [SaleController::class, 'void'])
                ->middleware('permission:void_sales')
                ->name('sales.void');
        });
    });
});

// Level 3: Administrative Access - Enhanced Security
Route::middleware([
    'auth',
    'verified',
    'system-user',
    'permission:view_users',
    'throttle:100,60',
    'audit-action:admin_access',
    'time-based-access:extended_hours',
])->group(function () {
    // User Management
    Route::resource('users', UserController::class);
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('{user}/roles', [UserController::class, 'roles'])->name('roles');
        Route::post('{user}/assign-role', [UserController::class, 'assignRole'])
            ->middleware('permission:assign_roles')
            ->name('assign-role');
        Route::delete('{user}/remove-role', [UserController::class, 'removeRole'])
            ->middleware('permission:assign_roles')
            ->name('remove-role');
        Route::post('{user}/activate', [UserController::class, 'activate'])
            ->middleware('permission:edit_users')
            ->name('activate');
        Route::post('{user}/deactivate', [UserController::class, 'deactivate'])
            ->middleware('permission:edit_users')
            ->name('deactivate');
        Route::post('{id}/restore', [UserController::class, 'restore'])
            ->middleware('permission:restore_users')
            ->name('restore')
            ->withTrashed();
    });

    // Role Management
    Route::middleware('permission:view_roles')->group(function () {
        Route::resource('roles', RoleController::class);
        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('all-permissions', [RoleController::class, 'allPermissions'])->name('all-permissions');
            Route::get('{role}/permissions', [RoleController::class, 'permissions'])->name('permissions');
            Route::post('{role}/sync-permissions', [RoleController::class, 'syncPermissions'])
                ->middleware('permission:assign_permissions_to_roles')
                ->name('sync-permissions');
            Route::get('{role}/users', [RoleController::class, 'users'])->name('users');
            Route::get('{role}/available-users', [RoleController::class, 'availableUsers'])->name('available-users');
            Route::post('{role}/assign-users', [RoleController::class, 'assignUsers'])
                ->middleware('permission:assign_roles')
                ->name('assign-users');
            Route::delete('{role}/remove-users', [RoleController::class, 'removeUsers'])
                ->middleware('permission:assign_roles')
                ->name('remove-users');
        });
    });

    // Permission Management
    Route::middleware('permission:view_permissions')->group(function () {
        Route::prefix('permissions')->name('permissions.')->group(function () {
            Route::get('/', [PermissionController::class, 'index'])->name('index');
            Route::get('grouped', [PermissionController::class, 'grouped'])->name('grouped');
            Route::get('statistics', [PermissionController::class, 'statistics'])->name('statistics');
            Route::get('{permission}', [PermissionController::class, 'show'])->name('show');
            Route::get('by-role/{role}', [PermissionController::class, 'byRole'])->name('by-role');
            Route::get('by-user/{user}', [PermissionController::class, 'byUser'])->name('by-user');
        });
    });

    // Level 3.5: Audit Access - Special Security Requirements
    Route::middleware([
        'role:super_admin,system_admin,auditor,school_admin',
        'audit-action:audit_access',
        'time-based-access:business_hours',
    ])->group(function () {
        Route::prefix('audits')->name('audits.')->group(function () {
            Route::get('/', [AuditController::class, 'index'])
                ->middleware('permission:view_audit_trail')
                ->name('index');
            Route::get('dashboard', [AuditController::class, 'dashboard'])
                ->middleware('permission:view_audit_dashboard')
                ->name('dashboard');
            Route::get('{audit}', [AuditController::class, 'show'])
                ->middleware('permission:view_audit_trail')
                ->name('show');
            Route::get('timeline/{modelType}/{modelId}', [AuditController::class, 'timeline'])
                ->middleware('permission:view_audit_trail')
                ->name('timeline');
            Route::get('user/{userId}', [AuditController::class, 'forUser'])
                ->middleware('permission:view_audit_trail')
                ->name('for-user');
        });
    });
});

// Level 4: Super Administrative Access - Maximum Security
Route::middleware([
    'auth',
    'verified',
    'role:super_admin',
    'ip-restricted:strict',
    'time-based-access:business_hours',
    'throttle:50,60',
    'audit-action:super_admin_access',
])->group(function () {
    // System Configuration
    Route::get('admin/system-config', function () {
        return Inertia::render('Admin/SystemConfig');
    })->name('admin.system.config');

    // Emergency Controls
    Route::prefix('admin/emergency')->name('admin.emergency.')->group(function () {
        Route::get('controls', function () {
            return Inertia::render('Admin/EmergencyControls');
        })->name('controls');

        Route::post('maintenance-mode', function () {
            // Toggle maintenance mode
        })->middleware('throttle:2,1')->name('maintenance.toggle');

        Route::post('emergency-shutdown', function () {
            // Emergency system shutdown
        })->middleware('throttle:1,60')->name('shutdown');
    });

    // Bulk Administration Tools
    Route::prefix('admin/bulk')->name('admin.bulk.')
        ->middleware(['audit-action:bulk_admin_operation', 'throttle:10,60'])
        ->group(function () {
            Route::get('operations', function () {
                return Inertia::render('Admin/BulkOperations');
            })->name('operations');

            Route::post('users/mass-update', function () {
                // Mass user updates
            })->name('users.mass_update');

            Route::post('schools/mass-import', function () {
                // Mass school import
            })->name('schools.mass_import');

            Route::post('permissions/rebuild', function () {
                // Rebuild permission cache
            })->name('permissions.rebuild');
        });

    // Security Management
    Route::prefix('admin/security')->name('admin.security.')
        ->middleware(['audit-action:security_management'])
        ->group(function () {
            Route::get('dashboard', function () {
                return Inertia::render('Admin/SecurityDashboard');
            })->name('dashboard');

            Route::get('failed-logins', function () {
                return Inertia::render('Admin/FailedLogins');
            })->name('failed_logins');

            Route::get('suspicious-activity', function () {
                return Inertia::render('Admin/SuspiciousActivity');
            })->name('suspicious_activity');

            Route::post('lockdown', function () {
                // Emergency system lockdown
            })->middleware('throttle:1,60')->name('lockdown');
        });
});

// Level 5: Emergency-Only Access (Bypasses normal restrictions)
Route::middleware([
    'auth',
    'role:super_admin',
    'audit-action:emergency_access',
])->group(function () {
    Route::post('emergency/unlock-user/{user}', function ($user) {
        // Emergency user unlock
    })->middleware('throttle:5,1')->name('emergency.unlock.user');

    Route::post('emergency/bypass-2fa/{user}', function ($user) {
        // Emergency 2FA bypass
    })->middleware('throttle:3,1')->name('emergency.bypass.2fa');

    Route::post('emergency/reset-permissions', function () {
        // Emergency permission reset
    })->middleware('throttle:1,60')->name('emergency.reset.permissions');
});

require __DIR__.'/settings.php';
require __DIR__.'/products.php';
require __DIR__.'/auth.php';
