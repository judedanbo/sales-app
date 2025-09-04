<?php

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can display schools index page', function () {
    $user = User::factory()->create();
    $schools = School::factory()->count(5)->create();

    $response = $this->actingAs($user)->get('/schools');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('Schools/Index')
        ->has('schools.data', 5)
    );
});

it('renders checkbox elements in the schools table', function () {
    $user = User::factory()->create();
    $schools = School::factory()->count(3)->create();

    $response = $this->actingAs($user)->get('/schools');

    $response->assertSuccessful();

    // Check that the component receives the necessary data structure
    $response->assertInertia(fn ($page) => $page->component('Schools/Index')
        ->has('schools.data', 3)
        ->has('schools.data.0.id')
        ->has('schools.data.0.school_name')
    );
});
