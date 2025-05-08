<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTimeEntryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization will be handled by the controller using model policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'task_id' => 'nullable|exists:tasks,id',
            'project_id' => 'nullable|exists:projects,id',
            'category_id' => 'nullable|exists:time_categories,id',
            'description' => 'nullable|string|max:255',
            'is_billable' => 'boolean',
            'tags' => 'nullable|array',
            'date' => 'nullable|date',
            'start_time' => 'nullable|required_with:date|date_format:H:i',
            'end_time' => 'nullable|required_with:date|date_format:H:i|after:start_time',
            'started_at' => 'nullable|date',
            'ended_at' => 'nullable|date|after:started_at',
            'duration' => 'nullable|integer|min:0',
        ];
    }
}
