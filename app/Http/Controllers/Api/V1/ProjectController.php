<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ProjectController
 *
 * Controller tetap tipis — hanya orkestrasi:
 * 1. Authorize via Policy
 * 2. Delegate ke ProjectService
 * 3. Wrap response dengan ApiResponse trait
 *
 * Route Model Binding + CompanyScope: Project::find($id) otomatis
 * di-scope ke company user yang login. Kalau ID ada tapi beda company,
 * model tidak ditemukan → ModelNotFoundException → 404.
 */
class ProjectController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ProjectService $projectService) {}

    /**
     * GET /api/v1/projects
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Project::class);

        $projects = $this->projectService->index();

        return $this->successResponse(
            ProjectResource::collection($projects),
            'Projects retrieved successfully'
        );
    }

    /**
     * POST /api/v1/projects
     */
    public function store(StoreProjectRequest $request): JsonResponse
    {
        $this->authorize('create', Project::class);

        $project = $this->projectService->store($request->validated(), $request->user());

        return $this->successResponse(
            new ProjectResource($project),
            'Project created successfully',
            201
        );
    }

    /**
     * GET /api/v1/projects/{project}
     * Route Model Binding: $project sudah di-scope oleh CompanyScope.
     */
    public function show(Request $request, Project $project): JsonResponse
    {
        $this->authorize('view', $project);

        $project = $this->projectService->show($project);

        return $this->successResponse(
            new ProjectResource($project),
            'Project retrieved successfully'
        );
    }

    /**
     * PATCH /api/v1/projects/{project}
     */
    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $this->authorize('update', $project);

        $project = $this->projectService->update($project, $request->validated());

        return $this->successResponse(
            new ProjectResource($project),
            'Project updated successfully'
        );
    }

    /**
     * DELETE /api/v1/projects/{project}
     */
    public function destroy(Request $request, Project $project): JsonResponse
    {
        $this->authorize('delete', $project);

        $this->projectService->destroy($project);

        return $this->successResponse(null, 'Project deleted successfully');
    }
}
