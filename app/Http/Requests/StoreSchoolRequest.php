<?php

namespace App\Http\Requests;

use App\Enums\BoardAffiliation;
use App\Enums\MediumOfInstruction;
use App\Enums\SchoolType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSchoolRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Add proper authorization logic if needed
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'school_code' => [
                'required',
                'string',
                'max:50',
                'unique:schools,school_code',
                'regex:/^[A-Z0-9\-]+$/', // Only uppercase letters, numbers, and hyphens
            ],
            'school_name' => [
                'required',
                'string',
                'max:255',
                'min:3',
            ],
            'school_type' => [
                'required',
                Rule::enum(SchoolType::class),
            ],
            'board_affiliation' => [
                'nullable',
                Rule::enum(BoardAffiliation::class),
            ],
            // 'medium_of_instruction' => [
            //     'nullable',
            //     Rule::enum(MediumOfInstruction::class),
            // ],
            'established_date' => [
                'nullable',
                'date',
                'before:today',
                'after:1800-01-01', // Reasonable minimum date
            ],
            'is_active' => [
                'boolean',
            ],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'school_code.required' => 'The school code is required.',
            'school_code.unique' => 'This school code is already taken.',
            'school_code.regex' => 'The school code must contain only uppercase letters, numbers, and hyphens.',
            'school_name.required' => 'The school name is required.',
            'school_name.min' => 'The school name must be at least 3 characters.',
            'school_type.required' => 'The school type is required.',
            'established_date.before' => 'The established date must be before today.',
            'established_date.after' => 'The established date must be after 1800.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'school_code' => 'school code',
            'school_name' => 'school name',
            'school_type' => 'school type',
            'board_affiliation' => 'board affiliation',
            'established_date' => 'established date',
            'is_active' => 'active status',
        ];
    }
}
