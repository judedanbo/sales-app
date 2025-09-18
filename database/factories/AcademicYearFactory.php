<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startYear = $this->faker->numberBetween(2020, 2025);
        $endYear = $startYear + 1;

        return [
            'year_name' => "{$startYear}-{$endYear}",
            'start_date' => "{$startYear}-04-01", // April 1st start
            'end_date' => "{$endYear}-03-31", // March 31st end
            'is_current' => $this->faker->boolean(20), // 20% chance of being current
        ];
    }
}
