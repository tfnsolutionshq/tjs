<?php

namespace App\Http\Middleware;

use App\Models\Journal;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanManageJournal
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $journal = $request->route('journal');

        if (! $user || ! $journal instanceof Journal) {
            abort(403);
        }

        if (! $user->canManageJournal($journal)) {
            abort(403, 'You do not manage this journal.');
        }

        // Platform admins may view locked journals, but cannot mutate unless allowed.
        if (! $request->isMethodSafe() && ! $journal->userMayMutate($user)) {
            abort(403, 'This journal has disabled edits by platform administrators. A journal admin can re-enable that in Settings.');
        }

        return $next($request);
    }
}
