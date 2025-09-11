<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AdministratorAccessSuccessTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create system_admin user
        $this->admin = User::factory()->create();
        $this->admin->assignRole('system_admin');
    }

    /**
     * Test that administrator can access dashboard and basic routes
     */
    public function test_administrator_can_access_dashboard_and_basic_routes()
    {
        $response = $this->actingAs($this->admin)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page->component('Dashboard')
        );

        $response = $this->actingAs($this->admin)->get('/');
        $response->assertStatus(200);
    }

    /**
     * Test that administrator can access documentation
     */
    public function test_administrator_can_access_documentation()
    {
        $response = $this->actingAs($this->admin)->get('/docs');
        $response->assertStatus(200);
    }

    /**
     * Test that administrator can access settings pages
     */
    public function test_administrator_can_access_all_settings_pages()
    {
        // Profile settings
        $response = $this->actingAs($this->admin)->get('/settings/profile');
        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page->component('settings/Profile')
        );

        // Password settings
        $response = $this->actingAs($this->admin)->get('/settings/password');
        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page->component('settings/Password')
        );

        // Appearance settings
        $response = $this->actingAs($this->admin)->get('/settings/appearance');
        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page->component('settings/Appearance')
        );
    }

    /**
     * Test that administrator can access permission pages
     */
    public function test_administrator_can_access_permission_pages()
    {
        $response = $this->actingAs($this->admin)->get('/permissions');
        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page->component('Permissions/Index')
        );
    }

    /**
     * Test that administrator can access school listing
     */
    public function test_administrator_can_access_school_listing()
    {
        $response = $this->actingAs($this->admin)->get('/schools');
        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page->component('Schools/Index')
        );

        $response = $this->actingAs($this->admin)->get('/schools/dashboard');
        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page->component('Schools/Dashboard')
        );
    }

    /**
     * Test that administrator can view individual school
     */
    public function test_administrator_can_view_individual_school()
    {
        $school = School::factory()->create();

        $response = $this->actingAs($this->admin)->get("/schools/{$school->id}");
        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page->component('Schools/Show')
            ->has('school')
        );
    }

    /**
     * Test that regular users cannot access admin routes
     */
    public function test_regular_users_cannot_access_admin_routes()
    {
        $regularUser = User::factory()->create();
        $regularUser->assignRole('staff');

        // Test that staff cannot access user management
        $response = $this->actingAs($regularUser)->get('/users');
        $response->assertStatus(403);

        // Test that staff cannot access role management
        $response = $this->actingAs($regularUser)->get('/roles');
        $response->assertStatus(403);

        // Test that staff cannot access audit dashboard
        $response = $this->actingAs($regularUser)->get('/audits/dashboard');
        $response->assertStatus(403);
    }

    /**
     * Test that unauthenticated users are redirected to login
     */
    public function test_unauthenticated_users_are_redirected_to_login()
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');

        $response = $this->get('/users');
        $response->assertRedirect('/login');

        $response = $this->get('/schools');
        $response->assertRedirect('/login');
    }

    /**
     * Test comprehensive administrator route access
     */
    public function test_administrator_has_access_to_key_admin_routes()
    {
        // Routes that system_admin should be able to access
        $accessibleRoutes = [
            '/dashboard' => 200,
            '/docs' => 200,
            '/settings/profile' => 200,
            '/settings/password' => 200,
            '/settings/appearance' => 200,
            '/schools' => 200,
            '/schools/dashboard' => 200,
            '/permissions' => 200,
        ];

        foreach ($accessibleRoutes as $route => $expectedStatus) {
            $response = $this->actingAs($this->admin)->get($route);
            $response->assertStatus($expectedStatus, "Failed to access route: {$route}");
        }
    }
}
