<?php

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('can access audit index page', function () {
    $this->actingAs($this->user)
        ->get('/audits')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Audits/Index')
            ->has('audits')
            ->has('filters')
            ->has('filter_options')
        );
});

it('can filter audits by model type', function () {
    // Create some audit data by creating a school
    School::factory()->create();

    $this->actingAs($this->user)
        ->get('/audits?auditable_type=App\\Models\\School')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Audits/Index')
            ->where('filters.auditable_type', 'App\\Models\\School')
        );
});

it('can filter audits by event type', function () {
    $this->actingAs($this->user)
        ->get('/audits?event=created')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Audits/Index')
            ->where('filters.event', 'created')
        );
});

it('can search audits', function () {
    $this->actingAs($this->user)
        ->get('/audits?search=test')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Audits/Index')
            ->where('filters.search', 'test')
        );
});

it('provides filter options', function () {
    $this->actingAs($this->user)
        ->get('/audits')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Audits/Index')
            ->has('filter_options.models')
            ->has('filter_options.events')
        );
});
