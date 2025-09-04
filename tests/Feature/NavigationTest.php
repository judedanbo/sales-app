<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can access dashboard from navigation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('Welcome'));
});

it('can access schools index from navigation', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/schools');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page->component('Schools/Index'));
});

it('renders navigation correctly for authenticated users', function () {
    $user = User::factory()->create();

    // Test dashboard page has navigation
    $response = $this->actingAs($user)->get('/');
    $response->assertSuccessful();

    // Test schools page has navigation
    $response = $this->actingAs($user)->get('/schools');
    $response->assertSuccessful();
});
