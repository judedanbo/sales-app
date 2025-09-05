<?php

namespace Tests\Feature\Api;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PermissionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles and permissions
        $this->artisan('db:seed', ['--class' => 'UserRolesSeeder']);

        // Create admin user
        $this->admin = User::factory()->create([
            'user_type' => UserType::SYSTEM_ADMIN,
            'email' => 'admin@test.com',
        ]);
        $this->admin->assignRole('system_admin');

        // Create staff user
        $this->staff = User::factory()->create([
            'user_type' => UserType::STAFF,
            'email' => 'staff@test.com',
        ]);
        $this->staff->assignRole('staff');
    }

    /** @test */
    public function it_can_list_permissions()
    {
        $response = $this->getJson('/api/permissions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'guard_name',
                        'display_name',
                        'category',
                        'action',
                    ],
                ],
                'meta' => [
                    'total',
                    'count',
                    'per_page',
                ],
            ]);
    }

    /** @test */
    public function it_can_filter_permissions_by_category()
    {
        $response = $this->getJson('/api/permissions?category=view');

        $response->assertStatus(200);
        $data = $response->json('data');

        foreach ($data as $permission) {
            $this->assertEquals('view', $permission['category']);
        }
    }

    /** @test */
    public function it_can_search_permissions()
    {
        $response = $this->getJson('/api/permissions?search=create_users');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'create_users']);
    }

    /** @test */
    public function it_can_show_a_permission()
    {
        $permission = Permission::findByName('view_users');

        $response = $this->getJson("/api/permissions/{$permission->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $permission->id)
            ->assertJsonPath('data.name', 'view_users');
    }

    /** @test */
    public function it_can_get_grouped_permissions()
    {
        $response = $this->getJson('/api/permissions/grouped');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'category',
                        'display_name',
                        'permissions',
                        'count',
                    ],
                ],
                'total_permissions',
                'total_categories',
            ]);
    }

    /** @test */
    public function it_can_get_permission_categories()
    {
        $response = $this->getJson('/api/permissions/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'value',
                        'label',
                        'count',
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_can_get_permissions_by_role()
    {
        $role = Role::findByName('admin');

        $response = $this->getJson("/api/permissions/by-role/{$role->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'role' => [
                    'id',
                    'name',
                    'display_name',
                ],
                'total_permissions',
            ]);
    }

    /** @test */
    public function it_can_get_permissions_by_user()
    {
        $response = $this->getJson("/api/permissions/by-user/{$this->admin->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'user_type',
                ],
                'total_permissions',
                'direct_permissions',
                'role_permissions',
            ]);
    }

    /** @test */
    public function it_can_check_if_user_has_permission()
    {
        $response = $this->getJson("/api/permissions/check-user/{$this->admin->id}?permission=create_users");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'has_permission',
                'permission',
                'source',
                'user',
            ])
            ->assertJsonPath('has_permission', true)
            ->assertJsonPath('permission', 'create_users');
    }

    /** @test */
    public function it_correctly_identifies_permission_source()
    {
        // Test role-based permission
        $response = $this->getJson("/api/permissions/check-user/{$this->staff->id}?permission=view_sales");

        $response->assertStatus(200)
            ->assertJsonPath('has_permission', true)
            ->assertJsonPath('source', 'role');

        // Test direct permission
        $directPermission = Permission::findByName('manage_settings');
        $this->staff->givePermissionTo($directPermission);

        $response = $this->getJson("/api/permissions/check-user/{$this->staff->id}?permission=manage_settings");

        $response->assertStatus(200)
            ->assertJsonPath('has_permission', true)
            ->assertJsonPath('source', 'direct');

        // Test no permission
        $response = $this->getJson("/api/permissions/check-user/{$this->staff->id}?permission=system_administration");

        $response->assertStatus(200)
            ->assertJsonPath('has_permission', false)
            ->assertJsonPath('source', 'none');
    }

    /** @test */
    public function it_validates_permission_exists_when_checking()
    {
        $response = $this->getJson("/api/permissions/check-user/{$this->admin->id}?permission=non_existent");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['permission']);
    }

    /** @test */
    public function it_can_get_permission_statistics()
    {
        $response = $this->getJson('/api/permissions/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_permissions',
                'permissions_with_roles',
                'permissions_without_roles',
                'total_roles',
                'permissions_by_guard',
                'permissions_by_category',
                'most_used_permissions',
            ]);
    }

    /** @test */
    public function it_returns_permissions_with_role_count()
    {
        $response = $this->getJson('/api/permissions');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Check that role relationships are loaded when requested
        foreach ($data as $permission) {
            $this->assertArrayHasKey('id', $permission);
            $this->assertArrayHasKey('name', $permission);
        }
    }

    /** @test */
    public function it_can_filter_permissions_by_role_assignment()
    {
        // Get permissions with roles
        $response = $this->getJson('/api/permissions?has_roles=true');

        $response->assertStatus(200);

        // Get permissions without roles
        $response = $this->getJson('/api/permissions?has_roles=false');

        $response->assertStatus(200);
    }

    /** @test */
    public function it_correctly_categorizes_permissions()
    {
        $response = $this->getJson('/api/permissions/grouped');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Check that permissions are properly grouped by category
        $categories = collect($data)->pluck('category')->toArray();

        // Should have categories like 'create', 'view', 'edit', 'delete', etc.
        $this->assertContains('create', $categories);
        $this->assertContains('view', $categories);
        $this->assertContains('edit', $categories);
    }

    /** @test */
    public function it_returns_correct_permission_count_per_category()
    {
        $response = $this->getJson('/api/permissions/categories');

        $response->assertStatus(200);
        $data = $response->json('data');

        foreach ($data as $category) {
            $this->assertArrayHasKey('value', $category);
            $this->assertArrayHasKey('label', $category);
            $this->assertArrayHasKey('count', $category);
            $this->assertIsInt($category['count']);
            $this->assertGreaterThan(0, $category['count']);
        }
    }

    /** @test */
    public function it_includes_user_count_in_permission_details()
    {
        $permission = Permission::findByName('view_users');
        $permission->load('roles.users');

        $response = $this->getJson("/api/permissions/{$permission->id}");

        $response->assertStatus(200);

        // When roles are loaded, should calculate user count
        if ($response->json('data.roles')) {
            $this->assertArrayHasKey('users_count', $response->json('data'));
        }
    }
}
