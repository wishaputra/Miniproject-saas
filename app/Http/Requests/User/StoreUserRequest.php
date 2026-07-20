<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization ditangani oleh UserPolicy di controller
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:191', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            // Tidak ada 'role' — role selalu di-set ke 'member' di UserService
            // Tidak ada 'company_id' — selalu dari auth()->user()->company_id
        ];
    }
}
