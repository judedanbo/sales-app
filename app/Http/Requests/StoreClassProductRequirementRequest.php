<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClassProductRequirementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        return $user->hasPermissionTo('manage_class_requirements');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Required relationships
            'school_id' => [
                'required',
                'integer',
                'exists:schools,id',
            ],
            'academic_year_id' => [
                'required',
                'integer',
                'exists:academic_years,id',
            ],
            'class_id' => [
                'required',
                'integer',
                'exists:school_classes,id',
            ],
            'product_id' => [
                'required',
                'integer',
                'exists:products,id',
            ],

            // Requirement specifications
            'is_required' => [
                'sometimes',
                'boolean',
            ],
            'min_quantity' => [
                'nullable',
                'integer',
                'min:0',
                'max:999999',
            ],
            'max_quantity' => [
                'nullable',
                'integer',
                'min:0',
                'max:999999',
                'gte:min_quantity',
            ],
            'recommended_quantity' => [
                'nullable',
                'integer',
                'min:0',
                'max:999999',
            ],

            // Timeline
            'required_by' => [
                'nullable',
                'date',
                'after_or_equal:today',
            ],

            // Status
            'is_active' => [
                'sometimes',
                'boolean',
            ],

            // Categorization
            'priority' => [
                'sometimes',
                'string',
                Rule::in(['low', 'medium', 'high', 'critical']),
            ],
            'requirement_category' => [
                'sometimes',
                'string',
                Rule::in(['textbooks', 'stationery', 'uniforms', 'supplies', 'technology', 'sports', 'art', 'science', 'other']),
            ],

            // Financial planning
            'estimated_cost' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'budget_allocation' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],

            // Additional information
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'school_id.required' => 'Please select a school.',
            'school_id.exists' => 'The selected school does not exist.',
            'academic_year_id.required' => 'Please select an academic year.',
            'academic_year_id.exists' => 'The selected academic year does not exist.',
            'class_id.required' => 'Please select a class.',
            'class_id.exists' => 'The selected class does not exist.',
            'product_id.required' => 'Please select a product.',
            'product_id.exists' => 'The selected product does not exist.',
            'min_quantity.min' => 'Minimum quantity must be at least 0.',
            'min_quantity.max' => 'Minimum quantity cannot exceed 999,999.',
            'max_quantity.min' => 'Maximum quantity must be at least 0.',
            'max_quantity.max' => 'Maximum quantity cannot exceed 999,999.',
            'max_quantity.gte' => 'Maximum quantity must be greater than or equal to minimum quantity.',
            'recommended_quantity.min' => 'Recommended quantity must be at least 0.',
            'recommended_quantity.max' => 'Recommended quantity cannot exceed 999,999.',
            'required_by.after_or_equal' => 'Required by date cannot be in the past.',
            'priority.in' => 'Please select a valid priority level.',
            'requirement_category.in' => 'Please select a valid requirement category.',
            'estimated_cost.min' => 'Estimated cost must be greater than or equal to 0.',
            'estimated_cost.regex' => 'Estimated cost must be a valid decimal number with up to 2 decimal places.',
            'budget_allocation.min' => 'Budget allocation must be greater than or equal to 0.',
            'budget_allocation.regex' => 'Budget allocation must be a valid decimal number with up to 2 decimal places.',
            'description.max' => 'Description cannot exceed 1000 characters.',
            'notes.max' => 'Notes cannot exceed 2000 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'school_id' => 'school',
            'academic_year_id' => 'academic year',
            'class_id' => 'class',
            'product_id' => 'product',
            'min_quantity' => 'minimum quantity',
            'max_quantity' => 'maximum quantity',
            'recommended_quantity' => 'recommended quantity',
            'required_by' => 'required by date',
            'estimated_cost' => 'estimated cost',
            'budget_allocation' => 'budget allocation',
            'requirement_category' => 'category',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        $this->merge([
            'is_required' => $this->is_required ?? false,
            'is_active' => $this->is_active ?? true,
            'priority' => $this->priority ?? 'medium',
            'requirement_category' => $this->requirement_category ?? 'other',
            'created_by' => $this->user()?->id,
        ]);

        // Clean and format numeric data
        if ($this->has('estimated_cost')) {
            $this->merge([
                'estimated_cost' => round((float) $this->estimated_cost, 2),
            ]);
        }

        if ($this->has('budget_allocation')) {
            $this->merge([
                'budget_allocation' => round((float) $this->budget_allocation, 2),
            ]);
        }

        // Set recommended quantity defaults based on min/max
        if (! $this->has('recommended_quantity') || is_null($this->recommended_quantity)) {
            if ($this->has('min_quantity') && $this->min_quantity > 0) {
                $this->merge([
                    'recommended_quantity' => $this->min_quantity,
                ]);
            }
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check for duplicate requirements (same school, year, class, product)
            $exists = \App\Models\ClassProductRequirement::where('school_id', $this->input('school_id'))
                ->where('academic_year_id', $this->input('academic_year_id'))
                ->where('class_id', $this->input('class_id'))
                ->where('product_id', $this->input('product_id'))
                ->exists();

            if ($exists) {
                $validator->errors()->add('product_id', 'This product requirement already exists for the selected class and academic year.');
            }

            // Validate quantity relationships
            $minQty = $this->input('min_quantity', 0);
            $maxQty = $this->input('max_quantity');
            $recQty = $this->input('recommended_quantity', 0);

            if ($recQty > 0 && $minQty > 0 && $recQty < $minQty) {
                $validator->errors()->add('recommended_quantity', 'Recommended quantity should not be less than minimum quantity.');
            }

            if ($recQty > 0 && $maxQty > 0 && $recQty > $maxQty) {
                $validator->errors()->add('recommended_quantity', 'Recommended quantity should not be greater than maximum quantity.');
            }

            // Validate budget vs estimated cost relationship
            $estimatedCost = $this->input('estimated_cost', 0);
            $budgetAllocation = $this->input('budget_allocation', 0);

            if ($estimatedCost > 0 && $budgetAllocation > 0 && $budgetAllocation < $estimatedCost) {
                $validator->errors()->add('budget_allocation', 'Budget allocation should not be less than estimated cost.');
            }

            // Check if the class belongs to the selected school
            if ($this->input('school_id') && $this->input('class_id')) {
                $classExists = \App\Models\SchoolClass::where('id', $this->input('class_id'))
                    ->where('school_id', $this->input('school_id'))
                    ->exists();

                if (! $classExists) {
                    $validator->errors()->add('class_id', 'The selected class does not belong to the selected school.');
                }
            }

            // Check if the academic year belongs to the selected school
            if ($this->input('school_id') && $this->input('academic_year_id')) {
                $yearExists = \App\Models\AcademicYear::where('id', $this->input('academic_year_id'))
                    ->where('school_id', $this->input('school_id'))
                    ->exists();

                if (! $yearExists) {
                    $validator->errors()->add('academic_year_id', 'The selected academic year does not belong to the selected school.');
                }
            }

            // Validate permission for high-budget items
            $totalEstimated = $estimatedCost * max($recQty, $minQty, 1);
            if ($totalEstimated > 10000 && ! $this->user()->hasPermissionTo('approve_class_requirements')) {
                $validator->errors()->add('estimated_cost', 'Requirements with total estimated cost over $10,000 require additional approval.');
            }
        });
    }
}
