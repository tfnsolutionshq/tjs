<?php

namespace App\Http\Middleware;

use App\Models\Journal;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanManageJournal
{
    /**
     * Routes that remain available while activation is unpaid/expired.
     *
     * @var list<string>
     */
    private const ACTIVATION_LOCKED_ALLOWED = [
        'journal.manage.dashboard',
        'journal.manage.activation.show',
        'journal.manage.activation.skip',
        'journal.manage.activation.pay',
        'journal.manage.billing.index',
        'journal.manage.billing.export',
        'journal.manage.payments.gateway',
        'journal.manage.payments.gateway.update',
        'journal.manage.doi.index',
        'journal.manage.doi.settings',
        'journal.manage.settings.edit',
        'journal.manage.settings.featured-request',
    ];

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

        $routeName = $request->route()?->getName();

        if ($journal->activationLocked()) {
            // Allow safe GET of dashboard / settings / activation, and the pay POST.
            $allowed = in_array($routeName, self::ACTIVATION_LOCKED_ALLOWED, true);
            if (! $allowed) {
                return redirect()
                    ->route('journal.manage.activation.show', $journal)
                    ->with('error', 'Pay or renew the journal activation fee to unlock management tools.');
            }

            if (! $request->isMethodSafe() && $routeName !== 'journal.manage.activation.pay' && $routeName !== 'journal.manage.activation.skip' && $routeName !== 'journal.manage.payments.gateway.update' && $routeName !== 'journal.manage.doi.settings' && $routeName !== 'journal.manage.settings.featured-request') {
                return redirect()
                    ->route('journal.manage.activation.show', $journal)
                    ->with('error', 'Pay or renew the journal activation fee to make changes.');
            }

            return $next($request);
        }

        // Platform admins may view locked journals, but cannot mutate unless allowed.
        if (! $request->isMethodSafe() && ! $journal->userMayMutate($user)) {
            abort(403, 'This journal has disabled edits by platform administrators. A journal admin can re-enable that in Settings.');
        }

        return $next($request);
    }
}
