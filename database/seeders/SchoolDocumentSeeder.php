<?php

namespace Database\Seeders;

use App\Enums\DocumentType;
use App\Models\School;
use App\Models\SchoolDocument;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SchoolDocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all schools
        $schools = School::all();

        foreach ($schools as $school) {
            // Each school should have registration document
            SchoolDocument::factory()->create([
                'school_id' => $school->id,
                'document_type' => DocumentType::REGISTRATION,
            ]);

            // Each school should have affiliation document
            SchoolDocument::factory()->create([
                'school_id' => $school->id,
                'document_type' => DocumentType::AFFILIATION,
            ]);

            // 80% chance of having tax certificate
            if (fake()->boolean(80)) {
                SchoolDocument::factory()->create([
                    'school_id' => $school->id,
                    'document_type' => DocumentType::TAX_CERTIFICATE,
                ]);
            }

            // 60% chance of having additional license
            if (fake()->boolean(60)) {
                SchoolDocument::factory()->create([
                    'school_id' => $school->id,
                    'document_type' => DocumentType::LICENSE,
                ]);
            }
        }
    }
}
