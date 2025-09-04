<?php

namespace Database\Seeders;

use App\Enums\SchoolType;
use App\Models\School;
use App\Models\SchoolClass;
use Illuminate\Database\Seeder;

class SchoolClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all schools
        $schools = School::all();

        foreach ($schools as $school) {
            $schoolType = $school->school_type;

            // Create classes based on school type
            $classes = $this->getClassesForSchoolType($schoolType);

            foreach ($classes as $grade => $className) {
                SchoolClass::factory()->create([
                    'school_id' => $school->id,
                    'class_name' => $className,
                    'class_code' => 'CLS-'.str_pad($grade, 2, '0', STR_PAD_LEFT),
                    'grade_level' => $grade,
                    'min_age' => $grade + 4,
                    'max_age' => $grade + 6,
                    'order_sequence' => $grade,
                ]);
            }
        }
    }

    private function getClassesForSchoolType(SchoolType $schoolType): array
    {
        return match ($schoolType) {
            SchoolType::PRIMARY => [
                1 => 'Class 1', 2 => 'Class 2', 3 => 'Class 3',
                4 => 'Class 4', 5 => 'Class 5',
            ],
            SchoolType::SECONDARY => [
                6 => 'Class 6', 7 => 'Class 7', 8 => 'Class 8',
                9 => 'Class 9', 10 => 'Class 10', 11 => 'Class 11', 12 => 'Class 12',
            ],
            SchoolType::HIGHER_SECONDARY => [
                1 => 'Class 1', 2 => 'Class 2', 3 => 'Class 3', 4 => 'Class 4', 5 => 'Class 5',
                6 => 'Class 6', 7 => 'Class 7', 8 => 'Class 8', 9 => 'Class 9', 10 => 'Class 10',
                11 => 'Class 11', 12 => 'Class 12',
            ],
            default => [
                1 => 'Class 1', 2 => 'Class 2', 3 => 'Class 3', 4 => 'Class 4', 5 => 'Class 5',
            ]
        };
    }
}
