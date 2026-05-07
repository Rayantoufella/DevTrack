<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $project = $this->route('project');

        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'deadline'    => ['required', 'date'],
            'priority'    => ['required', Rule::in(['low', 'medium', 'high'])],
            'status'      => ['required', Rule::in(['todo', 'in_progress', 'done'])],
            'user_id'     => [
                'required',
                Rule::exists('project_user', 'user_id')
                    ->where(fn ($q) => $q->where('project_id', $project?->id)),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.exists' => 'L\'utilisateur assigné doit être membre du projet.',
        ];
    }
}
