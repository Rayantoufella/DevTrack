<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'deadline'    => ['required', 'date', 'after:today'],
        ];
    }

    public function messages()
    {
        return [
            'deadline.after' => 'La deadline doit être postérieure à aujourd\'hui.',
        ];
    }
}
