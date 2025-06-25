<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DemoDataRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Permitir a todos los usuarios autenticados del tenant
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'scenario' => 'nullable|string',
            'start_date' => 'nullable|date',
            'skip_time_entries' => 'boolean',
            'only_structure' => 'boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'start_date.date' => 'La fecha de inicio debe ser una fecha válida.',
        ];
    }
}