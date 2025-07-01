<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $project = $this->route('project');

        // Only the project owner can update it
        return $this->user()->id === $project->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'client_id' => 'nullable|exists:clients,id',
            'due_date' => 'nullable|date',
            'status' => 'nullable|string|in:active,completed,paused',
            'settings' => 'nullable|array',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $validator->getData();
            $project = $this->route('project');

            // If status is changed to completed, set completed_at
            if (isset($data['status']) && $data['status'] === 'completed' && $project->status !== 'completed') {
                $this->merge(['completed_at' => now()]);
            } elseif (isset($data['status']) && $data['status'] !== 'completed') {
                $this->merge(['completed_at' => null]);
            }
        });
    }
}
