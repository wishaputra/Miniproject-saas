<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization ditangani oleh Policy di controller
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status'      => ['nullable', 'string', 'in:todo,in_progress,done'],
            // assigned_to harus user yang ada di company yang sama — cek lewat Rule::exists
            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')->where('company_id', $this->user()->company_id),
            ],
        ];
    }
}
