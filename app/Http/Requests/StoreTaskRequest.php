<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $project = $this->route('project');
        $projectId = $project ? $project->id : null;

        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'deadline'    => ['required', 'date', 'after:today'],
            'priority'    => ['required', Rule::in(['low', 'medium', 'high'])],
            // The assigned user must be a member of the project
            'user_id'     => [
                'required',
                Rule::exists('project_user', 'user_id')
                    ->where(function ($query) use ($projectId) {
                        $query->where('project_id', $projectId);
                    }),
            ],
        ];
    }

    public function messages()
    {
        return [
            'user_id.exists' => 'L\'utilisateur assigné doit être membre du projet.',
        ];
    }
}
