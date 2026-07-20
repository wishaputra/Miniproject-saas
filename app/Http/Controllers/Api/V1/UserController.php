<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * UserController
 *
 * Endpoint admin untuk kelola member dalam company-nya sendiri.
 * Hanya admin yang bisa akses (ditegakkan oleh UserPolicy).
 */
class UserController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly UserService $userService) {}

    /**
     * GET /api/v1/users
     * List semua user dalam company yang sama dengan admin yang login.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $users = $this->userService->index($request->user());

        return $this->successResponse(
            UserResource::collection($users),
            'Users retrieved successfully'
        );
    }

    /**
     * POST /api/v1/users
     * Create member baru di company admin yang login.
     * Role selalu 'member', company_id dari token — tidak pernah dari request.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $user = $this->userService->store($request->validated(), $request->user());

        return $this->successResponse(
            new UserResource($user),
            'Member created successfully',
            201
        );
    }
}
