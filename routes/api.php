<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — /api/v1/...
|--------------------------------------------------------------------------
|
| Prefix /api ditambahkan secara otomatis oleh bootstrap/app.php.
| Semua route protected di sini menggunakan middleware auth:sanctum.
| company_id SELALU diambil dari token user, tidak pernah dari request.
|
*/

Route::prefix('v1')->group(function () {

    // =========================================================================
    // Auth — tidak butuh auth:sanctum
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
        // Projects & Tasks akan ditambahkan di Bagian 3
    });
});
