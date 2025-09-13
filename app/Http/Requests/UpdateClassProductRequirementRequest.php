<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClassProductRequirementRequest extends FormRequest
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
        $requirementId = $this->route('classProductRequirement')?->id ?? $this->route('id');

        return [
            // Requirement specifications
            'is_required' => [
                'sometimes',
                'boolean',
            ],
            'min_quantity' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
                'max:999999',
            ],
            'max_quantity' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
                'max:999999',
                'gte:min_quantity',
            ],
            'recommended_quantity' => [
                'sometimes',
                'nullable',
                'integer',
                'min:0',
                'max:999999',
            ],

            // Timeline
            'required_by' => [
                'sometimes',
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
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'budget_allocation' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],

            // Additional information
            'description' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
            ],
            'notes' => [
                'sometimes',
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
        // Add updated_by field
        $this->merge([
            'updated_by' => $this->user()?->id,
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
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $requirement = $this->route('classProductRequirement');

            if (! $requirement) {
                $validator->errors()->add('requirement', 'Class product requirement not found.');

                return;
            }

            // Prevent updating approved requirements without proper permission
            if ($requirement->is_approved && ! $this->user()->hasPermissionTo('approve_class_requirements')) {
                $validator->errors()->add('requirement', 'Cannot update approved requirements without proper authorization.');

                return;
            }

            // Validate quantity relationships
            $minQty = $this->input('min_quantity', $requirement->min_quantity ?? 0);
            $maxQty = $this->input('max_quantity', $requirement->max_quantity);
            $recQty = $this->input('recommended_quantity', $requirement->recommended_quantity ?? 0);

            if ($recQty > 0 && $minQty > 0 && $recQty < $minQty) {
                $validator->errors()->add('recommended_quantity', 'Recommended quantity should not be less than minimum quantity.');
            }

            if ($recQty > 0 && $maxQty > 0 && $recQty > $maxQty) {
                $validator->errors()->add('recommended_quantity', 'Recommended quantity should not be greater than maximum quantity.');
            }

            // Validate budget vs estimated cost relationship
            $estimatedCost = $this->input('estimated_cost', $requirement->estimated_cost ?? 0);
            $budgetAllocation = $this->input('budget_allocation', $requirement->budget_allocation ?? 0);

            if ($estimatedCost > 0 && $budgetAllocation > 0 && $budgetAllocation < $estimatedCost) {
                $validator->errors()->add('budget_allocation', 'Budget allocation should not be less than estimated cost.');
            }

            // Validate permission for high-budget items
            $totalEstimated = $estimatedCost * max($recQty, $minQty, 1);
            if ($totalEstimated > 10000 && ! $this->user()->hasPermissionTo('approve_class_requirements')) {
                $validator->errors()->add('estimated_cost', 'Requirements with total estimated cost over $10,000 require additional approval.');
            }

            // Prevent deactivating required items close to deadline
            if ($this->input('is_active') === false && $requirement->is_required && $requirement->required_by) {
                $daysUntilDeadline = now()->diffInDays($requirement->required_by, false);
                if ($daysUntilDeadline <= 30 && $daysUntilDeadline >= 0) {
                    $validator->errors()->add('is_active', 'Cannot deactivate required items within 30 days of their deadline.');
                }
            }

            // Validate that critical priority items have a deadline
            if ($this->input('priority') === 'critical' && ! $this->input('required_by') && ! $requirement->required_by) {
                $validator->errors()->add('required_by', 'Critical priority items must have a required by date.');
            }
        });
    }
}
