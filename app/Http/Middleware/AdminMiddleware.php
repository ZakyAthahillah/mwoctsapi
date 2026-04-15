<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponseHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('api')->user();

        if (! $user || ! $user->is_admin) {
            return ApiResponseHelper::error('Forbidden', [
                'auth' => ['You do not have permission to perform this action.'],
            ], 403);
        }

        return $next($request);
    }
}
