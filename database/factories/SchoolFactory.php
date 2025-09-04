<?php

namespace Database\Factories;

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
        $boards = ['CBSE', 'ICSE', 'State Board', 'IB', 'IGCSE', 'NIOS'];
        
        return [
            'school_code' => 'SCH-' . $this->faker->unique()->numberBetween(1000, 9999),
            'school_name' => $this->faker->words(3, true) . ' School',
            'school_type' => $this->faker->randomElement(SchoolType::cases()),
            'board_affiliation' => $this->faker->randomElement($boards),
            'established_date' => $this->faker->dateTimeBetween('-50 years', '-1 year'),
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
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
