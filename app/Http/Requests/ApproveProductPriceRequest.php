<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApproveProductPriceRequest extends FormRequest
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

        return $user->hasPermissionTo('approve_price_changes');
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
                Rule::in(['approve', 'reject']),
            ],

            // Approval/rejection notes
            'notes' => [
                'required_if:action,reject',
                'nullable',
                'string',
                'max:1000',
            ],

            // Optional: override valid_from date when approving
            'valid_from' => [
                'sometimes',
                'date',
                'after_or_equal:today',
            ],

            // Optional: set valid_to date when approving
            'valid_to' => [
                'sometimes',
                'nullable',
                'date',
                'after:valid_from',
            ],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Please specify whether to approve or reject this price.',
            'action.in' => 'Action must be either approve or reject.',
            'notes.required_if' => 'Please provide a reason for rejecting this price.',
            'notes.max' => 'Notes cannot exceed 1000 characters.',
            'valid_from.after_or_equal' => 'Valid from date cannot be in the past.',
            'valid_to.after' => 'Valid to date must be after the valid from date.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'valid_from' => 'valid from date',
            'valid_to' => 'valid to date',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Add approver information
        $this->merge([
            'approved_by' => $this->user()?->id,
            'approved_at' => now(),
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $productPrice = $this->route('productPrice');

            if (! $productPrice) {
                $validator->errors()->add('product_price', 'Product price not found.');

                return;
            }

            // Can only approve/reject pending prices
            if ($productPrice->status !== 'pending') {
                $validator->errors()->add('action', 'Only pending prices can be approved or rejected.');

                return;
            }

            // Check for conflicting active prices when approving
            if ($this->input('action') === 'approve') {
                $validFrom = $this->input('valid_from', $productPrice->valid_from);
                $validTo = $this->input('valid_to', $productPrice->valid_to);

                $conflictingPrices = $productPrice->product
                    ->productPrices()
                    ->where('id', '!=', $productPrice->id)
                    ->where('status', 'active')
                    ->where(function ($query) use ($validFrom, $validTo) {
                        $query->where(function ($q) use ($validFrom) {
                            // Existing price starts before or at the same time as new price
                            $q->where('valid_from', '<=', $validFrom)
                                ->where(function ($innerQ) use ($validFrom) {
                                    $innerQ->whereNull('valid_to')
                                        ->orWhere('valid_to', '>', $validFrom);
                                });
                        })
                            ->orWhere(function ($q) use ($validFrom, $validTo) {
                                // Existing price starts within the new price's validity period
                                $q->where('valid_from', '>', $validFrom);
                                if ($validTo) {
                                    $q->where('valid_from', '<', $validTo);
                                }
                            });
                    })
                    ->exists();

                if ($conflictingPrices) {
                    $validator->errors()->add('valid_from', 'Approving this price would conflict with existing active prices. Please adjust the validity period.');
                }

                // Validate that approval won't create pricing gaps
                $hasOtherPrices = $productPrice->product
                    ->productPrices()
                    ->where('id', '!=', $productPrice->id)
                    ->where('status', 'active')
                    ->exists();

                if (! $hasOtherPrices && $validFrom > now()) {
                    $validator->errors()->add('valid_from', 'This would create a pricing gap. Consider setting the valid from date to today or ensure another price covers the interim period.');
                }
            }

            // Validate that rejector is not the same as creator (basic separation of duties)
            if ($this->input('action') === 'reject' && $productPrice->created_by === $this->user()->id) {
                $validator->errors()->add('action', 'You cannot reject your own price submission. Please have another authorized user review it.');
            }
        });
    }
}
