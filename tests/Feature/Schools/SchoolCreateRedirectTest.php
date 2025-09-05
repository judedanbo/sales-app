<?php

use App\Enums\SchoolType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a school via regular request and redirects to show page', function () {
    $user = User::factory()->create();

    $schoolData = [
        'school_name' => 'Test School',
        'school_code' => 'TS001',
        'school_type' => SchoolType::PRIMARY->value,
        'board_affiliation' => null,
        'established_date' => null,
        'status' => 'active',
    ];

    $response = $this->actingAs($user)
        ->post('/schools', $schoolData);

    // Regular requests should redirect to show page
    $response->assertStatus(302);
    $response->assertRedirectContains('/schools/');

    // Verify school was created
    $this->assertDatabaseHas('schools', [
        'school_name' => 'Test School',
        'school_code' => 'TS001',
    ]);
});

it('handles modal creation with Inertia request headers and redirects to index', function () {
    $user = User::factory()->create();

    $schoolData = [
        'school_name' => 'Modal Test School',
        'school_code' => 'MTS001',
        'school_type' => SchoolType::PRIMARY->value,
        'status' => 'active',
    ];

    $response = $this->actingAs($user)
        ->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => '1.0.0',
        ])
        ->post('/schools', $schoolData);

    // With Inertia headers, should redirect to index page for modal handling
    $response->assertStatus(302);
    $response->assertRedirect('/schools');  // Should redirect to index, not show page

    // Verify school was created
    $this->assertDatabaseHas('schools', [
        'school_name' => 'Modal Test School',
        'school_code' => 'MTS001',
    ]);
});

it('can access form data endpoint', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get('/schools/form-data');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'schoolTypes',
            'boardAffiliations',
            'mediumOfInstructions',
        ]);
});

it('sets success message after school creation', function () {
    $user = User::factory()->create();

    $schoolData = [
        'school_name' => 'Success Test School',
        'school_code' => 'STS001',
        'school_type' => SchoolType::PRIMARY->value,
        'status' => 'active',
    ];

    $response = $this->actingAs($user)
        ->withHeaders(['X-Inertia' => 'true'])
        ->post('/schools', $schoolData);

    $response->assertStatus(302)
        ->assertRedirect('/schools')
        ->assertSessionHas('success', 'School created successfully.');
});
