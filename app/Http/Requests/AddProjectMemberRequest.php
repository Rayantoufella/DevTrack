<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddProjectMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $project = $this->route('project');

        return [
            'email' => [
                'required',
                'email',
                'exists:users,email',
                Rule::notIn(
                    $project ? $project->users()->pluck('users.email')->all() : []
                ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.exists' => 'Aucun utilisateur avec cet email.',
            'email.not_in' => 'Cet utilisateur fait déjà partie du projet.',
        ];
    }
}
