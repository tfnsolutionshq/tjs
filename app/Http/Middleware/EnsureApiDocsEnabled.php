<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiDocsEnabled
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('api.enabled', true)) {
            abort(503, 'API documentation is unavailable while the API is disabled.');
        }

        if (! config('api.docs_enabled', true)) {
            abort(404);
        }

        return $next($request);
    }
}
