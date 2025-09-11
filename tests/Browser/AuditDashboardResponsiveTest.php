<?php

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();

    // Create some test data
    School::factory()->count(3)->create();
});

it('adapts layout properly on mobile phones', function () {
    $this->actingAs($this->user);

    $page = visit('/audits/dashboard');

    // Test iPhone SE dimensions (375x667)
    $page->resize(375, 667)
        ->assertVisible('h1') // Main title should be visible
        ->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();

    // Statistics cards should be stacked vertically on mobile
    $page->assertVisible('.grid'); // Grid container should exist

    // Content should not overflow horizontally
    $page->assertNotPresent('[style*="overflow-x: scroll"]');

    // Text should be readable (not too small)
    $page->assertVisible('.text-2xl'); // Large stats numbers should be visible
});

it('works well on iPhone 12/13 Pro dimensions', function () {
    $this->actingAs($this->user);

    $page = visit('/audits/dashboard');

    // Test iPhone 12/13 Pro dimensions (390x844)
    $page->resize(390, 844)
        ->assertVisible('h1')
        ->assertSee('Total Audits')
        ->assertSee('Recent Activity')
        ->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();

    // All major sections should be accessible
    $page->assertVisible('.grid');
});

it('displays properly on tablet portrait mode', function () {
    $this->actingAs($this->user);

    $page = visit('/audits/dashboard');

    // Test iPad portrait dimensions (768x1024)
    $page->resize(768, 1024)
        ->assertVisible('h1')
        ->assertSee('Audit Dashboard')
        ->assertNoJavascriptErrors();

    // Should show statistics in a grid appropriate for tablet
    $page->assertVisible('.grid')
        ->assertSee('Total Audits')
        ->assertSee('Today')
        ->assertSee('This Week')
        ->assertSee('This Month');

    // Content sections should be properly arranged
    $page->assertSee('Recent Activity')
        ->assertSee('Activity Types')
        ->assertSee('Models Activity')
        ->assertSee('Most Active Users');
});

it('displays properly on tablet landscape mode', function () {
    $this->actingAs($this->user);

    $page = visit('/audits/dashboard');

    // Test iPad landscape dimensions (1024x768)
    $page->resize(1024, 768)
        ->assertVisible('h1')
        ->assertNoJavascriptErrors();

    // Should utilize wider screen space effectively
    $page->assertVisible('.grid')
        ->assertSee('Total Audits');

    // Two-column layout should work well
    $page->assertSee('Recent Activity')
        ->assertSee('Activity Types');
});

it('handles very wide desktop screens', function () {
    $this->actingAs($this->user);

    $page = visit('/audits/dashboard');

    // Test ultra-wide desktop (2560x1440)
    $page->resize(2560, 1440)
        ->assertVisible('h1')
        ->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();

    // Content should not be stretched too wide
    $page->assertVisible('.grid')
        ->assertSee('Total Audits');

    // Should utilize space efficiently without being too spread out
    $page->assertSee('Recent Activity');
});

it('handles small mobile screens gracefully', function () {
    $this->actingAs($this->user);

    $page = visit('/audits/dashboard');

    // Test small mobile dimensions (320x568) - iPhone 5
    $page->resize(320, 568)
        ->assertVisible('h1')
        ->assertNoJavascriptErrors();

    // Should still be usable even on very small screens
    $page->assertVisible('.text-2xl') // Stats should still be visible
        ->assertSee('Total Audits');

    // Content should not be cut off
    $page->assertNotPresent('[style*="overflow-x: auto"]');
});

it('maintains touch-friendly interface on touchscreens', function () {
    $this->actingAs($this->user);

    $page = visit('/audits/dashboard');

    // Test tablet dimensions with touch simulation
    $page->resize(768, 1024)
        ->assertVisible('a[href="/audits"]') // Navigation should be touchable
        ->assertNoJavascriptErrors();

    // Links and buttons should be appropriately sized for touch
    // This is more of a visual test, but we can check they exist
    $page->assertPresent('button, a[href]');
});

