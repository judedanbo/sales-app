<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\SchoolManagement;
use Illuminate\Database\Seeder;

class SchoolManagementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all schools
        $schools = School::all();

        foreach ($schools as $school) {
            // Each school should have management information
            SchoolManagement::factory()->create([
                'school_id' => $school->id,
            ]);
        }
    }
}
