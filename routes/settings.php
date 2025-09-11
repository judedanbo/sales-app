<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Enhanced Settings Routes with Comprehensive Protection
Route::middleware(['auth', 'verified'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    // Profile Management Routes
    Route::middleware(['owner-or-permission:edit_profile,user', 'audit-action:profile_access'])->group(function () {
        Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');

        Route::patch('settings/profile', [ProfileController::class, 'update'])
            ->middleware(['throttle:5,1', 'audit-action:profile_update'])
            ->name('profile.update');

        Route::delete('settings/profile', [ProfileController::class, 'destroy'])
            ->middleware([
                'permission:delete_own_account',
                'throttle:1,60',
                'audit-action:account_deletion',
                'time-based-access:business_hours',
            ])
            ->name('profile.destroy');
    });

    // Password Management Routes
    Route::middleware(['audit-action:password_access'])->group(function () {
        Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');

        Route::put('settings/password', [PasswordController::class, 'update'])
            ->middleware([
                'throttle:3,1',
                'audit-action:password_change',
                'time-based-access:extended_hours',
            ])
            ->name('password.update');
    });

    // Appearance Settings (No sensitive operations)
    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance');

    // Security Settings (Admin Only)
    Route::middleware(['system-user', 'permission:manage_security_settings'])->group(function () {
        Route::get('settings/security', function () {
            return Inertia::render('settings/Security');
        })->name('security.settings');

        Route::post('settings/security/two-factor', function () {
            // Two-factor authentication setup
        })->middleware(['audit-action:2fa_setup', 'throttle:3,1'])->name('security.2fa.setup');

        Route::delete('settings/security/two-factor', function () {
            // Two-factor authentication disable
        })->middleware(['audit-action:2fa_disable', 'throttle:2,1'])->name('security.2fa.disable');
    });

    // IP Whitelist Management (Super Admin Only)
    Route::middleware([
        'role:Super Admin',
        'ip-restricted:strict',
        'time-based-access:business_hours',
        'audit-action:ip_management',
    ])->group(function () {
        Route::get('settings/ip-whitelist', function () {
            return Inertia::render('settings/IPWhitelist');
        })->name('ip.whitelist');

        Route::post('settings/ip-whitelist', function () {
            // Add IP to whitelist
        })->middleware('throttle:10,1')->name('ip.whitelist.add');

        Route::delete('settings/ip-whitelist/{ip}', function () {
            // Remove IP from whitelist
        })->middleware('throttle:10,1')->name('ip.whitelist.remove');
    });

    // Emergency Access Controls (Super Admin Only)
    Route::middleware([
        'role:Super Admin',
        'ip-restricted:office_only',
        'audit-action:emergency_controls',
    ])->group(function () {
        Route::post('settings/emergency/time-override/{userId}', function ($userId) {
            // Grant time-based override
        })->middleware('throttle:5,1')->name('emergency.time.override');

        Route::post('settings/emergency/ip-bypass/{userId}', function ($userId) {
            // Grant IP bypass
        })->middleware('throttle:3,1')->name('emergency.ip.bypass');

        Route::post('settings/emergency/maintenance-mode', function () {
            // Toggle maintenance mode
        })->middleware('throttle:2,1')->name('emergency.maintenance');
    });
});
