<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

/**
 * TaskPolicy
 *
 * RBAC untuk resource Task:
 * - admin: full CRUD semua task di company-nya
 * - member: viewAny + view (read); update HANYA jika assigned_to === dirinya;
 *           tidak bisa create atau delete task
 *
 * Catatan: company_id scoping ditangani oleh CompanyScope — policy cukup cek role & ownership.
 */
class TaskPolicy
{
    /**
     * admin & member boleh list task.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * admin & member boleh lihat detail task.
     */
    public function view(User $user, Task $task): bool
    {
        return true;
    }

    /**
     * Hanya admin yang boleh create task.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * admin: boleh update task apapun di company-nya.
     * member: HANYA boleh update task yang assigned_to === dirinya sendiri.
     *
     * Perbandingan menggunakan strict equality (===) antara integer ID.
     */
    public function update(User $user, Task $task): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // member: hanya task yang memang di-assign ke dirinya
        return $task->assigned_to === $user->id;
    }

    /**
     * Hanya admin yang boleh delete task.
     */
    public function delete(User $user, Task $task): bool
    {
        return $user->isAdmin();
    }
}
