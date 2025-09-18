<?php

namespace Database\Seeders;

use App\Enums\OfficialType;
use App\Models\School;
use App\Models\SchoolOfficial;
use Illuminate\Database\Seeder;

class SchoolOfficialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all schools
        $schools = School::all();

        foreach ($schools as $school) {
            // Each school should have a principal
            SchoolOfficial::factory()->create([
                'school_id' => $school->id,
                'official_type' => OfficialType::PRINCIPAL,
                'is_primary' => true,
            ]);

            // 80% chance of having a vice principal
            if (fake()->boolean(80)) {
                SchoolOfficial::factory()->create([
                    'school_id' => $school->id,
                    'official_type' => OfficialType::VICE_PRINCIPAL,
                ]);
            }

            // Each school should have an admin
            SchoolOfficial::factory()->create([
                'school_id' => $school->id,
                'official_type' => OfficialType::ADMIN,
            ]);

            // 70% chance of having an accountant
            if (fake()->boolean(70)) {
                SchoolOfficial::factory()->create([
                    'school_id' => $school->id,
                    'official_type' => OfficialType::ACCOUNTANT,
                ]);
            }

            // Random coordinators (0-2)
            $coordinatorCount = fake()->numberBetween(0, 2);
            for ($i = 0; $i < $coordinatorCount; $i++) {
                SchoolOfficial::factory()->create([
                    'school_id' => $school->id,
                    'official_type' => OfficialType::COORDINATOR,
                ]);
            }
        }
    }
}
