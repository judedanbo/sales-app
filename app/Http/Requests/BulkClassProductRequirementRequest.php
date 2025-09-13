<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkClassProductRequirementRequest extends FormRequest
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
        return [
            // Action to perform
            'action' => [
                'required',
                'string',
                Rule::in([
                    'activate',
                    'deactivate',
                    'approve',
                    'delete',
                    'copy_to_year',
                    'update_priority',
                    'update_category',
                    'extend_deadline',
                    'export',
                ]),
            ],

            // Requirement IDs to act upon
            'requirement_ids' => [
                'required',
                'array',
                'min:1',
                'max:500', // Prevent overwhelming bulk operations
            ],
            'requirement_ids.*' => [
                'integer',
                'exists:class_product_requirements,id',
            ],

            // Optional data for specific actions
            'data' => [
                'sometimes',
                'array',
            ],

            // For copy_to_year action
            'data.target_academic_year_id' => [
                'required_if:action,copy_to_year',
                'integer',
                'exists:academic_years,id',
            ],

            // For update_priority action
            'data.priority' => [
                'required_if:action,update_priority',
                'string',
                Rule::in(['low', 'medium', 'high', 'critical']),
            ],

            // For update_category action
            'data.requirement_category' => [
                'required_if:action,update_category',
                'string',
                Rule::in(['textbooks', 'stationery', 'uniforms', 'supplies', 'technology', 'sports', 'art', 'science', 'other']),
            ],

            // For extend_deadline action
            'data.extend_days' => [
                'required_if:action,extend_deadline',
                'integer',
                'min:1',
                'max:365',
            ],
            'data.new_deadline' => [
                'sometimes',
                'date',
                'after:today',
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
                Rule::in(['school', 'academicYear', 'schoolClass', 'product', 'creator', 'approver']),
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
            'requirement_ids.required' => 'Please select at least one requirement.',
            'requirement_ids.min' => 'Please select at least one requirement.',
            'requirement_ids.max' => 'You cannot perform bulk actions on more than 500 requirements at once.',
            'requirement_ids.*.exists' => 'One or more selected requirements do not exist.',
            'data.target_academic_year_id.required_if' => 'Please select a target academic year when copying requirements.',
            'data.target_academic_year_id.exists' => 'The selected academic year does not exist.',
            'data.priority.required_if' => 'Please specify the priority when updating priority.',
            'data.priority.in' => 'Please select a valid priority level.',
            'data.requirement_category.required_if' => 'Please specify the category when updating category.',
            'data.requirement_category.in' => 'Please select a valid requirement category.',
            'data.extend_days.required_if' => 'Please specify how many days to extend when extending deadline.',
            'data.extend_days.min' => 'Extension must be at least 1 day.',
            'data.extend_days.max' => 'Extension cannot exceed 365 days.',
            'data.new_deadline.after' => 'New deadline must be in the future.',
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
            'requirement_ids' => 'requirements',
            'data.target_academic_year_id' => 'target academic year',
            'data.priority' => 'priority',
            'data.requirement_category' => 'category',
            'data.extend_days' => 'extension days',
            'data.new_deadline' => 'new deadline',
            'data.format' => 'export format',
            'schedule_for' => 'scheduled time',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure requirement_ids are unique
        if ($this->has('requirement_ids')) {
            $this->merge([
                'requirement_ids' => array_unique($this->input('requirement_ids', [])),
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

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $action = $this->input('action');
            $user = $this->user();
            $requirementIds = $this->input('requirement_ids', []);

            // Check specific permissions for different actions
            switch ($action) {
                case 'approve':
                    if (! $user->hasPermissionTo('approve_class_requirements')) {
                        $validator->errors()->add('action', 'You do not have permission to approve requirements.');
                    }
                    break;

                case 'delete':
                    if (! $user->hasPermissionTo('delete_class_requirements')) {
                        $validator->errors()->add('action', 'You do not have permission to delete requirements.');
                    }
                    break;

                case 'copy_to_year':
                    if (! $user->hasPermissionTo('copy_class_requirements')) {
                        $validator->errors()->add('action', 'You do not have permission to copy requirements.');
                    }
                    break;

                case 'export':
                    if (! $user->hasPermissionTo('export_class_requirements')) {
                        $validator->errors()->add('action', 'You do not have permission to export requirements.');
                    }
                    break;
            }

            // Validate that all requirements belong to schools the user has access to
            if (! empty($requirementIds)) {
                $requirements = \App\Models\ClassProductRequirement::whereIn('id', $requirementIds)->get();

                // Check if user can access all the schools in the requirements
                $schoolIds = $requirements->pluck('school_id')->unique();
                foreach ($schoolIds as $schoolId) {
                    if (! $user->can('view', \App\Models\School::find($schoolId))) {
                        $validator->errors()->add('requirement_ids', 'You do not have access to some of the selected requirements.');
                        break;
                    }
                }

                // Additional validations based on action
                if ($action === 'approve') {
                    $alreadyApproved = $requirements->where('approved_at', '!=', null)->count();
                    if ($alreadyApproved > 0) {
                        $validator->errors()->add('requirement_ids', "{$alreadyApproved} of the selected requirements are already approved.");
                    }
                }

                if ($action === 'copy_to_year') {
                    $targetYearId = $this->input('data.target_academic_year_id');
                    if ($targetYearId) {
                        // Check if target year belongs to the same schools
                        foreach ($schoolIds as $schoolId) {
                            $yearExists = \App\Models\AcademicYear::where('id', $targetYearId)
                                ->where('school_id', $schoolId)
                                ->exists();
                            if (! $yearExists) {
                                $validator->errors()->add('data.target_academic_year_id', 'The selected academic year does not belong to all schools in the selected requirements.');
                                break;
                            }
                        }
                    }
                }

                if ($action === 'extend_deadline') {
                    $withoutDeadlines = $requirements->whereNull('required_by')->count();
                    if ($withoutDeadlines > 0) {
                        $validator->errors()->add('requirement_ids', "{$withoutDeadlines} of the selected requirements do not have deadlines to extend.");
                    }
                }

                // Validate bulk operation limits based on action
                $requirementCount = count($requirementIds);
                if ($action === 'delete' && $requirementCount > 100) {
                    $validator->errors()->add('requirement_ids', 'You cannot delete more than 100 requirements at once.');
                }

                if ($action === 'copy_to_year' && $requirementCount > 200) {
                    $validator->errors()->add('requirement_ids', 'You cannot copy more than 200 requirements at once.');
                }
            }

            // Validate deadline extension logic
            if ($action === 'extend_deadline') {
                $extendDays = $this->input('data.extend_days');
                $newDeadline = $this->input('data.new_deadline');

                if ($extendDays && $newDeadline) {
                    $validator->errors()->add('data.extend_days', 'Cannot specify both extension days and new deadline. Choose one method.');
                }

                if (! $extendDays && ! $newDeadline) {
                    $validator->errors()->add('data.extend_days', 'Must specify either extension days or new deadline.');
                }
            }
        });
    }
}
