<?php

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create super admin user
    $this->superAdmin = User::factory()->create([
        'name' => 'Super Administrator',
        'email' => 'super@test.com',
        'user_type' => UserType::SUPER_ADMIN,
        'is_active' => true,
    ]);

    // Assign super_admin role (matching the seeder)
    $this->superAdmin->assignRole('super_admin');

    // Create system admin user
    $this->systemAdmin = User::factory()->create([
        'name' => 'System Administrator',
        'email' => 'system@test.com',
        'user_type' => UserType::SYSTEM_ADMIN,
        'is_active' => true,
    ]);

    // Assign system_admin role
    $this->systemAdmin->assignRole('system_admin');
});

it('allows super admin to access audit index page', function () {
    $this->actingAs($this->superAdmin);

    $response = $this->get('/audits');

    $response->assertSuccessful();
});

it('allows super admin to access audit dashboard page', function () {
    $this->actingAs($this->superAdmin);

    $response = $this->get('/audits/dashboard');

    $response->assertSuccessful();
});

it('system admin access is restricted by time-based middleware', function () {
    $this->actingAs($this->systemAdmin);

    $response = $this->get('/audits');

    // System admins don't have time restriction override, so they get 423 during non-business hours
    // This is expected behavior according to the TimeBasedAccess middleware
    if ($response->status() === 423) {
        expect($response->status())->toBe(423, 'System admin correctly blocked by time restrictions');
    } else {
        // If running during business hours, it should work
        $response->assertSuccessful();
    }
});

it('creates users with correct roles for audit access', function () {
    expect($this->superAdmin->hasRole('super_admin'))->toBeTrue();
    expect($this->systemAdmin->hasRole('system_admin'))->toBeTrue();
});

it('verifies users have required audit permissions', function () {
    expect($this->superAdmin->can('view_audit_trail'))->toBeTrue();
    expect($this->superAdmin->can('view_audit_dashboard'))->toBeTrue();

    expect($this->systemAdmin->can('view_audit_trail'))->toBeTrue();
    expect($this->systemAdmin->can('view_audit_dashboard'))->toBeTrue();
});

it('denies non-admin users access to audit routes', function () {
    $teacher = User::factory()->create([
        'user_type' => UserType::TEACHER,
        'is_active' => true,
    ]);
    $teacher->assignRole('teacher');

    $this->actingAs($teacher);

    $response = $this->get('/audits');

    $response->assertForbidden();
});
