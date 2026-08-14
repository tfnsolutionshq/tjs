<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /** @param  string  ...$roles */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (! $user || (! in_array($user->role, $roles, true) && $user->role !== 'admin')) {
            abort(403, 'Unauthorized.');
        }

        return $next($request);
    }
}
