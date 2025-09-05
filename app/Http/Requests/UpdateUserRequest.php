<?php

namespace App\Http\Requests;

use App\Enums\UserType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->can('edit_users');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->user;

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email:rfc,dns',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => [
                'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
            'user_type' => [
                'sometimes',
                'required',
                Rule::enum(UserType::class),
            ],
            'school_id' => [
                'nullable',
                'integer',
                'exists:schools,id',
                function ($attribute, $value, $fail) {
                    // Get user_type from request or current user
                    $userType = null;
                    if ($this->has('user_type')) {
                        $userType = UserType::tryFrom($this->user_type);
                    } else {
                        // Get current user_type if not being updated
                        $user = $this->route('user');
                        if ($user) {
                            $userType = $user->user_type;
                        }
                    }

                    if ($userType) {
                        // School-specific user types must have school_id
                        if (in_array($userType, [UserType::SCHOOL_ADMIN, UserType::PRINCIPAL, UserType::TEACHER])) {
                            if (empty($value)) {
                                $fail('School is required for '.$userType->label().' users.');
                            }
                        }

                        // System-wide user types should not have school_id
                        if (in_array($userType, [UserType::STAFF, UserType::ADMIN, UserType::AUDIT, UserType::SYSTEM_ADMIN])) {
                            if (! empty($value)) {
                                $fail('School should not be assigned to '.$userType->label().' users.');
                            }
                        }
                    }
                },
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
            ],
            'department' => [
                'nullable',
                'string',
                'max:100',
            ],
            'bio' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'is_active' => [
                'sometimes',
                'boolean',
                function ($attribute, $value, $fail) {
                    // Prevent deactivating current user
                    $user = $this->route('user');
                    if ($user && $user->id === auth()->id() && ! $value) {
                        $fail('You cannot deactivate your own account.');
                    }
                },
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The full name is required.',
            'name.max' => 'The full name may not be greater than 255 characters.',
            'email.required' => 'The email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'password.min' => 'The password must be at least 8 characters long.',
            'password.confirmed' => 'The password confirmation does not match.',
            'user_type.required' => 'The user type is required.',
            'school_id.exists' => 'The selected school does not exist.',
            'phone.max' => 'The phone number may not be greater than 20 characters.',
            'department.max' => 'The department may not be greater than 100 characters.',
            'bio.max' => 'The bio may not be greater than 1000 characters.',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'user_type' => 'user type',
            'school_id' => 'school',
            'is_active' => 'active status',
        ];
    }
}
