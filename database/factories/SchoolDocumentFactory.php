<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SchoolDocument>
 */
class SchoolDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $documentType = $this->faker->randomElement(DocumentType::cases());
        
        return [
            'document_type' => $documentType,
            'document_name' => $this->getDocumentName($documentType),
            'document_number' => $this->faker->bothify('DOC-###-????'),
            'file_url' => $this->faker->optional(0.8)->url(),
            'issue_date' => $this->faker->dateTimeBetween('-5 years', '-1 year'),
            'expiry_date' => $this->faker->optional(0.7)->dateTimeBetween('now', '+5 years'),
        ];
    }

    private function getDocumentName(DocumentType $type): string
    {
        return match($type) {
            DocumentType::REGISTRATION => 'School Registration Certificate',
            DocumentType::AFFILIATION => 'Board Affiliation Certificate',
            DocumentType::TAX_CERTIFICATE => 'Tax Exemption Certificate',
            DocumentType::LICENSE => 'Educational Institution License',
        };
    }
}
