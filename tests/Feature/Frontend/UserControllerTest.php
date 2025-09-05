<?php

namespace Tests\Feature\Frontend;

use App\Enums\UserType;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $staff;

    protected School $school;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles and permissions
        $this->artisan('db:seed', ['--class' => 'UserRolesSeeder']);

        // Create test users
        $this->admin = User::factory()->create([
            'user_type' => UserType::ADMIN,
            'email' => 'admin@test.com',
        ]);
        $this->admin->assignRole('admin');

        $this->staff = User::factory()->create([
            'user_type' => UserType::STAFF,
            'email' => 'staff@test.com',
        ]);
        $this->staff->assignRole('staff');

        // Create a test school
        $this->school = School::factory()->create([
            'school_name' => 'Test School',
            'school_code' => 'TST001',
        ]);
    }

    /** @test */
    public function it_can_render_users_index_page()
    {
        User::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)
            ->get('/users');

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Users/Index')
                ->has('users.data')
                ->has('filters')
                ->has('schools')
                ->has('roles')
                ->has('userTypes')
                ->has('statistics')
            );
    }

    /** @test */
    public function it_can_filter_users_on_index_page()
    {
        User::factory()->count(3)->create(['user_type' => UserType::TEACHER]);
        User::factory()->count(2)->create(['user_type' => UserType::PRINCIPAL]);

        $response = $this->actingAs($this->admin)
            ->get('/users?user_type=teacher');

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Users/Index')
                ->where('filters.user_type', 'teacher')
                ->has('users.data', fn (Assert $page) => $page->each(
                    fn (Assert $user) => $user->where('user_type', 'teacher')
                ))
            );
    }

    /** @test */
    public function it_can_search_users_on_index_page()
    {
        User::factory()->create(['name' => 'John Doe']);
        User::factory()->create(['name' => 'Jane Smith']);

        $response = $this->actingAs($this->admin)
            ->get('/users?search=John');

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Users/Index')
                ->where('filters.search', 'John')
            );
    }

    /** @test */
    public function it_can_render_create_user_page()
    {
        $response = $this->actingAs($this->admin)
            ->get('/users/create');

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Users/Create')
                ->has('schools')
                ->has('userTypes')
            );
    }

    /** @test */
    public function it_can_store_a_new_user()
    {
        $userData = [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'user_type' => UserType::STAFF->value,
            'phone' => '1234567890',
            'department' => 'Sales',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)
            ->post('/users', $userData);

        $response->assertRedirect('/users')
            ->assertSessionHas('success', 'User created successfully.');

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'name' => 'New User',
        ]);
    }

    /** @test */
    public function it_validates_user_creation()
    {
        $response = $this->actingAs($this->admin)
            ->post('/users', []);

        $response->assertSessionHasErrors(['name', 'email', 'password', 'user_type']);
    }

    /** @test */
    public function it_can_render_user_show_page()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)
            ->get("/users/{$user->id}");

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Users/Show')
                ->has('user', fn (Assert $page) => $page
                    ->where('id', $user->id)
                    ->where('email', $user->email)
                )
            );
    }

    /** @test */
    public function it_can_render_edit_user_page()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)
            ->get("/users/{$user->id}/edit");

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Users/Edit')
                ->has('user', fn (Assert $page) => $page
                    ->where('id', $user->id)
                )
                ->has('schools')
                ->has('userTypes')
            );
    }

    /** @test */
    public function it_can_update_a_user()
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'user_type' => UserType::STAFF,
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'email' => $user->email,
            'phone' => '9876543210',
        ];

        $response = $this->actingAs($this->admin)
            ->put("/users/{$user->id}", $updateData);

        $response->assertRedirect("/users/{$user->id}")
            ->assertSessionHas('success', 'User updated successfully.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'phone' => '9876543210',
        ]);
    }

    /** @test */
    public function it_can_delete_a_user()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)
            ->delete("/users/{$user->id}");

        $response->assertRedirect('/users')
            ->assertSessionHas('success', 'User deleted successfully.');

        $this->assertSoftDeleted('users', [
            'id' => $user->id,
        ]);
    }

    /** @test */
    public function it_prevents_self_deletion_in_frontend()
    {
        $response = $this->actingAs($this->admin)
            ->delete("/users/{$this->admin->id}");

        $response->assertRedirect('/users')
            ->assertSessionHas('error', 'You cannot delete your own account.');
    }

    /** @test */
    public function it_can_render_user_roles_page()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)
            ->get("/users/{$user->id}/roles");

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Users/Roles')
                ->has('user')
                ->has('availableRoles')
            );
    }

    /** @test */
    public function it_can_assign_role_to_user_in_frontend()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)
            ->post("/users/{$user->id}/assign-role", [
                'role' => 'staff',
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success', "Role 'staff' assigned to user successfully.");

        $this->assertTrue($user->fresh()->hasRole('staff'));
    }

    /** @test */
    public function it_can_remove_role_from_user_in_frontend()
    {
        $user = User::factory()->create();
        $user->assignRole('staff');

        $response = $this->actingAs($this->admin)
            ->delete("/users/{$user->id}/remove-role", [
                'role' => 'staff',
            ]);

        $response->assertRedirect()
            ->assertSessionHas('success', "Role 'staff' removed from user successfully.");

        $this->assertFalse($user->fresh()->hasRole('staff'));
    }

    /** @test */
    public function it_can_activate_a_user()
    {
        $user = User::factory()->create(['is_active' => false]);

        $response = $this->actingAs($this->admin)
            ->post("/users/{$user->id}/activate");

        $response->assertRedirect()
            ->assertSessionHas('success', 'User activated successfully.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_deactivate_a_user()
    {
        $user = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->admin)
            ->post("/users/{$user->id}/deactivate");

        $response->assertRedirect()
            ->assertSessionHas('success', 'User deactivated successfully.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => false,
        ]);
    }

    /** @test */
    public function it_prevents_self_deactivation_in_frontend()
    {
        $response = $this->actingAs($this->admin)
            ->post("/users/{$this->admin->id}/deactivate");

        $response->assertRedirect()
            ->assertSessionHas('error', 'You cannot deactivate your own account.');
    }

    /** @test */
    public function it_can_restore_a_deleted_user()
    {
        $user = User::factory()->create();
        $user->delete();

        $response = $this->actingAs($this->admin)
            ->post("/users/{$user->id}/restore");

        $response->assertRedirect("/users/{$user->id}")
            ->assertSessionHas('success', 'User restored successfully.');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function it_requires_authentication_to_access_users()
    {
        $response = $this->get('/users');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function it_respects_user_permissions()
    {
        // Create a user without user management permissions
        $restrictedUser = User::factory()->create(['user_type' => UserType::STAFF]);
        $restrictedUser->assignRole('staff');

        $response = $this->actingAs($restrictedUser)
            ->post('/users', [
                'name' => 'Test',
                'email' => 'test@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
                'user_type' => UserType::STAFF->value,
            ]);

        $response->assertStatus(403);
    }
}
