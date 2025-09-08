<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('can fetch available users for a role', function () {
    // Create a test role
    $role = Role::create(['name' => 'test-role']);

    // Create some users
    $userWithRole = User::factory()->create(['name' => 'User With Role']);
    $userWithoutRole1 = User::factory()->create(['name' => 'Available User 1']);
    $userWithoutRole2 = User::factory()->create(['name' => 'Available User 2']);

    // Assign the role to one user
    $userWithRole->assignRole($role);

    // Make authenticated request
    $testUser = User::factory()->create();
    $this->actingAs($testUser);

    // Test the endpoint
    $response = $this->get(route('roles.available-users', $role));

    $response->assertOk();

    $data = $response->json();
    expect($data)->toHaveKey('data');
    expect($data)->toHaveKey('message');
    expect($data['message'])->toBe('Available users retrieved successfully');

    // Should return 2 available users (not including the one with the role)
    expect($data['data'])->toHaveCount(3); // 2 available + the test user

    // Check that the user with role is NOT in the available users
    $availableUserIds = collect($data['data'])->pluck('id')->toArray();
    expect($availableUserIds)->not->toContain($userWithRole->id);

    // Check that users without role ARE in the available users
    expect($availableUserIds)->toContain($userWithoutRole1->id);
    expect($availableUserIds)->toContain($userWithoutRole2->id);
});

it('returns empty list when all users have the role', function () {
    // Create a test role
    $role = Role::create(['name' => 'universal-role']);

    // Create users
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // Assign role to both users
    $user1->assignRole($role);
    $user2->assignRole($role);

    // Make authenticated request (this user will also get the role)
    $testUser = User::factory()->create();
    $testUser->assignRole($role);
    $this->actingAs($testUser);

    // Test the endpoint
    $response = $this->get(route('roles.available-users', $role));

    $response->assertOk();

    $data = $response->json();
    expect($data['data'])->toHaveCount(0);
});

it('requires authentication to access available users', function () {
    $role = Role::create(['name' => 'test-role']);

    $response = $this->get(route('roles.available-users', $role));

    $response->assertRedirect(route('login'));
});
