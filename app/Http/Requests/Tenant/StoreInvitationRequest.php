<?php

namespace App\Http\Requests\Tenant;

use App\Enums\SpaceRole;
use App\Models\Space;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreInvitationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        \Log::info('StoreInvitationRequest::authorize - Start');
        \Log::info('Tenant ID: ' . tenant('id'));
        \Log::info('User: ' . ($this->user() ? $this->user()->id : 'null'));
        
        $space = Space::find(tenant('id'));
        \Log::info('Space found: ' . ($space ? $space->id : 'null'));
        
        $canInvite = $space && $this->user() && $this->user()->can('invite', $space);
        \Log::info('Can invite: ' . ($canInvite ? 'true' : 'false'));
        
        return $canInvite;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'role' => ['required', new Enum(SpaceRole::class), Rule::notIn([SpaceRole::OWNER->value])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'email.required' => 'El correo electrónico es requerido.',
            'email.email' => 'El correo electrónico debe ser una dirección válida.',
            'role.required' => 'El rol es requerido.',
            'role.enum' => 'El rol seleccionado no es válido.',
        ];
    }
}