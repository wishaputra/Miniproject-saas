<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdateTaskRequest
 *
 * RBAC via validasi rules:
 * - member: HANYA boleh update field 'status' (field lain diabaikan di level validasi)
 * - admin: boleh update semua field (title, description, status, assigned_to)
 *
 * Pembatasan di dua lapis:
 * 1. Request ini → hanya field yang sesuai role yang masuk ke validated()
 * 2. TaskPolicy::update → member hanya bisa kalau assigned_to === dirinya
 */
class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization ditangani oleh Policy di controller
    }

    public function rules(): array
    {
        $user = $this->user();

        // Member: hanya boleh update status — field lain tidak di-whitelist,
        // sehingga tidak akan masuk ke validated() meski dikirim oleh client
        if ($user?->isMember()) {
            return [
                'status' => ['required', 'string', 'in:todo,in_progress,done'],
            ];
        }

        // Admin: semua field boleh diubah (PATCH — 'sometimes' bukan 'required')
        return [
            'title'       => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status'      => ['sometimes', 'string', 'in:todo,in_progress,done'],
            // assigned_to harus user dari company yang sama
            'assigned_to' => [
                'sometimes',
                'nullable',
                Rule::exists('users', 'id')->where('company_id', $user->company_id),
            ],
        ];
    }
}
