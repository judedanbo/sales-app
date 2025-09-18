<?php

namespace Database\Factories;

use App\Enums\OfficialType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SchoolOfficial>
 */
class SchoolOfficialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'official_type' => $this->faker->randomElement(OfficialType::cases()),
            'name' => $this->faker->name(),
            'qualification' => $this->faker->randomElement([
                'B.Ed, M.A', 'M.Ed, B.A', 'Ph.D, M.Sc', 'B.A, B.Ed',
                'M.Com, B.Ed', 'M.A, B.Ed', 'M.Sc, B.Ed',
            ]),
            'department' => $this->faker->optional(0.7)->randomElement([
                'Administration', 'Academics', 'Finance', 'Operations', 'Student Affairs',
            ]),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'is_primary' => $this->faker->boolean(20), // 20% chance of being primary
        ];
    }
}
