<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * UserService
 *
 * Logic kelola user (admin endpoint).
 * User tidak punya BelongsToCompany global scope,
 * sehingga filter company_id dilakukan eksplisit di service ini.
 */
class UserService
{
    /**
     * List semua user dalam company yang sama dengan admin yang login.
     * Filter company_id eksplisit — User model tidak punya CompanyScope.
     */
    public function index(User $authUser): Collection
    {
        return User::where('company_id', $authUser->company_id)
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Buat member baru di company yang sama dengan admin yang login.
     *
     * Keamanan yang di-enforce di service level (bukan request level):
     * - role selalu 'member' — admin tidak bisa buat admin lain lewat endpoint ini
     * - company_id dari $authUser->company_id — tidak pernah dari request input
     */
    public function store(array $data, User $authUser): User
    {
        return User::create([
            'company_id' => $authUser->company_id,
            'name'       => $data['name'],
            'email'      => $data['email'],
            'password'   => Hash::make($data['password']),
            'role'       => 'member', // hardcode — tidak pernah dari input
        ]);
    }
}
