<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization ditangani oleh Policy di controller
    }

    public function rules(): array
    {
        return [
            // 'sometimes' — field tidak wajib ada, tapi kalau ada harus valid
            'name'        => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
