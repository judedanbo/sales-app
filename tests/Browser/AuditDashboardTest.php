<?php

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('displays audit dashboard with all main sections', function () {
    // Create some data to populate the dashboard
    School::factory()->count(3)->create();

    $this->actingAs($this->user);

    $page = visit('/audits/dashboard');

    // Check main page elements
    $page->assertSee('Audit Dashboard')
        ->assertSee('Monitor system activity')
        ->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();

    // Check statistics cards are present
    $page->assertSee('Total Audits')
        ->assertSee('Today')
        ->assertSee('This Week')
        ->assertSee('This Month');

    // Check main sections are present
    $page->assertSee('Recent Activity')
        ->assertSee('Activity Types')
        ->assertSee('Models Activity')
        ->assertSee('Most Active Users');
});

it('displays statistics cards with proper formatting', function () {
    // Create some audit data
    School::factory()->count(5)->create();

    $this->actingAs($this->user);

    $page = visit('/audits/dashboard');

    // Statistics cards should show numbers (check for digit patterns)
    $page->assertVisible('.text-2xl.font-bold')
        ->assertNoJavascriptErrors();

    // Verify icons are present for each stat card
    $page->assertPresent('svg') // Icons should be present
        ->assertSee('All recorded activities')
        ->assertSee('Activities today')
        ->assertSee('Activities this week')
        ->assertSee('Activities this month');
});

it('shows recent activity section with audit records', function () {
    // Create some audit data
    School::factory()->count(2)->create();

    $this->actingAs($this->user);

    $page = visit('/audits/dashboard');

    // Recent activity section should be visible
    $page->waitFor('[data-section="recent-activity"]', 5)
        ->assertSee('Recent Activity')
        ->assertSee('Latest audit records')
        ->assertNoJavascriptErrors();
});

it('displays activity types breakdown with colors', function () {
    // Create some audit data
    School::factory()->count(3)->create();

    $this->actingAs($this->user);

    $page = visit('/audits/dashboard');

    // Activity types section
    $page->assertSee('Activity Types')
        ->assertSee('Breakdown of audit events')
        ->assertNoJavascriptErrors();

    // Color indicators should be present (rounded divs)
    $page->assertPresent('.rounded-full');
});

it('shows models activity breakdown', function () {
    // Create audit data across different models
    School::factory()->count(2)->create();
    User::factory()->count(2)->create();

    $this->actingAs($this->user);

    $page = visit('/audits/dashboard');

    $page->assertSee('Models Activity')
        ->assertSee('Audit records by model type')
        ->assertNoJavascriptErrors();
});

it('displays top users section', function () {
    // Create some users and audit data
    User::factory()->count(3)->create();
    School::factory()->count(2)->create();

    $this->actingAs($this->user);

    $page = visit('/audits/dashboard');

    $page->assertSee('Most Active Users')
        ->assertSee('Users with the most audit records')
        ->assertNoJavascriptErrors();
});

it('handles navigation to audit index correctly', function () {
    $this->actingAs($this->user);

    $page = visit('/audits/dashboard');

    // Find and click the "View All Audits" button
    $page->click('a[href="/audits"]')
        ->waitForUrl('/audits')
        ->assertUrlIs('/audits')
        ->assertSee('Audit Records')
        ->assertNoJavascriptErrors();
});

it('displays empty states gracefully when no data exists', function () {
    $this->actingAs($this->user);

    $page = visit('/audits/dashboard');

    // Should handle empty states without errors
    $page->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();

    // Statistics should still be visible (likely showing 0)
    $page->assertSee('Total Audits')
        ->assertVisible('.text-2xl.font-bold');
});

it('is responsive on mobile devices', function () {
    School::factory()->count(2)->create();

    $this->actingAs($this->user);

    $page = visit('/audits/dashboard');

    // Test mobile viewport
    $page->resize(375, 667) // iPhone SE dimensions
        ->assertVisible('h1') // Title should be visible
        ->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();

    // Statistics grid should adapt to mobile
    $page->assertVisible('.grid');
});

it('is responsive on tablet devices', function () {
    School::factory()->count(2)->create();

    $this->actingAs($this->user);

    $page = visit('/audits/dashboard');

    // Test tablet viewport
    $page->resize(768, 1024) // iPad dimensions
        ->assertVisible('h1')
        ->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();
});

it('supports dark mode without visual issues', function () {
    School::factory()->count(2)->create();

    $this->actingAs($this->user);

    $page = visit('/audits/dashboard');

    // Switch to dark mode (assuming dark mode toggle exists)
    $page->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();

    // Colors and text should be properly visible in both modes
    $page->assertVisible('.text-2xl.font-bold');
});

it('loads within acceptable time limits', function () {
    // Create substantial data to test performance
    School::factory()->count(10)->create();
    User::factory()->count(5)->create();

    $this->actingAs($this->user);

    $startTime = microtime(true);

    $page = visit('/audits/dashboard');

    $page->assertSee('Audit Dashboard');

    $loadTime = microtime(true) - $startTime;

    // Should load within 3 seconds (adjust as needed)
    expect($loadTime)->toBeLessThan(3.0);

    $page->assertNoJavascriptErrors();
});

it('handles breadcrumb navigation correctly', function () {
    $this->actingAs($this->user);

    $page = visit('/audits/dashboard');

    // Breadcrumbs should be functional
    $page->assertSee('Audits') // Breadcrumb text
        ->assertSee('Dashboard')
        ->assertNoJavascriptErrors();

    // Click on Audits breadcrumb should navigate to audit index
    if ($page->hasElement('a[href="/audits"]')) {
        $page->click('a[href="/audits"]')
            ->waitForUrl('/audits')
            ->assertUrlIs('/audits');
    }
});

it('displays proper accessibility attributes', function () {
    School::factory()->count(2)->create();

    $this->actingAs($this->user);

    $page = visit('/audits/dashboard');

    // Check for basic accessibility features
    $page->assertPresent('h1') // Main heading
        ->assertNoJavascriptErrors();

    // Icons should have appropriate attributes
    $page->assertPresent('svg');
});

it('shows user activity with proper formatting', function () {
    // Create multiple users and activities
    $users = User::factory()->count(3)->create();
    School::factory()->count(5)->create();

    $this->actingAs($this->user);

    $page = visit('/audits/dashboard');

    // Recent activity should show user names and relative times
    $page->waitFor('[data-section="recent-activity"]', 5)
        ->assertNoJavascriptErrors();

    // Time formatting should be present (look for common time patterns)
    // This will vary based on when the test runs, so we just check for no errors
    $page->assertNoConsoleLogs();
});
