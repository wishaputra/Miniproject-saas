<?php

namespace App\Services;

use App\Jobs\SendTaskAssignedNotification;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * TaskService
 *
 * Business logic untuk Task. Menjaga controller tetap tipis.
 * Dispatch SendTaskAssignedNotification ke queue (database driver) saat
 * assigned_to di-set — job keluar dari request cycle sesuai requirement.
 */
class TaskService
{
    /**
     * List semua task dalam project.
     * CompanyScope + project_id constraint memastikan isolasi ganda.
     * Eager load assignee + creator untuk hindari N+1.
     */
    public function index(Project $project): Collection
    {
        return $project->tasks()
            ->with(['assignee', 'creator'])
            ->get();
    }

    /**
     * Buat task baru.
     * company_id di-isi otomatis oleh BelongsToCompany trait.
     * Dispatch job notifikasi jika ada assigned_to.
     */
    public function store(array $data, Project $project, User $user): Task
    {
        $task = Task::create([
            'project_id'  => $project->id,
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'status'      => $data['status'] ?? 'todo',
            'assigned_to' => $data['assigned_to'] ?? null,
            'created_by'  => $user->id,
            // company_id di-isi otomatis oleh BelongsToCompany::bootBelongsToCompany()
        ]);

        // Dispatch notifikasi jika task langsung di-assign saat create
        $this->dispatchNotificationIfAssigned($task, null);

        return $task->load(['assignee', 'creator', 'project']);
    }

    /**
     * Detail satu task.
     * Eager load semua relasi yang dibutuhkan response.
     */
    public function show(Task $task): Task
    {
        return $task->load(['assignee', 'creator', 'project']);
    }

    /**
     * Update task.
     * Data yang masuk sudah di-filter oleh UpdateTaskRequest sesuai role:
     * - member: hanya 'status'
     * - admin: semua field
     *
     * Dispatch job jika assigned_to berubah.
     */
    public function update(Task $task, array $data): Task
    {
        $previousAssignee = $task->assigned_to;

        $task->update($data);
        $task->refresh();

        // Dispatch notifikasi jika assigned_to berubah (atau baru di-set)
        $this->dispatchNotificationIfAssigned($task, $previousAssignee);

        return $task->load(['assignee', 'creator', 'project']);
    }

    /**
     * Hapus task.
     */
    public function destroy(Task $task): void
    {
        $task->delete();
    }

    /**
     * Dispatch SendTaskAssignedNotification ke queue jika:
     * - assigned_to tidak null (ada assignee), DAN
     * - assigned_to berubah dari nilai sebelumnya
     *
     * Job berjalan di luar request-response cycle (ShouldQueue + queue driver=database).
     */
    private function dispatchNotificationIfAssigned(Task $task, ?int $previousAssigneeId): void
    {
        $hasAssignee    = $task->assigned_to !== null;
        $assigneeChanged = $task->assigned_to !== $previousAssigneeId;

        if ($hasAssignee && $assigneeChanged) {
            $assignee = User::find($task->assigned_to);

            if ($assignee) {
                // dispatch() bukan dispatchSync() — keluar dari request cycle
                SendTaskAssignedNotification::dispatch($task, $assignee);
            }
        }
    }
}
