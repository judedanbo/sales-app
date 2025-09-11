<?php

namespace Tests\Feature;

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use OwenIt\Auditing\Models\Audit;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdministratorAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    private User $testUser;

    private Role $testRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Create system_admin user
        $this->admin = User::factory()->create();
        $this->admin->assignRole('system_admin');

        // Create test data for parameterized routes
        $this->school = School::factory()->create();
        $this->testUser = User::factory()->create();
        $this->testRole = Role::findByName('staff');

        // Create some audit records
        Audit::create([
            'auditable_type' => User::class,
            'auditable_id' => $this->testUser->id,
            'event' => 'created',
            'user_id' => $this->admin->id,
            'user_type' => User::class,
            'old_values' => [],
            'new_values' => ['name' => $this->testUser->name],
            'url' => 'http://example.com',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
        ]);
    }

    /**
     * Test that administrator can access all basic authenticated routes
     */
    public function test_administrator_can_access_basic_routes()
    {
        $routes = [
            ['uri' => '/dashboard', 'component' => 'Dashboard'],
            ['uri' => '/docs', 'component' => 'Documentation'],
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->admin)->get($route['uri']);

            $response->assertStatus(200);

            if ($route['component']) {
                $response->assertInertia(fn (AssertableInertia $page) => $page->component($route['component'])
                );
            }
        }
    }

    /**
     * Test that administrator can access all school management routes
     */
    public function test_administrator_can_access_school_routes()
    {
        // Test basic school routes that don't involve cascade middleware
        $routes = [
            // School main routes
            ['uri' => '/schools', 'component' => 'Schools/Index'],
            ['uri' => '/schools/dashboard', 'component' => 'Schools/Dashboard'],
            ['uri' => '/schools/form-data', 'component' => null], // Returns JSON
            ['uri' => "/schools/{$this->school->id}", 'component' => 'Schools/Show'],
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->admin)->get($route['uri']);

            $response->assertStatus(200);

            if ($route['component']) {
                $response->assertInertia(fn (AssertableInertia $page) => $page->component($route['component'])
                );
            }
        }
    }

    /**
     * Test that administrator can access all user management routes
     */
    public function test_administrator_can_access_user_management_routes()
    {
        $routes = [
            ['uri' => '/users', 'component' => 'Users/Index'],
            ['uri' => '/users/create', 'component' => 'Users/Create'],
            ['uri' => "/users/{$this->testUser->id}", 'component' => 'Users/Show'],
            ['uri' => "/users/{$this->testUser->id}/edit", 'component' => 'Users/Edit'],
            ['uri' => "/users/{$this->testUser->id}/roles", 'component' => 'Users/Roles'],
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->admin)->get($route['uri']);

            $response->assertStatus(200);

            if ($route['component']) {
                $response->assertInertia(fn (AssertableInertia $page) => $page->component($route['component'])
                );
            }
        }
    }

    /**
     * Test that administrator can access all role management routes
     */
    public function test_administrator_can_access_role_management_routes()
    {
        $routes = [
            ['uri' => '/roles', 'component' => 'Roles/Index'],
            ['uri' => '/roles/create', 'component' => 'Roles/Create'],
            ['uri' => "/roles/{$this->testRole->id}", 'component' => 'Roles/Show'],
            ['uri' => "/roles/{$this->testRole->id}/edit", 'component' => 'Roles/Edit'],
            ['uri' => '/roles/all-permissions', 'component' => null], // Returns JSON
            ['uri' => "/roles/{$this->testRole->id}/permissions", 'component' => 'Roles/Permissions'],
            ['uri' => "/roles/{$this->testRole->id}/users", 'component' => 'Roles/Users'],
            ['uri' => "/roles/{$this->testRole->id}/available-users", 'component' => null], // Returns JSON
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->admin)->get($route['uri']);

            $response->assertStatus(200);

            if ($route['component']) {
                $response->assertInertia(fn (AssertableInertia $page) => $page->component($route['component'])
                );
            }
        }
    }

    /**
     * Test that administrator can access all permission management routes
     */
    public function test_administrator_can_access_permission_management_routes()
    {
        $permission = Permission::first();

        $routes = [
            ['uri' => '/permissions', 'component' => 'Permissions/Index'],
            ['uri' => '/permissions/grouped', 'component' => null], // Returns JSON
            ['uri' => '/permissions/statistics', 'component' => null], // Returns JSON or different component
            ['uri' => "/permissions/{$permission->id}", 'component' => 'Permissions/Show'],
            ['uri' => "/permissions/by-role/{$this->testRole->id}", 'component' => null], // Returns JSON
            ['uri' => "/permissions/by-user/{$this->testUser->id}", 'component' => null], // Returns JSON
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->admin)->get($route['uri']);

            $response->assertStatus(200);

            if ($route['component']) {
                $response->assertInertia(fn (AssertableInertia $page) => $page->component($route['component'])
                );
            }
        }
    }

    /**
     * Test that administrator can access all audit routes
     */
    public function test_administrator_can_access_audit_routes()
    {
        $audit = Audit::first();

        $routes = [
            ['uri' => '/audits', 'component' => 'Audits/Index'],
            ['uri' => '/audits/dashboard', 'component' => 'Audits/Dashboard'],
            ['uri' => "/audits/{$audit->id}", 'component' => 'Audits/Show'],
            ['uri' => "/audits/timeline/User/{$this->testUser->id}", 'component' => 'Audits/Timeline'],
            ['uri' => "/audits/user/{$this->testUser->id}", 'component' => 'Audits/UserAudits'],
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->admin)->get($route['uri']);

            $response->assertStatus(200);

            if ($route['component']) {
                $response->assertInertia(fn (AssertableInertia $page) => $page->component($route['component'])
                );
            }
        }
    }

    /**
     * Test that administrator can access settings routes
     */
    public function test_administrator_can_access_settings_routes()
    {
        $routes = [
            ['uri' => '/settings/profile', 'component' => 'settings/Profile'],
            ['uri' => '/settings/password', 'component' => 'settings/Password'],
            ['uri' => '/settings/appearance', 'component' => 'settings/Appearance'],
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->admin)->get($route['uri']);

            $response->assertStatus(200);

            if ($route['component']) {
                $response->assertInertia(fn (AssertableInertia $page) => $page->component($route['component'])
                );
            }
        }
    }

    /**
     * Test that administrator can perform write operations
     */
    public function test_administrator_can_perform_write_operations()
    {
        // Test creating a new user
        $userData = User::factory()->make()->toArray();
        $userData['password'] = 'password';
        $userData['password_confirmation'] = 'password';
        $response = $this->actingAs($this->admin)->post('/users', $userData);
        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => $userData['email']]);

        // Test updating a user
        $updateData = ['name' => 'Updated User Name', 'email' => $this->testUser->email];
        $response = $this->actingAs($this->admin)->put("/users/{$this->testUser->id}", $updateData);
        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $this->testUser->id, 'name' => 'Updated User Name']);

        // Test deleting a user
        $userToDelete = User::factory()->create();
        $response = $this->actingAs($this->admin)->delete("/users/{$userToDelete->id}");
        $response->assertRedirect();
        $this->assertSoftDeleted('users', ['id' => $userToDelete->id]);
    }

    /**
     * Test that administrator can access API endpoints
     */
    public function test_administrator_can_access_api_endpoints()
    {
        // Test school API endpoints
        $response = $this->actingAs($this->admin)->getJson('/api/schools');
        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'links', 'meta']);

        // Test user API endpoints
        $response = $this->actingAs($this->admin)->getJson('/api/users');
        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'links', 'meta']);

        // Test role API endpoints
        $response = $this->actingAs($this->admin)->getJson('/api/roles');
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);

        // Test permission API endpoints
        $response = $this->actingAs($this->admin)->getJson('/api/permissions');
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);

        // Test audit API endpoints
        $response = $this->actingAs($this->admin)->getJson('/api/audits');
        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    /**
     * Test that administrator bypasses time-based access restrictions
     */
    public function test_administrator_bypasses_restrictions()
    {
        // This would normally fail for non-admin users outside business hours
        // but should work for administrators
        $response = $this->actingAs($this->admin)->get('/users');
        $response->assertStatus(200);

        // Test that admin can access system-user restricted routes
        $response = $this->actingAs($this->admin)->get('/roles');
        $response->assertStatus(200);
    }

    /**
     * Test comprehensive route access using dataset
     */
    public function test_administrator_has_comprehensive_access()
    {
        $routesToTest = [
            // Basic routes
            '/dashboard' => 200,
            '/' => 200,

            // School routes
            '/schools' => 200,
            '/schools/dashboard' => 200,
            "/schools/{$this->school->id}" => 200,

            // User routes
            '/users' => 200,
            '/users/create' => 200,
            "/users/{$this->testUser->id}" => 200,
            "/users/{$this->testUser->id}/edit" => 200,

            // Role routes
            '/roles' => 200,
            '/roles/create' => 200,
            "/roles/{$this->testRole->id}" => 200,

            // Permission routes
            '/permissions' => 200,
            '/permissions/statistics' => 200,

            // Audit routes
            '/audits' => 200,
            '/audits/dashboard' => 200,

            // Settings routes
            '/settings/profile' => 200,
            '/settings/password' => 200,
            '/settings/appearance' => 200,
        ];

        foreach ($routesToTest as $route => $expectedStatus) {
            $response = $this->actingAs($this->admin)->get($route);
            $response->assertStatus($expectedStatus);
        }
    }

    /**
     * Test that non-admin users cannot access admin routes
     */
    public function test_non_admin_users_cannot_access_admin_routes()
    {
        $regularUser = User::factory()->create();
        $regularUser->assignRole('staff');

        $protectedRoutes = [
            '/users',
            '/roles',
            '/permissions',
            '/audits/dashboard',
        ];

        foreach ($protectedRoutes as $route) {
            $response = $this->actingAs($regularUser)->get($route);
            $response->assertStatus(403);
        }
    }
}
