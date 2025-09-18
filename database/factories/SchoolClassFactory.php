<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SchoolClass>
 */
class SchoolClassFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $gradeLevel = $this->faker->numberBetween(1, 12);
        $className = $this->getClassName($gradeLevel);

        return [
            'class_name' => $className,
            'class_code' => 'CLS-'.str_pad($gradeLevel, 2, '0', STR_PAD_LEFT),
            'grade_level' => $gradeLevel,
            'min_age' => $gradeLevel + 4, // Typical age calculation
            'max_age' => $gradeLevel + 6,
            'order_sequence' => $gradeLevel,
        ];
    }

    private function getClassName(int $gradeLevel): string
    {
        $classNames = [
            1 => 'Class 1', 2 => 'Class 2', 3 => 'Class 3', 4 => 'Class 4', 5 => 'Class 5',
            6 => 'Class 6', 7 => 'Class 7', 8 => 'Class 8', 9 => 'Class 9', 10 => 'Class 10',
            11 => 'Class 11', 12 => 'Class 12',
        ];

        return $classNames[$gradeLevel] ?? "Class {$gradeLevel}";
    }
}
