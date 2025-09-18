<?php

namespace Database\Seeders;

use App\Enums\ContactType;
use App\Models\School;
use App\Models\SchoolContact;
use Illuminate\Database\Seeder;

class SchoolContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all schools
        $schools = School::all();

        foreach ($schools as $school) {
            // Each school should have at least a main contact
            SchoolContact::factory()->create([
                'school_id' => $school->id,
                'contact_type' => ContactType::MAIN,
            ]);

            // 70% chance of having additional contacts
            if (fake()->boolean(70)) {
                SchoolContact::factory()->create([
                    'school_id' => $school->id,
                    'contact_type' => ContactType::ADMISSION,
                ]);
            }

            // 50% chance of having accounts contact
            if (fake()->boolean(50)) {
                SchoolContact::factory()->create([
                    'school_id' => $school->id,
                    'contact_type' => ContactType::ACCOUNTS,
                ]);
            }
        }
    }
}
