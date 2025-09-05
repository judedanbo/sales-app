<?php

namespace Database\Factories;

use App\Enums\UserType;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $userType = fake()->randomElement(UserType::cases());

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'user_type' => $userType,
            'school_id' => $this->shouldHaveSchool($userType) ? School::factory() : null,
            'phone' => fake()->phoneNumber(),
            'department' => fake()->optional(0.7)->randomElement([
                'Administration',
                'Accounts',
                'Academic',
                'Support',
                'IT',
                'Library',
                'Sports',
            ]),
            'bio' => fake()->optional(0.5)->realText(200),
            'is_active' => fake()->boolean(90),
            'last_login_at' => fake()->optional(0.8)->dateTimeBetween('-1 month', 'now'),
            'created_by' => fake()->optional(0.6)->name(),
            'updated_by' => fake()->optional(0.3)->name(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Create a user of a specific type.
     */
    public function type(UserType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'user_type' => $type,
            'school_id' => $this->shouldHaveSchool($type) ? School::factory() : null,
        ]);
    }

    /**
     * Create a school-based user (school admin, principal, teacher).
     */
    public function schoolUser(?int $schoolId = null): static
    {
        $schoolType = fake()->randomElement([
            UserType::SCHOOL_ADMIN,
            UserType::PRINCIPAL,
            UserType::TEACHER,
        ]);

        return $this->state(fn (array $attributes) => [
            'user_type' => $schoolType,
            'school_id' => $schoolId ?? School::factory(),
        ]);
    }

    /**
     * Create a system user (staff, admin, audit, system admin).
     */
    public function systemUser(): static
    {
        $systemType = fake()->randomElement([
            UserType::STAFF,
            UserType::ADMIN,
            UserType::AUDIT,
            UserType::SYSTEM_ADMIN,
        ]);

        return $this->state(fn (array $attributes) => [
            'user_type' => $systemType,
            'school_id' => null,
        ]);
    }

    /**
     * Create an inactive user.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'last_login_at' => null,
        ]);
    }

    /**
     * Determine if a user type should have a school association.
     */
    private function shouldHaveSchool(UserType $userType): bool
    {
        return in_array($userType, [
            UserType::SCHOOL_ADMIN,
            UserType::PRINCIPAL,
            UserType::TEACHER,
        ]);
    }
}
