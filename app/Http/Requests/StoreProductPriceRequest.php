<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductPriceRequest extends FormRequest
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

        return $user->hasPermissionTo('manage_pricing');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Product relationship
            'product_id' => [
                'required',
                'integer',
                'exists:products,id',
            ],

            // Price information
            'price' => [
                'required',
                'numeric',
                'min:0',
                'max:999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'cost_price' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'markup_percentage' => [
                'nullable',
                'numeric',
                'min:0',
                'max:1000',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],

            // Validity period
            'valid_from' => [
                'required',
                'date',
                'after_or_equal:today',
            ],
            'valid_to' => [
                'nullable',
                'date',
                'after:valid_from',
            ],

            // Currency
            'currency' => [
                'sometimes',
                'string',
                'size:3',
                'regex:/^[A-Z]{3}$/',
                Rule::in(['USD', 'EUR', 'GBP', 'INR', 'CAD', 'AUD', 'JPY']),
            ],

            // Status
            'status' => [
                'sometimes',
                'string',
                Rule::in(['draft', 'pending', 'active']),
            ],

            // Bulk discounts
            'bulk_discounts' => [
                'nullable',
                'array',
                'max:10',
            ],
            'bulk_discounts.*.min_quantity' => [
                'required_with:bulk_discounts',
                'integer',
                'min:1',
                'max:999999',
            ],
            'bulk_discounts.*.discount_percentage' => [
                'required_with:bulk_discounts',
                'numeric',
                'min:0.01',
                'max:75',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],

            // Notes
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'product_id.required' => 'Please select a product.',
            'product_id.exists' => 'The selected product does not exist.',
            'price.required' => 'Price is required.',
            'price.min' => 'Price must be greater than or equal to 0.',
            'price.regex' => 'Price must be a valid decimal number with up to 2 decimal places.',
            'cost_price.min' => 'Cost price must be greater than or equal to 0.',
            'cost_price.regex' => 'Cost price must be a valid decimal number with up to 2 decimal places.',
            'markup_percentage.max' => 'Markup percentage cannot exceed 1000%.',
            'markup_percentage.regex' => 'Markup percentage must be a valid decimal number with up to 2 decimal places.',
            'valid_from.required' => 'Valid from date is required.',
            'valid_from.after_or_equal' => 'Valid from date cannot be in the past.',
            'valid_to.after' => 'Valid to date must be after the valid from date.',
            'currency.size' => 'Currency must be a 3-letter code.',
            'currency.regex' => 'Currency must be in uppercase letters.',
            'currency.in' => 'Please select a valid currency.',
            'bulk_discounts.max' => 'You can add a maximum of 10 bulk discount tiers.',
            'bulk_discounts.*.min_quantity.required_with' => 'Minimum quantity is required for bulk discounts.',
            'bulk_discounts.*.min_quantity.min' => 'Minimum quantity must be at least 1.',
            'bulk_discounts.*.discount_percentage.required_with' => 'Discount percentage is required for bulk discounts.',
            'bulk_discounts.*.discount_percentage.min' => 'Discount percentage must be at least 0.01%.',
            'bulk_discounts.*.discount_percentage.max' => 'Discount percentage cannot exceed 75%.',
            'bulk_discounts.*.discount_percentage.regex' => 'Discount percentage must be a valid decimal number with up to 2 decimal places.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'product_id' => 'product',
            'valid_from' => 'valid from date',
            'valid_to' => 'valid to date',
            'cost_price' => 'cost price',
            'markup_percentage' => 'markup percentage',
            'bulk_discounts.*.min_quantity' => 'minimum quantity',
            'bulk_discounts.*.discount_percentage' => 'discount percentage',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Set default values
        $this->merge([
            'status' => $this->status ?? 'draft',
            'currency' => $this->currency ?? 'USD',
            'created_by' => $this->user()?->id,
        ]);

        // Clean and format numeric data
        if ($this->has('price')) {
            $this->merge([
                'price' => round((float) $this->price, 2),
            ]);
        }

        if ($this->has('cost_price')) {
            $this->merge([
                'cost_price' => round((float) $this->cost_price, 2),
            ]);
        }

        if ($this->has('markup_percentage')) {
            $this->merge([
                'markup_percentage' => round((float) $this->markup_percentage, 2),
            ]);
        }

        // Sort bulk discounts by min_quantity
        if ($this->has('bulk_discounts') && is_array($this->bulk_discounts)) {
            $discounts = collect($this->bulk_discounts)
                ->sortBy('min_quantity')
                ->values()
                ->all();

            $this->merge(['bulk_discounts' => $discounts]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check for permission to set active status immediately
            if ($this->input('status') === 'active' && ! $this->user()->hasPermissionTo('approve_price_changes')) {
                $validator->errors()->add('status', 'You do not have permission to set prices as active. Status will be set to pending for approval.');
                // Auto-correct to pending
                $this->merge(['status' => 'pending']);
            }

            // Validate bulk discount tiers don't overlap or conflict
            $bulkDiscounts = $this->input('bulk_discounts', []);
            if (count($bulkDiscounts) > 1) {
                $quantities = collect($bulkDiscounts)->pluck('min_quantity');
                if ($quantities->count() !== $quantities->unique()->count()) {
                    $validator->errors()->add('bulk_discounts', 'Bulk discount tiers cannot have duplicate minimum quantities.');
                }

                // Check that discount percentages increase with quantity
                $sorted = collect($bulkDiscounts)->sortBy('min_quantity');
                $previousDiscount = 0;
                foreach ($sorted as $index => $tier) {
                    if ($tier['discount_percentage'] < $previousDiscount) {
                        $validator->errors()->add("bulk_discounts.{$index}.discount_percentage", 'Discount percentage should increase with higher quantities.');
                    }
                    $previousDiscount = $tier['discount_percentage'];
                }
            }

            // Validate cost price vs selling price relationship
            $price = $this->input('price');
            $costPrice = $this->input('cost_price');
            if ($price && $costPrice && $price < $costPrice) {
                $validator->errors()->add('price', 'Selling price cannot be lower than cost price.');
            }

            // Validate markup percentage matches calculated markup
            if ($price && $costPrice && $this->has('markup_percentage')) {
                $calculatedMarkup = (($price - $costPrice) / $costPrice) * 100;
                $providedMarkup = $this->input('markup_percentage');

                if (abs($calculatedMarkup - $providedMarkup) > 0.1) {
                    $validator->errors()->add('markup_percentage', 'Markup percentage does not match the price and cost price relationship.');
                }
            }
        });
    }
}
