<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * AuthService
 *
 * Menaruh business logic auth di service class agar dapat di-share
 * oleh API Controller maupun Livewire Component tanpa duplikasi.
 */
class AuthService
{
    /**
     * Register company baru + user pertama sebagai admin.
     * Dibungkus DB::transaction untuk atomicity.
     *
     * @param  array{company_name: string, name: string, email: string, password: string}  $data
     * @return array{user: User, token: string}
     */
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            // 1. Buat company baru
            $company = Company::create([
                'name' => $data['company_name'],
            ]);

            // 2. Buat user pertama sebagai admin
            $user = User::create([
                'company_id' => $company->id,
                'name'       => $data['name'],
                'email'      => $data['email'],
                'password'   => Hash::make($data['password']),
                'role'       => 'admin',
            ]);

            // 3. Generate Sanctum token
            $token = $user->createToken('api-token')->plainTextToken;

            return compact('user', 'token', 'company');
        });
    }

    /**
     * Login user, return Sanctum token.
     *
     * @param  array{email: string, password: string}  $credentials
     * @return array{user: User, token: string}
     *
     * @throws ValidationException
     */
    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Hapus semua token lama (opsional: single-session behavior)
        $user->tokens()->delete();

        $token = $user->createToken('api-token')->plainTextToken;

        return compact('user', 'token');
    }

    /**
     * Logout: revoke current token.
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
