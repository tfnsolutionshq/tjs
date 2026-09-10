<?php

namespace App\Http\Middleware;

use App\Models\Journal;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanManageJournalApi
{
    /**
     * Safe GET routes allowed while activation is unpaid/expired.
     *
     * @var list<string>
     */
    private const ACTIVATION_LOCKED_READ_SUFFIXES = [
        'manage/dashboard',
        'manage/settings',
        'manage/billing',
    ];

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $journal = $request->route('journal');

        if (! $user || ! $journal instanceof Journal) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (! $user->canManageJournal($journal)) {
            return response()->json(['message' => 'You do not manage this journal.'], 403);
        }

        if ($journal->activationLocked()) {
            if ($this->allowedWhenActivationLocked($request)) {
                return $next($request);
            }

            return response()->json([
                'message' => 'Pay or renew the journal activation fee to unlock management tools.',
                'error' => 'activation_locked',
                'activation' => [
                    'status' => $journal->activation_status,
                    'expires_at' => $journal->activation_expires_at?->toIso8601String(),
                ],
            ], 403);
        }

        if (! $request->isMethodSafe() && ! $journal->userMayMutate($user)) {
            return response()->json([
                'message' => 'This journal has disabled edits by platform administrators. A journal admin can re-enable that in Settings.',
            ], 403);
        }

        return $next($request);
    }

    private function allowedWhenActivationLocked(Request $request): bool
    {
        if ($request->isMethodSafe() && $this->matchesAllowedReadSuffix($request)) {
            return true;
        }

        return false;
    }

    private function matchesAllowedReadSuffix(Request $request): bool
    {
        $path = trim($request->path(), '/');

        foreach (self::ACTIVATION_LOCKED_READ_SUFFIXES as $suffix) {
            if (str_ends_with($path, $suffix)) {
                return true;
            }
        }

        return false;
    }
}
