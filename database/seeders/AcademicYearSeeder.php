<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all schools
        $schools = School::all();

        foreach ($schools as $school) {
            // Create current academic year (2024-25)
            AcademicYear::factory()->create([
                'school_id' => $school->id,
                'year_name' => '2024-25',
                'start_date' => '2024-04-01',
                'end_date' => '2025-03-31',
                'is_current' => true,
            ]);

            // Create previous academic year (2023-24)
            AcademicYear::factory()->create([
                'school_id' => $school->id,
                'year_name' => '2023-24',
                'start_date' => '2023-04-01',
                'end_date' => '2024-03-31',
                'is_current' => false,
            ]);

            // 50% chance of having next academic year (2025-26) planned
            if (fake()->boolean(50)) {
                AcademicYear::factory()->create([
                    'school_id' => $school->id,
                    'year_name' => '2025-26',
                    'start_date' => '2025-04-01',
                    'end_date' => '2026-03-31',
                    'is_current' => false,
                ]);
            }
        }
    }
}
