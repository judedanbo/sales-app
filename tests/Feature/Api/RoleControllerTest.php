<?php

namespace Tests\Feature\Api;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

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
    }

    /** @test */
    public function it_can_list_roles()
    {
        $response = $this->getJson('/api/roles');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'guard_name',
                        'display_name',
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
    public function it_can_filter_roles_by_guard()
    {
        // Create additional role with different guard
        Role::create(['name' => 'api_role', 'guard_name' => 'api']);

        $response = $this->getJson('/api/roles?guard_name=web');

        $response->assertStatus(200);
        $data = $response->json('data');

        foreach ($data as $role) {
            $this->assertEquals('web', $role['guard_name']);
        }
    }

    /** @test */
    public function it_can_search_roles()
    {
        Role::create(['name' => 'special_admin']);

        $response = $this->getJson('/api/roles?search=special');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'special_admin']);
    }

    /** @test */
    public function it_can_create_a_role()
    {
        $roleData = [
            'name' => 'new_role',
            'guard_name' => 'web',
            'permissions' => ['view_users', 'create_users'],
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/roles', $roleData);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'new_role')
            ->assertJsonPath('data.guard_name', 'web');

        $this->assertDatabaseHas('roles', [
            'name' => 'new_role',
            'guard_name' => 'web',
        ]);

        $role = Role::findByName('new_role');
        $this->assertTrue($role->hasPermissionTo('view_users'));
        $this->assertTrue($role->hasPermissionTo('create_users'));
    }

    /** @test */
    public function it_validates_required_fields_when_creating_role()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/roles', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_validates_unique_role_name()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/roles', [
                'name' => 'admin', // Already exists
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_normalizes_role_name()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/roles', [
                'name' => 'New Role With Spaces',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'new_role_with_spaces');
    }

    /** @test */
    public function it_can_show_a_role()
    {
        $role = Role::findByName('admin');

        $response = $this->getJson("/api/roles/{$role->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $role->id)
            ->assertJsonPath('data.name', 'admin');
    }

    /** @test */
    public function it_can_update_a_role()
    {
        $role = Role::create(['name' => 'test_role']);

        $updateData = [
            'name' => 'updated_role',
            'permissions' => ['view_users'],
        ];

        $response = $this->actingAs($this->admin)
            ->putJson("/api/roles/{$role->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'updated_role');

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'updated_role',
        ]);

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo('view_users'));
    }

    /** @test */
    public function it_can_delete_a_role_without_users()
    {
        $role = Role::create(['name' => 'deletable_role']);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/roles/{$role->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('roles', [
            'id' => $role->id,
        ]);
    }

    /** @test */
    public function it_prevents_deletion_of_role_with_users()
    {
        $role = Role::findByName('staff');
        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/roles/{$role->id}");

        $response->assertStatus(409)
            ->assertJson(['message' => 'Cannot delete role that has users assigned to it.']);
    }

    /** @test */
    public function it_can_assign_permission_to_role()
    {
        $role = Role::create(['name' => 'test_role']);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/roles/{$role->id}/assign-permission", [
                'permission' => 'view_users',
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => "Permission 'view_users' assigned to role successfully."]);

        $this->assertTrue($role->fresh()->hasPermissionTo('view_users'));
    }

    /** @test */
    public function it_can_remove_permission_from_role()
    {
        $role = Role::create(['name' => 'test_role']);
        $role->givePermissionTo('view_users');

        $response = $this->actingAs($this->admin)
            ->postJson("/api/roles/{$role->id}/remove-permission", [
                'permission' => 'view_users',
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => "Permission 'view_users' removed from role successfully."]);

        $this->assertFalse($role->fresh()->hasPermissionTo('view_users'));
    }

    /** @test */
    public function it_can_sync_permissions_to_role()
    {
        $role = Role::create(['name' => 'test_role']);
        $role->givePermissionTo(['view_users', 'edit_users']);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/roles/{$role->id}/sync-permissions", [
                'permissions' => ['create_users', 'delete_users'],
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Role permissions updated successfully.']);

        $role->refresh();
        $this->assertFalse($role->hasPermissionTo('view_users'));
        $this->assertFalse($role->hasPermissionTo('edit_users'));
        $this->assertTrue($role->hasPermissionTo('create_users'));
        $this->assertTrue($role->hasPermissionTo('delete_users'));
    }

    /** @test */
    public function it_can_assign_users_to_role()
    {
        $role = Role::create(['name' => 'test_role']);
        $users = User::factory()->count(3)->create();
        $userIds = $users->pluck('id')->toArray();

        $response = $this->actingAs($this->admin)
            ->postJson("/api/roles/{$role->id}/assign-users", [
                'user_ids' => $userIds,
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => '3 users assigned to role successfully.']);

        foreach ($users as $user) {
            $this->assertTrue($user->hasRole('test_role'));
        }
    }

    /** @test */
    public function it_can_remove_users_from_role()
    {
        $role = Role::create(['name' => 'test_role']);
        $users = User::factory()->count(3)->create();

        foreach ($users as $user) {
            $user->assignRole($role);
        }

        $userIds = $users->pluck('id')->toArray();

        $response = $this->actingAs($this->admin)
            ->postJson("/api/roles/{$role->id}/remove-users", [
                'user_ids' => $userIds,
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => '3 users removed from role successfully.']);

        foreach ($users as $user) {
            $this->assertFalse($user->fresh()->hasRole('test_role'));
        }
    }

    /** @test */
    public function it_can_get_role_statistics()
    {
        Role::create(['name' => 'empty_role']);
        $roleWithUsers = Role::create(['name' => 'role_with_users']);
        $user = User::factory()->create();
        $user->assignRole($roleWithUsers);

        $response = $this->getJson('/api/roles/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_roles',
                'roles_with_users',
                'roles_without_users',
                'total_permissions',
                'roles_by_guard',
                'popular_roles',
            ]);
    }
}
