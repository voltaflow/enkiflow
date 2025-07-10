<?php

namespace App\Http\Requests\TimeReport;

use Illuminate\Foundation\Http\FormRequest;

class DateRangeReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'filters' => 'sometimes|array',
            'filters.project_id' => 'sometimes|integer|exists:projects,id',
            'filters.user_id' => 'sometimes|integer|exists:users,id',
        ];
    }
}