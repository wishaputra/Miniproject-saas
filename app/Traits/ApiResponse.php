<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * Trait ApiResponse
 *
 * Menyediakan method helper untuk response envelope yang konsisten:
 * { "success": bool, "data": ..., "message": ... }
 *
 * Di-use oleh semua API controller agar format response seragam
 * tanpa menulis array manual di tiap method controller.
 */
trait ApiResponse
{
    /**
     * Return a standardized success JSON response.
     *
     * @param  mixed  $data
     */
    protected function successResponse(mixed $data = null, string $message = 'OK', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => $data,
            'message' => $message,
        ], $code);
    }

    /**
     * Return a standardized error JSON response.
     *
     * @param  mixed  $errors
     */
    protected function errorResponse(string $message, mixed $errors = null, int $code = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $code);
    }
}
