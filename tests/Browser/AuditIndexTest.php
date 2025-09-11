<?php

use App\Models\User;

it('can view audit index page', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/audits');

    $page->assertSee('Audits')
        ->assertNoJavascriptErrors()
        ->assertPresent('[data-testid="audit-filters"]')
        ->assertPresent('[data-testid="audit-table"]');
});

it('can filter audits by event type', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/audits');

    $page->waitFor('[data-testid="event-filter"]')
        ->click('[data-testid="event-filter"]')
        ->waitFor('[role="option"]')
        ->click('[role="option"]:first-child')
        ->assertNoJavascriptErrors();
});

it('can search audits', function () {
    $this->actingAs(User::factory()->create());

    $page = visit('/audits');

    $page->type('[data-testid="search-input"]', 'test search')
        ->pause(500) // Wait for debounce
        ->assertNoJavascriptErrors();
});
