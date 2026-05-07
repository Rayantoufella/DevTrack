<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        // L'autorisation est gérée par ProjectPolicy via $this->authorize() dans le controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'deadline'    => ['required', 'date', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'deadline.after' => 'La deadline doit être postérieure à aujourd\'hui.',
        ];
    }
}
