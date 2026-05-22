<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?? $request->query('api_token');

        if (! $token || $token !== config('app.api_token')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        return $next($request);
    }
}
