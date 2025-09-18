<?php

namespace Database\Seeders;

use App\Enums\AddressType;
use App\Models\School;
use App\Models\SchoolAddress;
use Illuminate\Database\Seeder;

class SchoolAddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all schools
        $schools = School::all();

        foreach ($schools as $school) {
            // Each school must have a physical address
            SchoolAddress::factory()->create([
                'school_id' => $school->id,
                'address_type' => AddressType::PHYSICAL,
            ]);

            // 60% chance of having a separate mailing address
            if (fake()->boolean(60)) {
                SchoolAddress::factory()->create([
                    'school_id' => $school->id,
                    'address_type' => AddressType::MAILING,
                ]);
            }

            // 40% chance of having a separate billing address
            if (fake()->boolean(40)) {
                SchoolAddress::factory()->create([
                    'school_id' => $school->id,
                    'address_type' => AddressType::BILLING,
                ]);
            }
        }
    }
}
