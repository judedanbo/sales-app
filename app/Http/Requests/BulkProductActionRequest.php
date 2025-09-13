<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkProductActionRequest extends FormRequest
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

        // Check if user has bulk edit permission
        return $user->hasPermissionTo('bulk_edit_products');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Action to perform
            'action' => [
                'required',
                'string',
                Rule::in([
                    'activate',
                    'deactivate',
                    'discontinue',
                    'delete',
                    'restore',
                    'force_delete',
                    'change_category',
                    'update_pricing',
                    'export',
                ]),
            ],

            // Product IDs to act upon
            'product_ids' => [
                'required',
                'array',
                'min:1',
                'max:1000', // Prevent overwhelming bulk operations
            ],
            'product_ids.*' => [
                'integer',
                'exists:products,id',
            ],

            // Optional data for specific actions
            'data' => [
                'sometimes',
                'array',
            ],

            // For change_category action
            'data.category_id' => [
                'required_if:action,change_category',
                'integer',
                'exists:categories,id',
            ],

            // For update_pricing action
            'data.price_adjustment_type' => [
                'required_if:action,update_pricing',
                'string',
                Rule::in(['percentage', 'fixed_amount', 'set_price']),
            ],
            'data.price_adjustment_value' => [
                'required_if:action,update_pricing',
                'numeric',
                'min:0',
            ],
            'data.apply_to_tax' => [
                'sometimes',
                'boolean',
            ],

            // For export action
            'data.format' => [
                'required_if:action,export',
                'string',
                Rule::in(['csv', 'xlsx', 'json']),
            ],
            'data.include_relationships' => [
                'sometimes',
                'array',
            ],
            'data.include_relationships.*' => [
                'string',
                Rule::in(['category', 'prices', 'inventory', 'requirements']),
            ],

            // General options
            'reason' => [
                'sometimes',
                'string',
                'max:500',
            ],
            'notify_users' => [
                'sometimes',
                'boolean',
            ],
            'schedule_for' => [
                'sometimes',
                'date',
                'after:now',
            ],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Please specify the action to perform.',
            'action.in' => 'The selected action is not valid.',
            'product_ids.required' => 'Please select at least one product.',
            'product_ids.min' => 'Please select at least one product.',
            'product_ids.max' => 'You cannot perform bulk actions on more than 1000 products at once.',
            'product_ids.*.exists' => 'One or more selected products do not exist.',
            'data.category_id.required_if' => 'Please select a category when changing product categories.',
            'data.category_id.exists' => 'The selected category does not exist.',
            'data.price_adjustment_type.required_if' => 'Please specify the price adjustment type.',
            'data.price_adjustment_value.required_if' => 'Please specify the price adjustment value.',
            'data.price_adjustment_value.min' => 'Price adjustment value must be greater than or equal to 0.',
            'data.format.required_if' => 'Please specify the export format.',
            'data.format.in' => 'Please select a valid export format.',
            'schedule_for.after' => 'Scheduled time must be in the future.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'product_ids' => 'products',
            'data.category_id' => 'category',
            'data.price_adjustment_type' => 'price adjustment type',
            'data.price_adjustment_value' => 'price adjustment value',
            'data.format' => 'export format',
            'schedule_for' => 'scheduled time',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $action = $this->input('action');
            $user = $this->user();

            // Check specific permissions for different actions
            switch ($action) {
                case 'delete':
                    if (! $user->hasPermissionTo('delete_products')) {
                        $validator->errors()->add('action', 'You do not have permission to delete products.');
                    }
                    break;

                case 'restore':
                    if (! $user->hasPermissionTo('restore_products')) {
                        $validator->errors()->add('action', 'You do not have permission to restore products.');
                    }
                    break;

                case 'force_delete':
                    if (! $user->hasPermissionTo('force_delete_products')) {
                        $validator->errors()->add('action', 'You do not have permission to permanently delete products.');
                    }
                    break;

                case 'activate':
                case 'deactivate':
                case 'discontinue':
                    if (! $user->hasPermissionTo('manage_product_status')) {
                        $validator->errors()->add('action', 'You do not have permission to change product status.');
                    }
                    break;

                case 'change_category':
                    if (! $user->hasPermissionTo('manage_product_categories')) {
                        $validator->errors()->add('action', 'You do not have permission to change product categories.');
                    }
                    break;

                case 'update_pricing':
                    if (! $user->hasPermissionTo('bulk_update_pricing')) {
                        $validator->errors()->add('action', 'You do not have permission to update product pricing.');
                    }
                    break;

                case 'export':
                    if (! $user->hasPermissionTo('export_products')) {
                        $validator->errors()->add('action', 'You do not have permission to export products.');
                    }
                    break;
            }

            // Validate product limits based on action
            $productCount = count($this->input('product_ids', []));
            if ($action === 'force_delete' && $productCount > 100) {
                $validator->errors()->add('product_ids', 'You cannot permanently delete more than 100 products at once.');
            }

            // Validate price adjustment logic
            if ($action === 'update_pricing') {
                $adjustmentType = $this->input('data.price_adjustment_type');
                $adjustmentValue = $this->input('data.price_adjustment_value');

                if ($adjustmentType === 'percentage' && $adjustmentValue > 500) {
                    $validator->errors()->add('data.price_adjustment_value', 'Percentage adjustment cannot exceed 500%.');
                }

                if ($adjustmentType === 'fixed_amount' && $adjustmentValue > 10000) {
                    $validator->errors()->add('data.price_adjustment_value', 'Fixed amount adjustment cannot exceed $10,000.');
                }
            }
        });
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure product_ids are unique
        if ($this->has('product_ids')) {
            $this->merge([
                'product_ids' => array_unique($this->input('product_ids', [])),
            ]);
        }

        // Set default values for optional fields
        $this->merge([
            'notify_users' => $this->input('notify_users', false),
        ]);

        // Add user context
        $this->merge([
            'performed_by' => $this->user()?->id,
            'performed_at' => now(),
        ]);
    }
}
