<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Models\Task;
use App\Services\TaskService;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Show extends Component
{
    use AuthorizesRequests;

    public Project $project;
    
    // New task form fields
    public $title = '';
    public $description = '';
    public $status = 'todo';
    public $assigned_to = null;

    public function mount(Project $project)
    {
        // View policy handled by route middleware or here
        $this->authorize('view', $project);
        $this->project = $project;
    }

    public function createTask(TaskService $taskService)
    {
        $this->authorize('create', [Task::class, $this->project]);

        $validated = $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:todo,in_progress,done',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $taskService->create($this->project, $validated);

        $this->reset(['title', 'description', 'status', 'assigned_to']);
        session()->flash('message', 'Task created successfully.');
    }

    public function updateTaskStatus(TaskService $taskService, $taskId, $newStatus)
    {
        $task = Task::findOrFail($taskId);
        
        // Cek policy update
        $this->authorize('update', $task);

        $taskService->update($this->project, $task, ['status' => $newStatus]);
    }

    public function render()
    {
        // Fetch tasks
        $tasks = $this->project->tasks()->with('assignee')->latest()->get();
        
        // Fetch members for assignment dropdown (admin only)
        $members = [];
        if (auth()->user()->isAdmin()) {
            $members = User::where('company_id', auth()->user()->company_id)
                ->where('role', 'member')
                ->get();
        }

        return view('livewire.projects.show', [
            'tasks' => $tasks,
            'members' => $members,
        ]);
    }
}
