<?php

namespace Tests\Feature\Api;

use App\Enums\UserType;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
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
    public function it_can_list_users()
    {
        // Create additional users
        User::factory()->count(5)->create();

        $response = $this->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'user_type',
                        'user_type_label',
                        'is_active',
                    ],
                ],
                'meta' => [
                    'total',
                    'count',
                    'per_page',
                    'current_page',
                ],
            ]);
    }

    /** @test */
    public function it_can_filter_users_by_type()
    {
        User::factory()->create(['user_type' => UserType::TEACHER]);
        User::factory()->create(['user_type' => UserType::PRINCIPAL]);

        $response = $this->getJson('/api/users?user_type=teacher');

        $response->assertStatus(200);
        $data = $response->json('data');

        foreach ($data as $user) {
            $this->assertEquals('teacher', $user['user_type']);
        }
    }

    /** @test */
    public function it_can_filter_users_by_school()
    {
        User::factory()->count(2)->create([
            'user_type' => UserType::TEACHER,
            'school_id' => $this->school->id,
        ]);

        $response = $this->getJson("/api/users?school_id={$this->school->id}");

        $response->assertStatus(200);
        $data = $response->json('data');

        foreach ($data as $user) {
            $this->assertEquals($this->school->id, $user['school_id']);
        }
    }

    /** @test */
    public function it_can_search_users()
    {
        $searchUser = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
        ]);

        $response = $this->getJson('/api/users?search=John');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'John Doe']);
    }

    /** @test */
    public function it_can_create_a_user()
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
            ->postJson('/api/users', $userData);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'New User')
            ->assertJsonPath('data.email', 'newuser@example.com')
            ->assertJsonPath('data.user_type', 'staff');

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'name' => 'New User',
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_user()
    {
        $response = $this->actingAs($this->admin)
            ->postJson('/api/users', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password', 'user_type']);
    }

    /** @test */
    public function it_requires_school_for_school_based_users()
    {
        $userData = [
            'name' => 'Teacher User',
            'email' => 'teacher@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'user_type' => UserType::TEACHER->value,
            // Missing school_id
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/users', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['school_id']);
    }

    /** @test */
    public function it_prevents_school_assignment_for_system_users()
    {
        $userData = [
            'name' => 'Admin User',
            'email' => 'admin2@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'user_type' => UserType::ADMIN->value,
            'school_id' => $this->school->id, // Should not be allowed
        ];

        $response = $this->actingAs($this->admin)
            ->postJson('/api/users', $userData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['school_id']);
    }

    /** @test */
    public function it_can_show_a_user()
    {
        $user = User::factory()->create();

        $response = $this->getJson("/api/users/{$user->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);
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
            'phone' => '9876543210',
        ];

        $response = $this->actingAs($this->admin)
            ->putJson("/api/users/{$user->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.phone', '9876543210');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function it_prevents_self_deactivation()
    {
        $response = $this->actingAs($this->admin)
            ->putJson("/api/users/{$this->admin->id}", [
                'is_active' => false,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['is_active']);
    }

    /** @test */
    public function it_can_delete_a_user()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/users/{$user->id}");

        $response->assertStatus(204);

        $this->assertSoftDeleted('users', [
            'id' => $user->id,
        ]);
    }

    /** @test */
    public function it_prevents_self_deletion()
    {
        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/users/{$this->admin->id}");

        $response->assertStatus(403)
            ->assertJson(['message' => 'You cannot delete your own account.']);
    }

    /** @test */
    public function it_can_restore_a_deleted_user()
    {
        $user = User::factory()->create();
        $user->delete();

        $response = $this->actingAs($this->admin)
            ->postJson("/api/users/{$user->id}/restore");

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function it_can_permanently_delete_a_user()
    {
        $user = User::factory()->create();
        $user->delete();

        $response = $this->actingAs($this->admin)
            ->deleteJson("/api/users/{$user->id}/force-delete");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }

    /** @test */
    public function it_can_bulk_update_user_status()
    {
        $users = User::factory()->count(3)->create(['is_active' => true]);
        $userIds = $users->pluck('id')->toArray();

        $response = $this->actingAs($this->admin)
            ->postJson('/api/users/bulk/update-status', [
                'user_ids' => $userIds,
                'is_active' => false,
            ]);

        $response->assertStatus(200);

        foreach ($userIds as $userId) {
            $this->assertDatabaseHas('users', [
                'id' => $userId,
                'is_active' => false,
            ]);
        }
    }

    /** @test */
    public function it_prevents_bulk_deactivation_of_current_user()
    {
        $users = User::factory()->count(2)->create();
        $userIds = array_merge([$this->admin->id], $users->pluck('id')->toArray());

        $response = $this->actingAs($this->admin)
            ->postJson('/api/users/bulk/update-status', [
                'user_ids' => $userIds,
                'is_active' => false,
            ]);

        $response->assertStatus(403)
            ->assertJson(['message' => 'You cannot deactivate your own account.']);
    }

    /** @test */
    public function it_can_assign_role_to_user()
    {
        $user = User::factory()->create();
        $role = Role::findByName('staff');

        $response = $this->actingAs($this->admin)
            ->postJson("/api/users/{$user->id}/assign-role", [
                'role' => 'staff',
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => "Role 'staff' assigned to user successfully."]);

        $this->assertTrue($user->fresh()->hasRole('staff'));
    }

    /** @test */
    public function it_can_remove_role_from_user()
    {
        $user = User::factory()->create();
        $user->assignRole('staff');

        $response = $this->actingAs($this->admin)
            ->postJson("/api/users/{$user->id}/remove-role", [
                'role' => 'staff',
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => "Role 'staff' removed from user successfully."]);

        $this->assertFalse($user->fresh()->hasRole('staff'));
    }

    /** @test */
    public function it_can_get_user_statistics()
    {
        User::factory()->count(5)->create(['is_active' => true]);
        User::factory()->count(2)->create(['is_active' => false]);

        $response = $this->getJson('/api/users/statistics');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_users',
                'active_users',
                'inactive_users',
                'users_by_type',
                'users_by_school',
            ]);
    }
}
