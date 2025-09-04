<?php

namespace Database\Factories;

use App\Enums\ContactType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SchoolContact>
 */
class SchoolContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contact_type' => $this->faker->randomElement(ContactType::cases()),
            'phone_primary' => $this->faker->phoneNumber(),
            'phone_secondary' => $this->faker->optional(0.3)->phoneNumber(),
            'email_primary' => $this->faker->companyEmail(),
            'email_secondary' => $this->faker->optional(0.2)->companyEmail(),
            'website' => $this->faker->optional(0.7)->domainName(),
        ];
    }
}
