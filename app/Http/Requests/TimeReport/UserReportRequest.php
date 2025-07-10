<?php

namespace App\Http\Requests\TimeReport;

use Illuminate\Foundation\Http\FormRequest;

class UserReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
        ];
    }
}