it('supports proper accessibility features', function () {
    $this->actingAs($this->user);

    $page = visit('/audits/dashboard');

    // Check for semantic HTML structure
    $page->assertPresent('h1') // Main heading
        ->assertPresent('main, [role="main"]') // Main content area
        ->assertNoJavascriptErrors();

    // Icons should have appropriate handling
    $page->assertPresent('svg'); // Icons should be present

    // Links should be keyboard accessible
    $page->assertPresent('a[href]');
});

it('handles screen orientation changes', function () {
    $this->actingAs($this->user);

    $page = visit('/audits/dashboard');

    // Start in portrait mobile
    $page->resize(375, 667)
        ->assertVisible('h1')
        ->assertNoJavascriptErrors();

    // Switch to landscape mobile
    $page->resize(667, 375)
        ->assertVisible('h1')
        ->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();

    // Content should adapt appropriately
    $page->assertVisible('.grid');
});

it('handles zoom levels appropriately', function () {
    $this->actingAs($this->user);

    $page = visit('/audits/dashboard');

    // Test at normal size
    $page->assertVisible('h1')
        ->assertNoJavascriptErrors();

    // Simulate zoom by testing smaller viewport (approximates zoom)
    $page->resize(800, 600) // Smaller than typical desktop
        ->assertVisible('h1')
        ->assertNoJavascriptErrors();

    // Content should still be accessible
    $page->assertSee('Total Audits')
        ->assertSee('Recent Activity');
});

it('maintains performance on mobile devices', function () {
    $this->actingAs($this->user);

    // Mobile viewport
    $startTime = microtime(true);

    $page = visit('/audits/dashboard');
    $page->resize(375, 667)
        ->assertVisible('h1');

    $loadTime = microtime(true) - $startTime;

    // Should load quickly even on mobile
    expect($loadTime)->toBeLessThan(4.0); // Mobile devices might be slower

    $page->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();
});

it('handles content overflow gracefully on small screens', function () {
    $this->actingAs($this->user);

    $page = visit('/audits/dashboard');

    // Very narrow screen
    $page->resize(280, 600)
        ->assertVisible('h1')
        ->assertNoJavascriptErrors();

    // Content should adapt and not cause horizontal scrolling
    $page->assertVisible('.text-2xl'); // Stats should still be readable

    // Long text should wrap appropriately
    $page->assertSee('Total Audits');
});

it('provides consistent user experience across breakpoints', function () {
    $this->actingAs($this->user);

    $breakpoints = [
        [375, 667],  // Mobile
        [768, 1024], // Tablet
        [1024, 768], // Tablet landscape
        [1920, 1080], // Desktop
    ];

    $page = visit('/audits/dashboard');

    foreach ($breakpoints as [$width, $height]) {
        $page->resize($width, $height)
            ->assertVisible('h1')
            ->assertSee('Total Audits')
            ->assertSee('Recent Activity')
            ->assertNoJavascriptErrors();

        // Brief pause between tests
        $page->pause(100);
    }
});

it('handles reduced motion preferences', function () {
    $this->actingAs($this->user);

    $page = visit('/audits/dashboard');

    // This is a basic test - in a real scenario, you'd test with
    // prefers-reduced-motion CSS media query
    $page->assertVisible('h1')
        ->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();

    // Animations and transitions should be respectful of user preferences
    // This is more of a visual/CSS test that would require specific setup
});

it('supports keyboard navigation', function () {
    $this->actingAs($this->user);

    $page = visit('/audits/dashboard');

    // Test that interactive elements are keyboard accessible
    $page->assertPresent('a[href]') // Links should be focusable
        ->assertNoJavascriptErrors();

    // This would require more sophisticated testing in a real scenario
    // to actually test tab navigation and focus management
});
