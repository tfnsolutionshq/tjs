<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanAccessReviewQueue
{
    /**
     * Allow global reviewers/editors/admins and journal-team reviewers
     * (ordinary platform members who review for a specific journal).
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->canAccessReviewQueue()) {
            abort(403, 'You do not have access to the review queue.');
        }

        return $next($request);
    }
}
