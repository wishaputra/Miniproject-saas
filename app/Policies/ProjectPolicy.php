<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

/**
 * ProjectPolicy
 *
 * RBAC untuk resource Project:
 * - admin: full CRUD semua project di company-nya
 * - member: hanya viewAny + view (read-only)
 *
 * Catatan: company_id scoping sudah ditangani oleh CompanyScope (global scope),
 * sehingga Policy tidak perlu re-check company_id — cukup cek role.
 */
class ProjectPolicy
{
    /**
     * admin & member boleh list project.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * admin & member boleh lihat detail project.
     */
    public function view(User $user, Project $project): bool
    {
        return true;
    }

    /**
     * Hanya admin yang boleh create project.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Hanya admin yang boleh update project.
     */
    public function update(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }

    /**
     * Hanya admin yang boleh delete project.
     */
    public function delete(User $user, Project $project): bool
    {
        return $user->isAdmin();
    }
}
