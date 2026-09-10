<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('api.enabled', true)) {
            return response()->json([
                'message' => 'The API is currently disabled.',
            ], 503);
        }

        return $next($request);
    }
}
