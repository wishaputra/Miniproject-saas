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

    // Reassign state
    public $reassignTaskId = null;
    public $newAssigneeId = null;
    public $reassignNote = '';
    public $showReassignModal = false;

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

        $taskService->store($validated, $this->project, auth()->user());

        $this->reset(['title', 'description', 'status', 'assigned_to']);
        session()->flash('message', 'Task created successfully.');
    }

    public function updateTaskStatus(TaskService $taskService, $taskId, $newStatus)
    {
        $task = Task::findOrFail($taskId);
        
        // Cek policy update
        $this->authorize('update', $task);

        $taskService->update($task, ['status' => $newStatus]);
    }

    public function initiateReassign($taskId, $newAssigneeId)
    {
        $task = Task::findOrFail($taskId);
        $this->authorize('update', $task);

        if (!auth()->user()->isAdmin()) {
            abort(403);
        }

        $this->reassignTaskId = $taskId;
        $this->newAssigneeId = $newAssigneeId;
        $this->reassignNote = '';

        if (in_array($task->status, ['done', 'in_progress'])) {
            $this->showReassignModal = true;
        } else {
            $this->confirmReassign(app(TaskService::class));
        }
    }

    public function confirmReassign(TaskService $taskService)
    {
        $task = Task::findOrFail($this->reassignTaskId);
        $this->authorize('update', $task);

        if (in_array($task->status, ['done', 'in_progress'])) {
            $this->validate([
                'reassignNote' => 'required|string',
            ], [
                'reassignNote.required' => 'A note is required when reassigning a completed task.',
            ]);
        }

        $taskService->reassign($task, $this->newAssigneeId ?: null, auth()->user(), $this->reassignNote);

        $this->resetReassign();
        session()->flash('message', 'Task reassigned successfully.');
    }

    public function resetReassign()
    {
        $this->reset(['reassignTaskId', 'newAssigneeId', 'reassignNote', 'showReassignModal']);
    }

    // Delete Task State
    public $deleteTaskId = null;
    public $showDeleteModal = false;

    public function initiateDelete($taskId)
    {
        $task = Task::findOrFail($taskId);
        $this->authorize('delete', $task);

        $this->deleteTaskId = $taskId;
        $this->showDeleteModal = true;
    }

    public function executeDelete()
    {
        if (!$this->deleteTaskId) return;

        $task = Task::findOrFail($this->deleteTaskId);
        $this->authorize('delete', $task);

        $task->delete();

        $this->reset(['deleteTaskId', 'showDeleteModal']);
        session()->flash('message', 'Task deleted successfully.');
    }

    public function cancelDelete()
    {
        $this->reset(['deleteTaskId', 'showDeleteModal']);
    }

    public function render()
    {
        // Fetch tasks
        $tasks = $this->project->tasks()->with(['assignee', 'reassignmentLogs.admin'])->latest()->get();
        
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
