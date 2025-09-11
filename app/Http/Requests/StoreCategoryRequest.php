<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Category::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // Unique name within the same parent level
                Rule::unique('categories')->where(function ($query) {
                    return $query->where('parent_id', $this->parent_id);
                }),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                'unique:categories,slug',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'parent_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],
            'sort_order' => [
                'nullable',
                'integer',
                'min:0',
                'max:99999',
            ],
            'is_active' => [
                'boolean',
            ],
            'color' => [
                'nullable',
                'string',
                'max:50',
            ],
            'icon' => [
                'nullable',
                'string',
                'max:50',
            ],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The category name is required.',
            'name.unique' => 'A category with this name already exists at this level.',
            'slug.regex' => 'The slug must contain only lowercase letters, numbers, and hyphens.',
            'slug.unique' => 'This slug is already in use.',
            'parent_id.exists' => 'The selected parent category does not exist.',
            'sort_order.min' => 'Sort order must be a positive number.',
            'sort_order.max' => 'Sort order cannot exceed 99999.',
            'description.max' => 'Description cannot exceed 1000 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'parent_id' => 'parent category',
            'sort_order' => 'sort order',
            'is_active' => 'active status',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'sort_order' => $this->integer('sort_order', 0),
        ]);

        // Convert empty parent_id to null
        if ($this->parent_id === '' || $this->parent_id === '0') {
            $this->merge(['parent_id' => null]);
        }
    }

    /**
     * Handle a passed validation attempt.
     */
    public function passedValidation(): void
    {
        // Additional validation after basic rules pass
        if ($this->parent_id) {
            $this->validateParentCategory();
        }
    }

    /**
     * Validate the parent category selection.
     */
    protected function validateParentCategory(): void
    {
        $parent = Category::find($this->parent_id);

        if (! $parent) {
            return; // This will be caught by the exists rule
        }

        // Check if parent is active
        if (! $parent->is_active) {
            $this->validator->errors()->add(
                'parent_id',
                'Cannot create a category under an inactive parent category.'
            );
        }

        // Check maximum depth (prevent too deep nesting)
        if ($parent->getDepth() >= 4) { // Max 5 levels deep
            $this->validator->errors()->add(
                'parent_id',
                'Cannot create categories more than 5 levels deep.'
            );
        }
    }
}
