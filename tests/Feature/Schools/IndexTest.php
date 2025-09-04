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

it('displays pagination when there are more than 15 schools', function () {
    $user = User::factory()->create();
    // Create 20 schools to trigger pagination (15 per page)
    School::factory()->count(20)->create();

    $response = $this->actingAs($user)->get('/schools');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('Schools/Index')
        ->has('schools.data', 15) // First page should have 15 schools
        ->where('schools.current_page', 1)
        ->where('schools.last_page', 2)
        ->where('schools.total', 20)
        ->has('schools.links') // Ensure links array is present
        ->has('schools.links', 4) // Should have 4 links: Previous, Page 1, Page 2, Next
        ->where('schools.prev_page_url', null) // First page has no previous URL
        ->has('schools.next_page_url') // First page has next URL
    );
});

it('can navigate to second page of schools', function () {
    $user = User::factory()->create();
    School::factory()->count(20)->create();

    $response = $this->actingAs($user)->get('/schools?page=2');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('Schools/Index')
        ->has('schools.data', 5) // Second page should have remaining 5 schools
        ->where('schools.current_page', 2)
        ->where('schools.last_page', 2)
        ->has('schools.prev_page_url') // Second page has previous URL
        ->where('schools.next_page_url', null) // Last page has no next URL
    );
});

it('preserves filters when navigating pages', function () {
    $user = User::factory()->create();
    School::factory()->count(20)->create(['school_type' => 'primary']);
    School::factory()->count(10)->create(['school_type' => 'secondary']);

    $response = $this->actingAs($user)->get('/schools?school_type=primary&page=2');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('Schools/Index')
        ->has('schools.data', 5) // Second page of primary schools
        ->where('schools.current_page', 2)
        ->where('filters.school_type', 'primary') // Filter should be preserved
    );
});

it('has correct pagination links structure', function () {
    $user = User::factory()->create();
    School::factory()->count(25)->create(); // 2 pages with 15 per page, 10 on page 2

    $response = $this->actingAs($user)->get('/schools');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('Schools/Index')
        ->has('schools.links.0', fn ($link) => $link
            ->where('label', '&laquo; Previous')
            ->where('active', false)
            ->where('url', null)
            ->where('page', null)
        )
        ->has('schools.links.1', fn ($link) => $link
            ->where('label', '1')
            ->where('active', true)
            ->where('page', 1)
            ->has('url')
        )
        ->has('schools.links.2', fn ($link) => $link
            ->where('label', '2')
            ->where('active', false)
            ->where('page', 2)
            ->has('url')
        )
        ->has('schools.links.3', fn ($link) => $link
            ->where('label', 'Next &raquo;')
            ->where('active', false)
            ->where('page', 2)
            ->has('url')
        )
    );
});

it('shows no pagination when there are fewer than 15 schools', function () {
    $user = User::factory()->create();
    School::factory()->count(10)->create();

    $response = $this->actingAs($user)->get('/schools');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('Schools/Index')
        ->has('schools.data', 10)
        ->where('schools.current_page', 1)
        ->where('schools.last_page', 1) // Only one page
        ->where('schools.total', 10)
    );
});
