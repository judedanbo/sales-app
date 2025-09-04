<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can access documentation page when authenticated', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/docs');

    $response->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->component('Documentation')
            ->has('markdownContent')
            ->has('tableOfContents')
            ->has('lastModified')
        );
});

it('redirects to login when not authenticated', function () {
    $response = $this->get('/docs');

    $response->assertRedirect('/login');
});

it('documentation contains expected content', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/docs');

    $response->assertStatus(200);

    $props = $response->inertiaProps();

    expect($props['markdownContent'])->toContain('Sales Application User Manual');
    expect($props['markdownContent'])->toContain('Getting Started');
    expect($props['markdownContent'])->toContain('Dashboard');
    expect($props['markdownContent'])->toContain('Schools Management');
    expect($props['tableOfContents'])->toBeArray();
    expect($props['lastModified'])->toBeNumeric();
});

it('table of contents is properly generated', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/docs');

    $response->assertStatus(200);

    $props = $response->inertiaProps();
    $toc = $props['tableOfContents'];

    // Check that TOC contains expected sections
    $tocTitles = collect($toc)->pluck('title')->toArray();

    expect($tocTitles)->toContain('Getting Started');
    expect($tocTitles)->toContain('Dashboard');
    expect($tocTitles)->toContain('Schools Management');
    expect($tocTitles)->toContain('Navigation');
    expect($tocTitles)->toContain('Settings');

    // Check that each TOC item has required properties
    foreach ($toc as $item) {
        expect($item)->toHaveKeys(['level', 'title', 'anchor']);
        expect($item['level'])->toBeInt();
        expect($item['title'])->toBeString();
        expect($item['anchor'])->toBeString();
    }
});
