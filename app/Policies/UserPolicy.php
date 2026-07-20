<?php

namespace App\Policies;

use App\Models\User;

/**
 * UserPolicy
 *
 * RBAC untuk endpoint kelola user (/api/v1/users):
 * - admin: boleh list + create member di company-nya
 * - member: tidak ada akses ke endpoint ini
 *
 * User yang dibuat lewat endpoint ini selalu role=member,
 * dan company_id diambil dari token admin — tidak pernah dari request.
 */
class UserPolicy
{
    /**
     * Hanya admin yang boleh list user dalam company-nya.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Hanya admin yang boleh create member baru.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }
}
