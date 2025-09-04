<?php

namespace Database\Factories;

use App\Enums\AddressType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SchoolAddress>
 */
class SchoolAddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'address_type' => $this->faker->randomElement(AddressType::cases()),
            'address_line1' => $this->faker->streetAddress(),
            'address_line2' => $this->faker->optional(0.4)->secondaryAddress(),
            'city' => $this->faker->city(),
            'state_province' => $this->faker->state(),
            'postal_code' => $this->faker->postcode(),
            'country' => 'India',
        ];
    }
}
