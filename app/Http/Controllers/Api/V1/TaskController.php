<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Services\TaskService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * TaskController
 *
 * Resource nested di bawah Project: /api/v1/projects/{project}/tasks/{task}
 *
 * Double isolation di Route Model Binding:
 * - $project di-scope oleh CompanyScope → beda company = 404
 * - $task di-scope oleh CompanyScope → beda company = 404
 * - Validasi $task->project_id === $project->id dilakukan eksplisit
 *   untuk mencegah task hopping antar project dalam company yang sama
 */
class TaskController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly TaskService $taskService) {}

    /**
     * GET /api/v1/projects/{project}/tasks
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorize('viewAny', Task::class);

        $tasks = $this->taskService->index($project);

        return $this->successResponse(
            TaskResource::collection($tasks),
            'Tasks retrieved successfully'
        );
    }

    /**
     * POST /api/v1/projects/{project}/tasks
     */
    public function store(StoreTaskRequest $request, Project $project): JsonResponse
    {
        $this->authorize('create', Task::class);

        $task = $this->taskService->store($request->validated(), $project, $request->user());

        return $this->successResponse(
            new TaskResource($task),
            'Task created successfully',
            201
        );
    }

    /**
     * GET /api/v1/projects/{project}/tasks/{task}
     */
    public function show(Request $request, Project $project, Task $task): JsonResponse
    {
        $this->authorize('view', $task);
        $this->ensureTaskBelongsToProject($task, $project);

        $task = $this->taskService->show($task);

        return $this->successResponse(
            new TaskResource($task),
            'Task retrieved successfully'
        );
    }

    /**
     * PATCH /api/v1/projects/{project}/tasks/{task}
     *
     * UpdateTaskRequest sudah filter field berdasarkan role:
     * - member: hanya 'status' yang masuk validated()
     * - admin: semua field
     */
    public function update(UpdateTaskRequest $request, Project $project, Task $task): JsonResponse
    {
        $this->authorize('update', $task);
        $this->ensureTaskBelongsToProject($task, $project);

        $task = $this->taskService->update($task, $request->validated());

        return $this->successResponse(
            new TaskResource($task),
            'Task updated successfully'
        );
    }

    /**
     * DELETE /api/v1/projects/{project}/tasks/{task}
     */
    public function destroy(Request $request, Project $project, Task $task): JsonResponse
    {
        $this->authorize('delete', $task);
        $this->ensureTaskBelongsToProject($task, $project);

        $this->taskService->destroy($task);

        return $this->successResponse(null, 'Task deleted successfully');
    }

    /**
     * Validasi bahwa task memang milik project yang diakses.
     * Mencegah task hopping: /projects/1/tasks/99 di mana task 99 sebenarnya milik project 2.
     * CompanyScope sudah handle cross-tenant, tapi cross-project dalam company perlu cek ini.
     */
    private function ensureTaskBelongsToProject(Task $task, Project $project): void
    {
        if ($task->project_id !== $project->id) {
            abort(404);
        }
    }
}
