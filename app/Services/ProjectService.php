<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * ProjectService
 *
 * Semua business logic untuk Project ada di sini.
 * Controller dan Livewire component memanggil service ini — tidak ada logic di controller.
 * company_id selalu dari auth()->user()->company_id (lewat BelongsToCompany trait + service).
 */
class ProjectService
{
    /**
     * List semua project milik company user yang login.
     * CompanyScope sudah otomatis filter — tidak perlu where() manual.
     * Eager load 'creator' untuk hindari N+1.
     */
    public function index(): Collection
    {
        return Project::with(['creator'])->get();
    }

    /**
     * Buat project baru.
     * company_id di-set oleh BelongsToCompany trait (model event 'creating').
     * created_by di-set di sini dari user yang sedang login.
     */
    public function store(array $data, User $user): Project
    {
        $project = Project::create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'created_by'  => $user->id,
            // company_id di-isi otomatis oleh BelongsToCompany::bootBelongsToCompany()
        ]);

        return $project->load('creator');
    }

    /**
     * Detail project.
     * CompanyScope sudah memastikan hanya project milik company sendiri yang bisa diakses.
     * Eager load creator + tasks beserta assignee & creator-nya.
     */
    public function show(Project $project): Project
    {
        return $project->load(['creator', 'tasks.assignee', 'tasks.creator']);
    }

    /**
     * Update project.
     * Hanya field yang ada di $data yang diupdate (PATCH semantics via 'sometimes' di request).
     */
    public function update(Project $project, array $data): Project
    {
        $project->update($data);

        return $project->load('creator');
    }

    /**
     * Hapus project.
     * Tasks ter-cascade delete lewat FK `tasks.project_id` ON DELETE CASCADE.
     */
    public function destroy(Project $project): void
    {
        $project->delete();
    }
}
