<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->can('manage_roles');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $roleId = $this->route('role')?->id ?? $this->role;

        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($roleId),
                'regex:/^[a-z_]+$/', // Only lowercase letters and underscores
            ],
            'guard_name' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'permissions' => [
                'nullable',
                'array',
            ],
            'permissions.*' => [
                'string',
                'exists:permissions,name',
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
            'name.required' => 'The role name is required.',
            'name.string' => 'The role name must be a string.',
            'name.max' => 'The role name may not be greater than 255 characters.',
            'name.unique' => 'This role name already exists.',
            'name.regex' => 'The role name may only contain lowercase letters and underscores.',
            'guard_name.string' => 'The guard name must be a string.',
            'guard_name.max' => 'The guard name may not be greater than 255 characters.',
            'permissions.array' => 'The permissions must be an array.',
            'permissions.*.string' => 'Each permission must be a string.',
            'permissions.*.exists' => 'One or more selected permissions do not exist.',
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
            'guard_name' => 'guard name',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert name to lowercase and replace spaces with underscores if name is being updated
        if ($this->has('name')) {
            $this->merge([
                'name' => strtolower(str_replace(' ', '_', $this->name)),
            ]);
        }
    }
}
