<?php

namespace Database\Seeders;

use App\Enums\SchoolType;
use App\Models\School;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create specific test schools
        School::factory()->create([
            'school_code' => 'SCH-001',
            'school_name' => 'Greenwood Primary School',
            'school_type' => SchoolType::PRIMARY,
            'board_affiliation' => 'CBSE',
            'established_date' => '1995-06-15',
            'is_active' => true,
        ]);

        School::factory()->create([
            'school_code' => 'SCH-002',
            'school_name' => 'St. Mary\'s High School',
            'school_type' => SchoolType::SECONDARY,
            'board_affiliation' => 'ICSE',
            'established_date' => '1987-04-10',
            'is_active' => true,
        ]);

        School::factory()->create([
            'school_code' => 'SCH-003',
            'school_name' => 'Rainbow International School',
            'school_type' => SchoolType::COMBINED,
            'board_affiliation' => 'IB',
            'established_date' => '2010-08-20',
            'is_active' => true,
        ]);

        // Create random schools for testing
        School::factory(10)->create();
        School::factory(2)->inactive()->create();
    }
}
