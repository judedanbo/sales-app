<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SchoolResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_code' => $this->school_code,
            'school_name' => $this->school_name,
            'school_type' => [
                'value' => $this->school_type->value,
                'label' => ucfirst(str_replace('_', ' ', $this->school_type->value)),
            ],
            'board_affiliation' => $this->board_affiliation,
            'established_date' => $this->established_date?->format('Y-m-d'),
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
            
            // Relationships (only when loaded) - simplified for now
            'contacts_count' => $this->whenCounted('contacts'),
            'addresses_count' => $this->whenCounted('addresses'),
            'officials_count' => $this->whenCounted('officials'),
            'documents_count' => $this->whenCounted('documents'),
            'academic_years_count' => $this->whenCounted('academicYears'),
            'school_classes_count' => $this->whenCounted('schoolClasses'),
            
            // Additional computed fields
            'years_since_established' => $this->established_date ? 
                now()->diffInYears($this->established_date) : null,
            'status_label' => $this->is_active ? 'Active' : 'Inactive',
        ];
    }
}
