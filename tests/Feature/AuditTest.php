<?php

use App\Models\School;
use App\Models\SchoolContact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use OwenIt\Auditing\Models\Audit;

uses(RefreshDatabase::class);

test('user creation is audited', function () {
    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $audit = Audit::where('auditable_type', User::class)
        ->where('auditable_id', $user->id)
        ->where('event', 'created')
        ->first();

    expect($audit)->not()->toBeNull();
    expect($audit->new_values)->toHaveKey('name', 'Test User');
    expect($audit->new_values)->toHaveKey('email', 'test@example.com');
});

test('user update is audited', function () {
    $user = User::factory()->create();

    $user->update([
        'name' => 'Updated Name',
        'phone' => '1234567890',
    ]);

    $audit = Audit::where('auditable_type', User::class)
        ->where('auditable_id', $user->id)
        ->where('event', 'updated')
        ->first();

    expect($audit)->not()->toBeNull();
    expect($audit->new_values)->toHaveKey('name', 'Updated Name');
    expect($audit->new_values)->toHaveKey('phone', '1234567890');
});

test('user deletion is audited', function () {
    $user = User::factory()->create();
    $userId = $user->id;

    $user->delete();

    $audit = Audit::where('auditable_type', User::class)
        ->where('auditable_id', $userId)
        ->where('event', 'deleted')
        ->first();

    expect($audit)->not()->toBeNull();
});

test('school creation is audited', function () {
    $school = School::factory()->create([
        'school_name' => 'Test School',
        'school_code' => 'TS001',
    ]);

    $audit = Audit::where('auditable_type', School::class)
        ->where('auditable_id', $school->id)
        ->where('event', 'created')
        ->first();

    expect($audit)->not()->toBeNull();
    expect($audit->new_values)->toHaveKey('school_name', 'Test School');
    expect($audit->new_values)->toHaveKey('school_code', 'TS001');
});

test('school contact creation is audited', function () {
    $school = School::factory()->create();
    $contact = SchoolContact::factory()->create([
        'school_id' => $school->id,
        'email_primary' => 'contact@testschool.com',
    ]);

    $audit = Audit::where('auditable_type', SchoolContact::class)
        ->where('auditable_id', $contact->id)
        ->where('event', 'created')
        ->first();

    expect($audit)->not()->toBeNull();
    expect($audit->new_values)->toHaveKey('school_id', $school->id);
    expect($audit->new_values)->toHaveKey('email_primary', 'contact@testschool.com');
});

test('sensitive fields are excluded from user auditing', function () {
    $user = User::factory()->create();

    // Try to update password and other sensitive fields
    $user->update([
        'password' => 'new_password',
        'remember_token' => 'new_token',
        'name' => 'Updated Name',
    ]);

    $audit = Audit::where('auditable_type', User::class)
        ->where('auditable_id', $user->id)
        ->where('event', 'updated')
        ->first();

    expect($audit)->not()->toBeNull();
    expect($audit->new_values)->toHaveKey('name', 'Updated Name');
    expect($audit->new_values)->not->toHaveKey('password');
    expect($audit->new_values)->not->toHaveKey('remember_token');
});

test('audit api endpoints return data', function () {
    // Create authenticated user with audit permissions
    $authUser = User::factory()->create();
    $authUser->assignRole('system_admin');

    // Create some audit data
    $user = User::factory()->create();
    $school = School::factory()->create();

    // Test main index endpoint
    $response = $this->actingAs($authUser)->getJson('/api/audits/');
    $response->assertSuccessful();

    // Test statistics endpoint
    $response = $this->actingAs($authUser)->getJson('/api/audits/statistics/summary');
    $response->assertSuccessful();
    $response->assertJsonStructure([
        'stats' => [
            'total_audits',
            'today_audits',
            'this_week_audits',
            'this_month_audits',
        ],
        'events_breakdown',
        'models_breakdown',
        'top_users',
    ]);

    // Test models list endpoint
    $response = $this->actingAs($authUser)->getJson('/api/audits/models/list');
    $response->assertSuccessful();
});

test('audit filtering works', function () {
    // Create authenticated user with audit permissions
    $authUser = User::factory()->create();
    $authUser->assignRole('system_admin');

    $user1 = User::factory()->create(['name' => 'User 1']);
    $user2 = User::factory()->create(['name' => 'User 2']);
    $school = School::factory()->create();

    // Test filtering by model type
    $response = $this->actingAs($authUser)->getJson('/api/audits/?auditable_type='.urlencode(User::class));
    $response->assertSuccessful();

    // Test filtering by user
    $response = $this->actingAs($authUser)->getJson("/api/audits/?user_id={$user1->id}");
    $response->assertSuccessful();

    // Test filtering by event
    $response = $this->actingAs($authUser)->getJson('/api/audits/?event=created');
    $response->assertSuccessful();
});

test('audit timeline shows chronological changes', function () {
    // Create authenticated user with audit permissions
    $authUser = User::factory()->create();
    $authUser->assignRole('system_admin');

    $user = User::factory()->create(['name' => 'Original Name']);

    // Make several updates to create a timeline
    $user->update(['name' => 'First Update']);
    $user->update(['name' => 'Second Update']);
    $user->update(['phone' => '1234567890']);

    $response = $this->actingAs($authUser)->getJson('/api/audits/timeline/'.urlencode(User::class)."/{$user->id}");
    $response->assertSuccessful();

    $audits = $response->json();
    expect($audits)->toHaveCount(4); // created + 3 updates

    // Verify they are in chronological order
    $events = array_column($audits, 'event');
    expect($events[0])->toBe('created');
    expect($events[1])->toBe('updated');
    expect($events[2])->toBe('updated');
    expect($events[3])->toBe('updated');
});

test('user specific audits can be retrieved', function () {
    // Create authenticated user with audit permissions
    $authUser = User::factory()->create();
    $authUser->assignRole('system_admin');

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // Update both users
    $user1->update(['name' => 'User 1 Updated']);
    $user2->update(['name' => 'User 2 Updated']);

    $response = $this->actingAs($authUser)->getJson("/api/audits/user/{$user1->id}");
    $response->assertSuccessful();

    $data = $response->json();

    // Should only contain audits for user1
    foreach ($data['data'] as $audit) {
        expect($audit['auditable_type'])->toBe(User::class);
        expect($audit['auditable_id'])->toBe($user1->id);
    }
});

test('model specific audits can be retrieved', function () {
    // Create authenticated user with audit permissions
    $authUser = User::factory()->create();
    $authUser->assignRole('system_admin');

    $user = User::factory()->create();
    $school = School::factory()->create();

    $user->update(['name' => 'Updated User']);
    $school->update(['school_name' => 'Updated School']);

    $userClass = urlencode(User::class);
    $response = $this->actingAs($authUser)->getJson("/api/audits/model/{$userClass}/{$user->id}");
    $response->assertSuccessful();

    $data = $response->json();

    // Should only contain audits for the specific user
    foreach ($data['data'] as $audit) {
        expect($audit['auditable_type'])->toBe(User::class);
        expect($audit['auditable_id'])->toBe($user->id);
    }
});
