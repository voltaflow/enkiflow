<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $clientId = $this->route('client')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('clients')->ignore($clientId)->whereNull('deleted_at'),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'url', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'timezone' => ['nullable', 'timezone'],
            'currency' => ['nullable', 'string', 'size:3'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'email' => 'correo electrónico',
            'phone' => 'teléfono',
            'address' => 'dirección',
            'city' => 'ciudad',
            'state' => 'estado/provincia',
            'country' => 'país',
            'postal_code' => 'código postal',
            'website' => 'sitio web',
            'contact_name' => 'nombre de contacto',
            'contact_email' => 'correo de contacto',
            'contact_phone' => 'teléfono de contacto',
            'notes' => 'notas',
            'timezone' => 'zona horaria',
            'currency' => 'moneda',
            'is_active' => 'estado activo',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del cliente es obligatorio.',
            'name.max' => 'El nombre no puede tener más de 255 caracteres.',
            'email.email' => 'El correo electrónico debe ser una dirección válida.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'website.url' => 'El sitio web debe ser una URL válida.',
            'contact_email.email' => 'El correo de contacto debe ser una dirección válida.',
            'timezone.timezone' => 'La zona horaria debe ser válida.',
            'currency.size' => 'El código de moneda debe tener exactamente 3 caracteres.',
        ];
    }
}