<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectAssignmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the controller
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'assignments' => 'required|array|min:1',
            'assignments.*.project_ids' => [
                'sometimes',
                'required_without_all:assignments.*.all_current_projects,assignments.*.all_future_projects',
                'array',
            ],
            'assignments.*.project_ids.*' => 'integer|exists:projects,id',
            'assignments.*.all_current_projects' => 'sometimes|boolean',
            'assignments.*.all_future_projects' => 'sometimes|boolean',
            'assignments.*.role' => [
                'sometimes',
                'string',
                Rule::in(['member', 'manager', 'viewer']),
            ],
            'assignments.*.custom_rate' => 'sometimes|nullable|numeric|min:0|max:9999.99',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'assignments.required' => 'At least one assignment must be provided.',
            'assignments.array' => 'Assignments must be an array.',
            'assignments.*.project_ids.required_without_all' => 'Either project IDs or all projects access must be specified.',
            'assignments.*.project_ids.*.exists' => 'One or more selected projects do not exist.',
            'assignments.*.role.in' => 'Role must be one of: member, manager, or viewer.',
            'assignments.*.custom_rate.numeric' => 'Custom rate must be a valid number.',
            'assignments.*.custom_rate.min' => 'Custom rate cannot be negative.',
            'assignments.*.custom_rate.max' => 'Custom rate cannot exceed 9999.99.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure assignments is always an array
        if ($this->has('assignments') && !is_array($this->assignments)) {
            $this->merge([
                'assignments' => [$this->assignments],
            ]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $assignments = $this->assignments ?? [];
            
            foreach ($assignments as $index => $assignment) {
                // Validate that each assignment has at least one type of assignment
                if (
                    empty($assignment['project_ids']) &&
                    empty($assignment['all_current_projects']) &&
                    empty($assignment['all_future_projects'])
                ) {
                    $validator->errors()->add(
                        "assignments.{$index}",
                        'Each assignment must specify either project IDs or all projects access.'
                    );
                }

                // Validate that all_current_projects and all_future_projects are not both true
                if (
                    !empty($assignment['all_current_projects']) &&
                    !empty($assignment['all_future_projects']) &&
                    count($assignments) > 1
                ) {
                    $validator->errors()->add(
                        "assignments.{$index}",
                        'Cannot have multiple assignments when assigning all current and future projects.'
                    );
                }

                // Validate that project_ids is not provided with all projects access
                if (
                    !empty($assignment['project_ids']) &&
                    (!empty($assignment['all_current_projects']) || !empty($assignment['all_future_projects']))
                ) {
                    $validator->errors()->add(
                        "assignments.{$index}",
                        'Cannot specify individual projects when assigning all projects access.'
                    );
                }
            }
        });
    }
}