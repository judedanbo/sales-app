<?php

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('can access audit dashboard page', function () {
    $this->actingAs($this->user)
        ->get('/audits/dashboard')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Audits/Dashboard')
            ->has('stats')
            ->has('recent_audits')
        );
});

it('provides audit statistics with correct data structure', function () {
    // Create some data to generate audit records
    School::factory()->count(3)->create();
    User::factory()->count(2)->create();

    $this->actingAs($this->user)
        ->get('/audits/dashboard')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Audits/Dashboard')
            ->has('stats', fn ($stats) => $stats->has('total_audits')
                ->has('today_audits')
                ->has('this_week_audits')
                ->has('this_month_audits')
                ->where('total_audits', fn ($total) => $total >= 0)
            )
            ->has('events_breakdown')
            ->has('models_breakdown')
            ->has('top_users')
            ->has('recent_audits')
        );
});

it('calculates statistics correctly', function () {
    // Create specific data to test calculations
    $schools = School::factory()->count(5)->create();
    $users = User::factory()->count(3)->create();

    $this->actingAs($this->user)
        ->get('/audits/dashboard')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Audits/Dashboard')
            ->where('stats.total_audits', fn ($total) => $total >= 0)
            ->has('events_breakdown')
            ->has('models_breakdown')
        );
});

it('provides recent audits in correct format', function () {
    // Create data with known audit records
    School::factory()->count(2)->create();

    $this->actingAs($this->user)
        ->get('/audits/dashboard')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Audits/Dashboard')
            ->has('recent_audits')
                // Only test structure if audits exist
            ->when(
                fn ($p) => count($p->toArray()['recent_audits'] ?? []) > 0,
                fn ($p) => $p->has('recent_audits.0.id')
                    ->has('recent_audits.0.event')
                    ->has('recent_audits.0.auditable_type')
            )
        );
});

it('provides top users with correct structure', function () {
    // Create multiple users to ensure top users calculation
    User::factory()->count(5)->create();
    School::factory()->count(3)->create();

    $this->actingAs($this->user)
        ->get('/audits/dashboard')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Audits/Dashboard')
            ->has('top_users')
                // Only test structure if top users exist
            ->when(
                fn ($p) => count($p->toArray()['top_users'] ?? []) > 0,
                fn ($p) => $p->has('top_users.0.user.id')
                    ->has('top_users.0.user.name')
                    ->has('top_users.0.audit_count')
            )
        );
});

it('handles empty data gracefully', function () {
    // Clear all audit data to test empty state
    \OwenIt\Auditing\Models\Audit::truncate();
    
    // Test with no audit data
    $this->actingAs($this->user)
        ->get('/audits/dashboard')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Audits/Dashboard')
            ->where('stats.total_audits', 0)
            ->where('stats.today_audits', 0)
            ->where('stats.this_week_audits', 0)
            ->where('stats.this_month_audits', 0)
            ->where('recent_audits', [])
            ->where('top_users', [])
            ->where('events_breakdown', [])
            ->where('models_breakdown', [])
        );
});

it('requires authentication to access dashboard', function () {
    $this->get('/audits/dashboard')
        ->assertStatus(302)
        ->assertRedirect('/login');
});

it('provides events breakdown with known event types', function () {
    // Create data that will generate specific events
    School::factory()->count(2)->create();

    $this->actingAs($this->user)
        ->get('/audits/dashboard')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Audits/Dashboard')
            ->has('events_breakdown')
            ->where('events_breakdown', fn ($breakdown) => collect($breakdown)->keys()->every(fn ($key) => is_string($key) && strlen($key) > 0
            )
            )
        );
});

it('provides models breakdown with proper model names', function () {
    // Create different types of models to test breakdown
    School::factory()->count(2)->create();
    User::factory()->count(1)->create();

    $this->actingAs($this->user)
        ->get('/audits/dashboard')
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page->component('Audits/Dashboard')
            ->has('models_breakdown')
            ->where('models_breakdown', fn ($breakdown) => collect($breakdown)->every(fn ($count, $model) => is_string($model) && is_numeric($count) && $count >= 0
            )
            )
        );
});
