<?php

namespace App\Observers;

use App\Models\Task;
use App\Models\ActivityLog;

class TaskObserver
{
    /**
     * Handle the Task "created" event.
     */
    public function created(Task $task): void
    {
        $this->logActivity('created', $task, null, $task->toArray(), 'Task "' . $task->title . '" was created.');
    }

    /**
     * Handle the Task "updated" event.
     */
    public function updated(Task $task): void
    {
        $oldValues = [];
        $newValues = [];

        foreach ($task->getDirty() as $key => $value) {
            $oldValues[$key] = $task->getOriginal($key);
            $newValues[$key] = $value;
        }

        $this->logActivity('updated', $task, $oldValues, $newValues, 'Task "' . $task->title . '" was updated.');
    }

    /**
     * Handle the Task "deleted" event.
     */
    public function deleted(Task $task): void
    {
        $this->logActivity('deleted', $task, $task->toArray(), null, 'Task "' . $task->title . '" was deleted.');
    }

    /**
     * Create the activity log.
     */
    protected function logActivity(string $action, Task $task, ?array $oldValues, ?array $newValues, string $description): void
    {
        // Don't log if running in console/seeder without auth unless you want to
        if (!auth()->check()) {
            return;
        }

        ActivityLog::create([
            'company_id' => $task->company_id,
            'user_id' => auth()->id(),
            'action' => $action,
            'loggable_type' => Task::class,
            'loggable_id' => $task->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description,
        ]);
    }
}
