<?php

use App\Enums\SchoolType;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('updates a school via regular request and redirects to show page', function () {
    $user = User::factory()->create();
    $school = School::factory()->create([
        'school_name' => 'Original School',
        'school_code' => 'OS001',
        'school_type' => SchoolType::PRIMARY->value,
    ]);

    $updateData = [
        'school_name' => 'Updated School Name',
        'school_code' => 'US001',
        'school_type' => SchoolType::SECONDARY->value,
        'board_affiliation' => null,
        'established_date' => null,
        'website' => null,
        'description' => null,
        'status' => 'active',
    ];

    $response = $this->actingAs($user)
        ->put("/schools/{$school->id}", $updateData);

    // Regular requests should redirect to show page
    $response->assertStatus(302);
    $response->assertRedirect("/schools/{$school->id}");

    // Verify school was updated
    $this->assertDatabaseHas('schools', [
        'id' => $school->id,
        'school_name' => 'Updated School Name',
        'school_code' => 'US001',
        'school_type' => SchoolType::SECONDARY->value,
    ]);
});

it('handles modal edit with Inertia request headers and redirects to index', function () {
    $user = User::factory()->create();
    $school = School::factory()->create([
        'school_name' => 'Modal Edit School',
        'school_code' => 'MES001',
        'school_type' => SchoolType::PRIMARY->value,
    ]);

    $updateData = [
        'school_name' => 'Modal Updated School',
        'school_code' => 'MUS001',
        'school_type' => SchoolType::K12->value,
        'board_affiliation' => null,
        'established_date' => null,
        'website' => null,
        'description' => null,
        'status' => 'inactive',
    ];

    $response = $this->actingAs($user)
        ->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => '1.0.0',
        ])
        ->put("/schools/{$school->id}", $updateData);

    // With Inertia headers, should redirect to index page for modal handling
    $response->assertStatus(303); // Laravel uses 303 for PUT redirects
    $response->assertRedirect('/schools'); // Should redirect to index, not show page

    // Verify school was updated
    $this->assertDatabaseHas('schools', [
        'id' => $school->id,
        'school_name' => 'Modal Updated School',
        'school_code' => 'MUS001',
        'school_type' => SchoolType::K12->value,
        'status' => 'inactive',
    ]);
});

it('sets success message after school update', function () {
    $user = User::factory()->create();
    $school = School::factory()->create([
        'school_name' => 'Success Message School',
        'school_code' => 'SMS001',
    ]);

    $updateData = [
        'school_name' => 'Updated Success School',
        'school_code' => 'USS001',
        'school_type' => SchoolType::HIGHER_SECONDARY->value,
        'board_affiliation' => null,
        'established_date' => null,
        'website' => null,
        'description' => null,
        'status' => 'active',
    ];

    $response = $this->actingAs($user)
        ->withHeaders(['X-Inertia' => 'true'])
        ->put("/schools/{$school->id}", $updateData);

    $response->assertStatus(303)
        ->assertRedirect('/schools')
        ->assertSessionHas('success', 'School updated successfully.');
});

it('validates required fields during update', function () {
    $user = User::factory()->create();
    $school = School::factory()->create();

    $invalidData = [
        'school_name' => '', // Required field empty
        'school_code' => '', // Required field empty
        'school_type' => 'invalid_type',
        'status' => 'active',
    ];

    $response = $this->actingAs($user)
        ->withHeaders(['X-Inertia' => 'true'])
        ->put("/schools/{$school->id}", $invalidData);

    $response->assertStatus(303); // Redirects back with errors
    $response->assertSessionHasErrors(['school_name', 'school_code', 'school_type']);
});

it('validates unique school code during update excluding current school', function () {
    $user = User::factory()->create();

    // Create two schools
    $school1 = School::factory()->create(['school_code' => 'EXISTING001']);
    $school2 = School::factory()->create(['school_code' => 'CURRENT001']);

    // Try to update school2 to use school1's code
    $updateData = [
        'school_name' => 'Updated School',
        'school_code' => 'EXISTING001', // Should fail - already exists
        'school_type' => SchoolType::PRIMARY->value,
        'board_affiliation' => null,
        'established_date' => null,
        'website' => null,
        'description' => null,
        'status' => 'active',
    ];

    $response = $this->actingAs($user)
        ->withHeaders(['X-Inertia' => 'true'])
        ->put("/schools/{$school2->id}", $updateData);

    $response->assertStatus(303);
    $response->assertSessionHasErrors('school_code');
});

it('allows keeping the same school code during update', function () {
    $user = User::factory()->create();
    $school = School::factory()->create([
        'school_name' => 'Keep Code School',
        'school_code' => 'KCS001',
    ]);

    $updateData = [
        'school_name' => 'Updated Keep Code School',
        'school_code' => 'KCS001', // Same code should be allowed
        'school_type' => SchoolType::PRIMARY->value,
        'board_affiliation' => null,
        'established_date' => null,
        'website' => null,
        'description' => null,
        'status' => 'active',
    ];

    $response = $this->actingAs($user)
        ->withHeaders(['X-Inertia' => 'true'])
        ->put("/schools/{$school->id}", $updateData);

    $response->assertStatus(303);
    $response->assertRedirect('/schools');
    $response->assertSessionHasNoErrors();

    // Verify update was successful
    $this->assertDatabaseHas('schools', [
        'id' => $school->id,
        'school_name' => 'Updated Keep Code School',
        'school_code' => 'KCS001',
    ]);
});
