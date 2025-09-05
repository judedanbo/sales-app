<?php

namespace Database\Factories;

use App\Enums\BoardAffiliation;
use App\Enums\SchoolType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\School>
 */
class SchoolFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'school_code' => 'SCH-'.$this->faker->unique()->numberBetween(1000, 9999),
            'school_name' => $this->faker->words(3, true).' School',
            'school_type' => $this->faker->randomElement(SchoolType::cases()),
            'board_affiliation' => $this->faker->optional(0.8)->randomElement(BoardAffiliation::cases()),
            'established_date' => $this->faker->dateTimeBetween('-50 years', '-1 year'),
            'status' => $this->faker->randomElement(['active', 'inactive']), // Random status
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'school_type' => SchoolType::PRIMARY,
        ]);
    }

    public function secondary(): static
    {
        return $this->state(fn (array $attributes) => [
            'school_type' => SchoolType::SECONDARY,
        ]);
    }
}
