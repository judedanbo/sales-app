<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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

        return $user->hasPermissionTo('create_products');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Core product information
            'sku' => [
                'nullable',
                'string',
                'max:50',
                'unique:products,sku',
                'regex:/^[A-Z0-9\-_]+$/',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                'min:2',
            ],
            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],

            // Category relationship
            'category_id' => [
                'required',
                'integer',
                'exists:categories,id',
            ],

            // Product status and configuration
            'status' => [
                'sometimes',
                'string',
                Rule::in(['active', 'inactive', 'discontinued']),
            ],
            'unit_price' => [
                'required',
                'numeric',
                'min:0',
                'max:999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'unit_type' => [
                'required',
                'string',
                'max:50',
                'in:piece,kg,gram,liter,ml,meter,cm,box,pack,set,dozen,pair',
            ],

            // Inventory management
            'reorder_level' => [
                'nullable',
                'integer',
                'min:0',
                'max:999999',
            ],
            'tax_rate' => [
                'sometimes',
                'numeric',
                'min:0',
                'max:1',
                'regex:/^\d+(\.\d{1,4})?$/',
            ],

            // Physical attributes
            'weight' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999.999',
            ],
            'dimensions' => [
                'nullable',
                'array',
            ],
            'dimensions.length' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999.999',
            ],
            'dimensions.width' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999.999',
            ],
            'dimensions.height' => [
                'nullable',
                'numeric',
                'min:0',
                'max:99999.999',
            ],
            'color' => [
                'nullable',
                'string',
                'max:100',
            ],
            'brand' => [
                'nullable',
                'string',
                'max:255',
            ],

            // Additional metadata
            'attributes' => [
                'nullable',
                'array',
            ],
            'attributes.*' => [
                'string',
                'max:500',
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:100',
                'unique:products,barcode',
            ],
            'image_url' => [
                'nullable',
                'string',
                'url',
                'max:2048',
            ],
            'gallery' => [
                'nullable',
                'array',
                'max:10',
            ],
            'gallery.*' => [
                'string',
                'url',
                'max:2048',
            ],

            // SEO and search
            'meta_title' => [
                'nullable',
                'string',
                'max:255',
            ],
            'meta_description' => [
                'nullable',
                'string',
                'max:500',
            ],
            'tags' => [
                'nullable',
                'array',
                'max:20',
            ],
            'tags.*' => [
                'string',
                'max:50',
                'distinct',
            ],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'sku.unique' => 'This SKU is already in use. Please choose a different one.',
            'sku.regex' => 'SKU must contain only uppercase letters, numbers, hyphens, and underscores.',
            'name.required' => 'Product name is required.',
            'name.min' => 'Product name must be at least 2 characters long.',
            'category_id.required' => 'Please select a product category.',
            'category_id.exists' => 'The selected category does not exist.',
            'unit_price.required' => 'Unit price is required.',
            'unit_price.min' => 'Unit price must be greater than or equal to 0.',
            'unit_price.regex' => 'Unit price must be a valid decimal number with up to 2 decimal places.',
            'unit_type.required' => 'Unit type is required.',
            'unit_type.in' => 'Please select a valid unit type.',
            'tax_rate.max' => 'Tax rate cannot exceed 100% (1.0).',
            'tax_rate.regex' => 'Tax rate must be a valid decimal number with up to 4 decimal places.',
            'barcode.unique' => 'This barcode is already in use.',
            'image_url.url' => 'Please provide a valid image URL.',
            'gallery.max' => 'You can upload a maximum of 10 gallery images.',
            'gallery.*.url' => 'Each gallery item must be a valid URL.',
            'tags.max' => 'You can add a maximum of 20 tags.',
            'tags.*.max' => 'Each tag must be no longer than 50 characters.',
            'tags.*.distinct' => 'Duplicate tags are not allowed.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'category_id' => 'category',
            'unit_price' => 'price',
            'unit_type' => 'unit',
            'reorder_level' => 'reorder level',
            'tax_rate' => 'tax rate',
            'meta_title' => 'SEO title',
            'meta_description' => 'SEO description',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        $this->merge([
            'status' => $this->status ?? 'active',
            'tax_rate' => $this->tax_rate ?? 0.0,
            'created_by' => $this->user()?->id,
        ]);

        // Clean and format data
        if ($this->has('unit_price')) {
            $this->merge([
                'unit_price' => round((float) $this->unit_price, 2),
            ]);
        }

        if ($this->has('weight')) {
            $this->merge([
                'weight' => round((float) $this->weight, 3),
            ]);
        }

        // Auto-generate SKU if not provided
        if (! $this->has('sku') || empty($this->sku)) {
            $this->merge([
                'sku' => $this->generateSku(),
            ]);
        }
    }

    /**
     * Generate SKU from product name
     */
    private function generateSku(): string
    {
        $name = $this->input('name', 'PRODUCT');
        $baseSku = strtoupper(str_replace([' ', '-'], '', substr($name, 0, 8)));

        // Add random suffix to ensure uniqueness
        return $baseSku.rand(100, 999);
    }
}
