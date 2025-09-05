<?php

namespace Tests\Feature\Frontend;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
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
    public function it_can_render_permissions_index_page()
    {
        $response = $this->actingAs($this->admin)
            ->get('/permissions');

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Permissions/Index')
                ->has('permissions.data')
                ->has('filters')
                ->has('categories')
            );
    }

    /** @test */
    public function it_can_filter_permissions_by_category()
    {
        $response = $this->actingAs($this->admin)
            ->get('/permissions?category=view');

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Permissions/Index')
                ->where('filters.category', 'view')
                ->has('permissions.data', fn (Assert $page) => $page->each(
                    fn (Assert $permission) => $permission->where('category', 'view')
                ))
            );
    }

    /** @test */
    public function it_can_search_permissions()
    {
        $response = $this->actingAs($this->admin)
            ->get('/permissions?search=users');

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Permissions/Index')
                ->where('filters.search', 'users')
            );
    }

    /** @test */
    public function it_can_render_permission_show_page()
    {
        $permission = Permission::findByName('view_users');

        $response = $this->actingAs($this->admin)
            ->get("/permissions/{$permission->id}");

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Permissions/Show')
                ->has('permission', fn (Assert $page) => $page
                    ->where('id', $permission->id)
                    ->where('name', 'view_users')
                )
            );
    }

    /** @test */
    public function it_can_render_grouped_permissions_page()
    {
        $response = $this->actingAs($this->admin)
            ->get('/permissions/grouped');

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Permissions/Grouped')
                ->has('permissionGroups')
                ->has('filters')
                ->has('totalPermissions')
                ->has('totalCategories')
            );
    }

    /** @test */
    public function it_can_filter_grouped_permissions()
    {
        $response = $this->actingAs($this->admin)
            ->get('/permissions/grouped?search=create');

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Permissions/Grouped')
                ->where('filters.search', 'create')
            );
    }

    /** @test */
    public function it_can_render_permissions_by_role_page()
    {
        $role = Role::findByName('admin');

        $response = $this->actingAs($this->admin)
            ->get("/permissions/by-role/{$role->id}");

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Permissions/ByRole')
                ->has('role', fn (Assert $page) => $page
                    ->where('id', $role->id)
                    ->where('name', 'admin')
                )
                ->has('permissionGroups')
                ->has('totalPermissions')
            );
    }

    /** @test */
    public function it_can_render_permissions_by_user_page()
    {
        $response = $this->actingAs($this->admin)
            ->get("/permissions/by-user/{$this->staff->id}");

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Permissions/ByUser')
                ->has('user', fn (Assert $page) => $page
                    ->where('id', $this->staff->id)
                )
                ->has('permissionGroups')
                ->has('totalPermissions')
                ->has('directPermissions')
                ->has('rolePermissions')
            );
    }

    /** @test */
    public function it_distinguishes_between_direct_and_role_permissions()
    {
        // Give direct permission to staff
        $directPermission = Permission::findByName('manage_settings');
        $this->staff->givePermissionTo($directPermission);

        $response = $this->actingAs($this->admin)
            ->get("/permissions/by-user/{$this->staff->id}");

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Permissions/ByUser')
                ->has('permissionGroups')
                ->where('directPermissions', 1)
                ->where('rolePermissions', fn ($count) => $count > 0)
            );
    }

    /** @test */
    public function it_can_render_permission_statistics_page()
    {
        $response = $this->actingAs($this->admin)
            ->get('/permissions/statistics');

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Permissions/Statistics')
                ->has('stats', fn (Assert $page) => $page
                    ->has('total_permissions')
                    ->has('permissions_with_roles')
                    ->has('permissions_without_roles')
                    ->has('total_roles')
                    ->has('usage_percentage')
                )
                ->has('permissionsByCategory')
                ->has('mostUsedPermissions')
                ->has('unusedPermissions')
            );
    }

    /** @test */
    public function it_correctly_calculates_usage_percentage()
    {
        $response = $this->actingAs($this->admin)
            ->get('/permissions/statistics');

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Permissions/Statistics')
                ->where('stats.usage_percentage', fn ($value) => is_numeric($value) && $value >= 0 && $value <= 100
                )
            );
    }

    /** @test */
    public function it_shows_category_distribution_with_percentages()
    {
        $response = $this->actingAs($this->admin)
            ->get('/permissions/statistics');

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Permissions/Statistics')
                ->has('permissionsByCategory', fn (Assert $page) => $page->each(
                    fn (Assert $category) => $category
                        ->has('category')
                        ->has('display_name')
                        ->has('count')
                        ->has('percentage')
                ))
            );
    }

    /** @test */
    public function it_lists_most_used_permissions()
    {
        $response = $this->actingAs($this->admin)
            ->get('/permissions/statistics');

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Permissions/Statistics')
                ->has('mostUsedPermissions', fn (Assert $page) => $page->each(
                    fn (Assert $permission) => $permission
                        ->has('name')
                        ->has('display_name')
                        ->has('roles_count')
                        ->has('category')
                ))
            );
    }

    /** @test */
    public function it_limits_unused_permissions_display()
    {
        $response = $this->actingAs($this->admin)
            ->get('/permissions/statistics');

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Permissions/Statistics')
                ->has('unusedPermissions', fn (Assert $permissions) =>
                    // Should be limited to 20 for display
                    $permissions->count() <= 20
                )
            );
    }

    /** @test */
    public function it_requires_authentication_to_access_permissions()
    {
        $response = $this->get('/permissions');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function it_shows_permission_details_with_role_relationships()
    {
        $permission = Permission::findByName('view_users');

        $response = $this->actingAs($this->admin)
            ->get("/permissions/{$permission->id}");

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Permissions/Show')
                ->has('permission', fn (Assert $page) => $page
                    ->has('roles')
                    ->has('roles_count')
                    ->has('users_count')
                )
            );
    }

    /** @test */
    public function it_properly_formats_permission_display_names()
    {
        $response = $this->actingAs($this->admin)
            ->get('/permissions');

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Permissions/Index')
                ->has('permissions.data', fn (Assert $page) => $page->each(
                    fn (Assert $permission) => $permission
                        ->has('display_name')
                        ->has('category_display')
                ))
            );
    }

    /** @test */
    public function it_provides_filter_categories()
    {
        $response = $this->actingAs($this->admin)
            ->get('/permissions');

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Permissions/Index')
                ->has('categories', fn (Assert $page) => $page->each(
                    fn (Assert $category) => $category
                        ->has('value')
                        ->has('label')
                ))
            );
    }
}
