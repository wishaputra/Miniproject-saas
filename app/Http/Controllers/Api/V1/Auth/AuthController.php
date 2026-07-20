<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AuthController
 *
 * Controller tetap tipis: hanya delegasi ke AuthService.
 * Business logic ada di AuthService, bukan di sini.
 */
class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AuthService $authService) {}

    /**
     * POST /api/v1/auth/register
     * Daftarkan company baru + user admin pertama.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->successResponse([
            'user'  => [
                'id'           => $result['user']->id,
                'name'         => $result['user']->name,
                'email'        => $result['user']->email,
                'role'         => $result['user']->role,
                'company_id'   => $result['user']->company_id,
            ],
            'company' => [
                'id'   => $result['company']->id,
                'name' => $result['company']->name,
            ],
            'token' => $result['token'],
        ], 'Registration successful', 201);
    }

    /**
     * POST /api/v1/auth/login
     * Login dan return Sanctum token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return $this->successResponse([
            'user'  => [
                'id'         => $result['user']->id,
                'name'       => $result['user']->name,
                'email'      => $result['user']->email,
                'role'       => $result['user']->role,
                'company_id' => $result['user']->company_id,
            ],
            'token' => $result['token'],
        ], 'Login successful');
    }

    /**
     * POST /api/v1/auth/logout
     * Revoke current Sanctum token.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->successResponse(null, 'Logged out successfully');
    }
}
