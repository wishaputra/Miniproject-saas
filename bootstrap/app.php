<?php

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // =====================================================================
        // Semua API error wajib return envelope: { success, message, errors }
        // Urutan handler penting: spesifik dulu, generic di bawah.
        // =====================================================================

        // 422 — Validasi gagal (FormRequest & manual ValidationException)
        $exceptions->render(function (ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors'  => $e->errors(),
                ], 422);
            }
        });

        // 403 — Authorization exception dari $this->authorize() / Policy
        // AuthorizationException di-throw oleh Laravel Gate/Policy secara internal
        $exceptions->render(function (AuthorizationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden',
                    'errors'  => null,
                ], 403);
            }
        });

        // 401 — Tidak terautentikasi (token tidak ada / kadaluarsa)
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                    'errors'  => null,
                ], 401);
            }
        });

        // 404 — Model tidak ditemukan (findOrFail, Route Model Binding)
        // Tenant isolation: resource ada tapi beda company → global scope menyembunyikannya
        // sehingga findOrFail throw ModelNotFoundException → kita return 404, bukan 200 kosong
        $exceptions->render(function (ModelNotFoundException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not found',
                    'errors'  => null,
                ], 404);
            }
        });

        // 404 — Route tidak ditemukan (NotFoundHttpException)
        $exceptions->render(function (NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Not found',
                    'errors'  => null,
                ], 404);
            }
        });

        // Generic catch-all — HttpException lain (405 Method Not Allowed, dll.)
        $exceptions->render(function (HttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'HTTP Error',
                    'errors'  => null,
                ], $e->getStatusCode());
            }
        });
    })->create();

