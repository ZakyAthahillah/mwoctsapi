<?php

use App\Http\Middleware\AdminMiddleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => AdminMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Bad request',
                    'data' => null,
                    'meta' => null,
                    'errors' => $exception->errors(),
                ], 400);
            }
        });

        $exceptions->render(function (UnauthorizedHttpException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'data' => null,
                    'meta' => null,
                    'errors' => [
                        'auth' => [$exception->getMessage()],
                    ],
                ], 401);
            }
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'data' => null,
                    'meta' => null,
                    'errors' => [
                        'auth' => ['Authentication is required.'],
                    ],
                ], 401);
            }
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found',
                    'data' => null,
                    'meta' => null,
                    'errors' => null,
                ], 404);
            }
        });
    })->create();
