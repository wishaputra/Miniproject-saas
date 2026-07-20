<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * SendTaskAssignedNotification
 *
 * Di-dispatch saat task di-assign ke user (create atau update assigned_to).
 * Job ini keluar dari request-response cycle (implements ShouldQueue + queue=database).
 *
 * Saat ini: log ke Laravel log + tabel notifications (log driver).
 * Untuk production: ganti handle() dengan Mailable / push notification / dll.
 */
class SendTaskAssignedNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Task $task,
        public readonly User $assignee,
    ) {}

    /**
     * Handle the job.
     * Logging ke storage/logs/laravel.log sebagai bukti job keluar dari request cycle.
     */
    public function handle(): void
    {
        Log::info('[TaskAssigned] Task assigned to user', [
            'task_id'     => $this->task->id,
            'task_title'  => $this->task->title,
            'assignee_id' => $this->assignee->id,
            'assignee'    => $this->assignee->email,
            'project_id'  => $this->task->project_id,
            'company_id'  => $this->task->company_id,
            'handled_at'  => now()->toISOString(),
        ]);
    }
}
