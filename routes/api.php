<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — /api/v1/...
|--------------------------------------------------------------------------
|
| Prefix /api ditambahkan secara otomatis oleh bootstrap/app.php.
|
| Tenant isolation:
| - company_id SELALU dari auth()->user()->company_id (tidak pernah dari request)
| - CompanyScope (global scope) otomatis filter semua query Project & Task
| - Route Model Binding + CompanyScope: ID beda company → 404 otomatis
|
*/

Route::prefix('v1')->group(function () {

    // =========================================================================
    // Auth — public (tidak butuh auth:sanctum)
    // =========================================================================
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login',    [AuthController::class, 'login']);

        // Logout butuh token valid
        Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    });

    // =========================================================================
    // Protected routes — semua butuh auth:sanctum
    // =========================================================================
    Route::middleware('auth:sanctum')->group(function () {

        // ---------------------------------------------------------------------
        // User management (admin only — ditegakkan oleh UserPolicy)
        // GET  /api/v1/users       → list member dalam company
        // POST /api/v1/users       → create member baru
        // ---------------------------------------------------------------------
        Route::get('users',  [UserController::class, 'index']);
        Route::post('users', [UserController::class, 'store']);

        // ---------------------------------------------------------------------
        // Projects
        // GET    /api/v1/projects           → list (admin + member)
        // POST   /api/v1/projects           → create (admin only)
        // GET    /api/v1/projects/{project} → detail (admin + member)
        // PATCH  /api/v1/projects/{project} → update (admin only)
        // DELETE /api/v1/projects/{project} → delete (admin only)
        // ---------------------------------------------------------------------
        Route::apiResource('projects', ProjectController::class)->except(['put']);

        // ---------------------------------------------------------------------
        // Tasks (nested di bawah project — selalu butuh project context)
        // GET    /api/v1/projects/{project}/tasks              → list
        // POST   /api/v1/projects/{project}/tasks              → create (admin only)
        // GET    /api/v1/projects/{project}/tasks/{task}       → detail
        // PATCH  /api/v1/projects/{project}/tasks/{task}       → update
        // DELETE /api/v1/projects/{project}/tasks/{task}       → delete (admin only)
        // ---------------------------------------------------------------------
        Route::apiResource('projects.tasks', TaskController::class)
            ->except(['put']);
    });
});
