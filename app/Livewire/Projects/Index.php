<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class Index extends Component
{
    use AuthorizesRequests;

    public $name = '';
    public $description = '';

    public function createProject(ProjectService $projectService)
    {
        $this->authorize('create', Project::class);

        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $projectService->create($validated);

        $this->reset(['name', 'description']);
        session()->flash('message', 'Project created successfully.');
    }

    public function render(ProjectService $projectService)
    {
        // ProjectService->index() uses global scope, so it's already tenant-isolated
        $projects = $projectService->index();

        return view('livewire.projects.index', [
            'projects' => $projects,
        ]);
    }
}
