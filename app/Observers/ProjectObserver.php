<?php

namespace App\Observers;

use App\Models\Project;
use App\Models\ActivityLog;

class ProjectObserver
{
    /**
     * Handle the Project "created" event.
     */
    public function created(Project $project): void
    {
        $this->logActivity('created', $project, null, $project->toArray(), 'Project "' . $project->name . '" was created.');
    }

    /**
     * Handle the Project "updated" event.
     */
    public function updated(Project $project): void
    {
        $oldValues = [];
        $newValues = [];

        foreach ($project->getDirty() as $key => $value) {
            $oldValues[$key] = $project->getOriginal($key);
            $newValues[$key] = $value;
        }

        $this->logActivity('updated', $project, $oldValues, $newValues, 'Project "' . $project->name . '" was updated.');
    }

    /**
     * Handle the Project "deleted" event.
     */
    public function deleted(Project $project): void
    {
        $this->logActivity('deleted', $project, $project->toArray(), null, 'Project "' . $project->name . '" was deleted.');
    }

    /**
     * Create the activity log.
     */
    protected function logActivity(string $action, Project $project, ?array $oldValues, ?array $newValues, string $description): void
    {
        if (!auth()->check()) {
            return;
        }

        ActivityLog::create([
            'company_id' => $project->company_id,
            'user_id' => auth()->id(),
            'action' => $action,
            'loggable_type' => Project::class,
            'loggable_id' => $project->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description,
        ]);
    }
}
