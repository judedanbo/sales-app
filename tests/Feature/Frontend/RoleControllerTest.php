<?php

namespace Tests\Feature\Frontend;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
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
    public function it_can_render_roles_index_page()
    {
        $response = $this->actingAs($this->admin)
            ->get('/roles');

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Roles/Index')
                ->has('roles.data')
                ->has('filters')
            );
    }

    /** @test */
    public function it_can_search_roles_on_index_page()
    {
        Role::create(['name' => 'special_role']);

        $response = $this->actingAs($this->admin)
            ->get('/roles?search=special');

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Roles/Index')
                ->where('filters.search', 'special')
            );
    }

    /** @test */
    public function it_can_render_create_role_page()
    {
        $response = $this->actingAs($this->admin)
            ->get('/roles/create');

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Roles/Create')
                ->has('permissions')
                ->has('permissionGroups')
            );
    }

    /** @test */
    public function it_can_store_a_new_role()
    {
        $roleData = [
            'name' => 'new_role',
            'guard_name' => 'web',
            'permissions' => ['view_users', 'create_users'],
        ];

        $response = $this->actingAs($this->admin)
            ->post('/roles', $roleData);

        $response->assertRedirect('/roles')
            ->assertSessionHas('success', 'Role created successfully.');

        $this->assertDatabaseHas('roles', [
            'name' => 'new_role',
            'guard_name' => 'web',
        ]);

        $role = Role::findByName('new_role');
        $this->assertTrue($role->hasPermissionTo('view_users'));
    }

    /** @test */
    public function it_validates_role_creation()
    {
        $response = $this->actingAs($this->admin)
            ->post('/roles', []);

        $response->assertSessionHasErrors(['name']);
    }

    /** @test */
    public function it_can_render_role_show_page()
    {
        $role = Role::findByName('admin');

        $response = $this->actingAs($this->admin)
            ->get("/roles/{$role->id}");

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Roles/Show')
                ->has('role', fn (Assert $page) => $page
                    ->where('id', $role->id)
                    ->where('name', 'admin')
                )
                ->has('permissionGroups')
            );
    }

    /** @test */
    public function it_can_render_edit_role_page()
    {
        $role = Role::findByName('staff');

        $response = $this->actingAs($this->admin)
            ->get("/roles/{$role->id}/edit");

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Roles/Edit')
                ->has('role', fn (Assert $page) => $page
                    ->where('id', $role->id)
                )
                ->has('permissions')
                ->has('permissionGroups')
            );
    }

    /** @test */
    public function it_can_update_a_role()
    {
        $role = Role::create(['name' => 'test_role']);

        $updateData = [
            'name' => 'updated_role',
            'guard_name' => 'web',
            'permissions' => ['view_users'],
        ];

        $response = $this->actingAs($this->admin)
            ->put("/roles/{$role->id}", $updateData);

        $response->assertRedirect("/roles/{$role->id}")
            ->assertSessionHas('success', 'Role updated successfully.');

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'name' => 'updated_role',
        ]);
    }

    /** @test */
    public function it_can_delete_a_role()
    {
        $role = Role::create(['name' => 'deletable_role']);

        $response = $this->actingAs($this->admin)
            ->delete("/roles/{$role->id}");

        $response->assertRedirect('/roles')
            ->assertSessionHas('success', 'Role deleted successfully.');

        $this->assertDatabaseMissing('roles', [
            'id' => $role->id,
        ]);
    }

    /** @test */
    public function it_prevents_deletion_of_role_with_users_in_frontend()
    {
        $role = Role::findByName('staff');
        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($this->admin)
            ->delete("/roles/{$role->id}");

        $response->assertRedirect('/roles')
            ->assertSessionHas('error', 'Cannot delete role that has users assigned to it.');
    }

    /** @test */
    public function it_can_render_role_permissions_page()
    {
        $role = Role::findByName('admin');

        $response = $this->actingAs($this->admin)
            ->get("/roles/{$role->id}/permissions");

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Roles/Permissions')
                ->has('role')
                ->has('permissionGroups')
            );
    }

    /** @test */
    public function it_can_sync_permissions_in_frontend()
    {
        $role = Role::create(['name' => 'test_role']);

        $response = $this->actingAs($this->admin)
            ->post("/roles/{$role->id}/sync-permissions", [
                'permissions' => ['view_users', 'create_users'],
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success', 'Role permissions updated successfully.');

        $this->assertTrue($role->fresh()->hasPermissionTo('view_users'));
        $this->assertTrue($role->fresh()->hasPermissionTo('create_users'));
    }

    /** @test */
    public function it_can_render_role_users_page()
    {
        $role = Role::findByName('staff');
        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($this->admin)
            ->get("/roles/{$role->id}/users");

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Roles/Users')
                ->has('role')
            );
    }

    /** @test */
    public function it_can_assign_users_to_role_in_frontend()
    {
        $role = Role::create(['name' => 'test_role']);
        $users = User::factory()->count(2)->create();
        $userIds = $users->pluck('id')->toArray();

        $response = $this->actingAs($this->admin)
            ->post("/roles/{$role->id}/assign-users", [
                'user_ids' => $userIds,
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success', '2 users assigned to role successfully.');

        foreach ($users as $user) {
            $this->assertTrue($user->hasRole('test_role'));
        }
    }

    /** @test */
    public function it_can_remove_users_from_role_in_frontend()
    {
        $role = Role::create(['name' => 'test_role']);
        $users = User::factory()->count(2)->create();

        foreach ($users as $user) {
            $user->assignRole($role);
        }

        $userIds = $users->pluck('id')->toArray();

        $response = $this->actingAs($this->admin)
            ->delete("/roles/{$role->id}/remove-users", [
                'user_ids' => $userIds,
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success', '2 users removed from role successfully.');

        foreach ($users as $user) {
            $this->assertFalse($user->fresh()->hasRole('test_role'));
        }
    }

    /** @test */
    public function it_requires_authentication_to_access_roles()
    {
        $response = $this->get('/roles');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function it_respects_role_management_permissions()
    {
        $restrictedUser = User::factory()->create(['user_type' => UserType::STAFF]);
        $restrictedUser->assignRole('staff');

        $response = $this->actingAs($restrictedUser)
            ->post('/roles', [
                'name' => 'test_role',
            ]);

        $response->assertStatus(403);
    }
}
