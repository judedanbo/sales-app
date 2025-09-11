<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $category = $this->route('category');

        return $this->user()->can('update', $category);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                // Unique name within the same parent level, excluding current category
                Rule::unique('categories')->where(function ($query) {
                    return $query->where('parent_id', $this->parent_id);
                })->ignore($category->id),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('categories')->ignore($category->id),
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
                // Prevent setting self as parent
                Rule::notIn([$category->id]),
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
            'parent_id.not_in' => 'A category cannot be its own parent.',
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
        $category = $this->route('category');

        // Additional validation after basic rules pass
        if ($this->parent_id) {
            $this->validateParentCategory($category);
        }

        // Validate deactivation
        if (! $this->boolean('is_active') && $category->is_active) {
            $this->validateDeactivation($category);
        }
    }

    /**
     * Validate the parent category selection.
     */
    protected function validateParentCategory(Category $category): void
    {
        $parent = Category::find($this->parent_id);

        if (! $parent) {
            return; // This will be caught by the exists rule
        }

        // Check if parent is active
        if (! $parent->is_active) {
            $this->validator->errors()->add(
                'parent_id',
                'Cannot move category under an inactive parent category.'
            );
        }

        // Check for circular reference
        if ($category->wouldCreateCircularReference($this->parent_id)) {
            $this->validator->errors()->add(
                'parent_id',
                'Cannot move category under one of its own descendants.'
            );
        }

        // Check maximum depth after moving
        if ($parent->getDepth() >= 4) { // Max 5 levels deep
            $this->validator->errors()->add(
                'parent_id',
                'Cannot move category to more than 5 levels deep.'
            );
        }
    }

    /**
     * Validate category deactivation.
     */
    protected function validateDeactivation(Category $category): void
    {
        // Check if category has active children
        if ($category->activeChildren()->exists()) {
            $this->validator->errors()->add(
                'is_active',
                'Cannot deactivate a category that has active child categories.'
            );
        }

        // Check if category has active products (when Product model exists)
        if (class_exists('App\Models\Product')) {
            $activeProductsCount = $category->products()->where('status', 'active')->count();
            if ($activeProductsCount > 0) {
                $this->validator->errors()->add(
                    'is_active',
                    "Cannot deactivate a category that contains {$activeProductsCount} active product(s)."
                );
            }
        }
    }
}
