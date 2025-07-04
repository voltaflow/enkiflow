<?php

namespace App\Http\Requests\Tenant;

use App\Enums\SpaceRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // La autorización se maneja en el controlador con policies
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role' => [
                'sometimes',
                'required',
                'string',
                Rule::in(array_column(SpaceRole::assignableRoles(), 'value'))
            ],
            'capacity_hours' => [
                'sometimes',
                'required',
                'integer',
                'min:0',
                'max:60'
            ],
            'cost_rate' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99'
            ],
            'billable_rate' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999.99'
            ],
            'status' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['active', 'invited', 'archived'])
            ],
            'custom_permissions' => [
                'nullable',
                'array'
            ],
            'custom_permissions.*' => [
                'string',
                'distinct'
            ],
            'additional_permissions' => [
                'nullable',
                'array'
            ],
            'additional_permissions.*' => [
                'string',
                'distinct'
            ],
            'revoked_permissions' => [
                'nullable',
                'array'
            ],
            'revoked_permissions.*' => [
                'string',
                'distinct'
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'role.in' => 'El rol seleccionado no es válido.',
            'capacity_hours.min' => 'La capacidad debe ser al menos 0 horas.',
            'capacity_hours.max' => 'La capacidad no puede exceder 60 horas semanales.',
            'cost_rate.min' => 'La tasa de costo debe ser positiva.',
            'cost_rate.max' => 'La tasa de costo es demasiado alta.',
            'billable_rate.min' => 'La tasa facturable debe ser positiva.',
            'billable_rate.max' => 'La tasa facturable es demasiado alta.',
            'status.in' => 'El estado seleccionado no es válido.',
        ];
    }
}