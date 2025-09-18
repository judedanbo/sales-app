<?php

namespace Database\Factories;

use App\Enums\ManagementType;
use App\Enums\OwnershipType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SchoolManagement>
 */
class SchoolManagementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'management_type' => $this->faker->randomElement(ManagementType::cases()),
            'ownership_type' => $this->faker->randomElement(OwnershipType::cases()),
            'managing_authority' => $this->faker->company().' Education Trust',
            'board_name' => $this->faker->optional(0.6)->randomElement([
                'CBSE Board', 'ICSE Council', 'State Education Board',
                'IB Organization', 'Cambridge Board',
            ]),
        ];
    }
}
