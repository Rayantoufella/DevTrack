<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddProjectMemberRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        // The project taken from the URL
        $project = $this->route('project');

        // Get the emails of users already in the project
        $existingEmails = [];
        if ($project) {
            $existingEmails = $project->users()->pluck('users.email')->all();
        }

        return [
            'email' => [
                'required',
                'email',
                'exists:users,email',
                Rule::notIn($existingEmails),
            ],
        ];
    }

    public function messages()
    {
        return [
            'email.exists' => 'Aucun utilisateur avec cet email.',
            'email.not_in' => 'Cet utilisateur fait déjà partie du projet.',
        ];
    }
}
