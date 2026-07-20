<?php

namespace App\Services;

use App\Jobs\SendTaskAssignedNotification;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

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
        return DB::transaction(function () use ($task, $data) {
            // Lock baris task secara eksklusif untuk mencegah race condition (mis. 2 admin update status bersamaan)
            $lockedTask = Task::where('id', $task->id)->lockForUpdate()->first();

            $previousAssignee = $lockedTask->assigned_to;

            $lockedTask->update($data);
            $lockedTask->refresh();

            // Dispatch notifikasi jika assigned_to berubah (atau baru di-set)
            $this->dispatchNotificationIfAssigned($lockedTask, $previousAssignee);

            return $lockedTask->load(['assignee', 'creator', 'project']);
        });
    }

    /**
     * Re-assign task khusus Admin (termasuk validasi note jika status done).
     */
    public function reassign(Task $task, ?int $newAssigneeId, User $admin, ?string $note = null): Task
    {
        return DB::transaction(function () use ($task, $newAssigneeId, $admin, $note) {
            $lockedTask = Task::where('id', $task->id)->lockForUpdate()->first();

            if (in_array($lockedTask->status, ['done', 'in_progress']) && empty(trim($note))) {
                throw new \InvalidArgumentException('A note is required when reassigning a task in progress or completed.');
            }

            $previousAssignee = $lockedTask->assigned_to;
            
            if ($previousAssignee !== $newAssigneeId) {
                \App\Models\TaskReassignmentLog::create([
                    'task_id' => $lockedTask->id,
                    'admin_id' => $admin->id,
                    'from_user_id' => $previousAssignee,
                    'to_user_id' => $newAssigneeId,
                    'note' => $note,
                ]);

                $lockedTask->update(['assigned_to' => $newAssigneeId]);
                $lockedTask->refresh();

                $this->dispatchNotificationIfAssigned($lockedTask, $previousAssignee);
            }

            return $lockedTask->load(['assignee', 'creator', 'project']);
        });
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
