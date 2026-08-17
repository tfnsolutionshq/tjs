<?php

namespace App\Providers;

use App\Models\Submission;
use App\Support\PlatformSettings;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        try {
            PlatformSettings::applyToConfig();
        } catch (Throwable) {
            // Settings table may not exist yet during early migrate.
        }

        View::composer('layouts.admin', function ($view) {
            $view->with(
                'adminPendingSubmissions',
                Submission::query()
                    ->whereIn('status', ['submitted', 'under_review', 'resubmitted', 'revision_requested'])
                    ->count()
            );
        });

        View::composer('layouts.journal-manage', function ($view) {
            $journal = request()->route('journal');
            $pending = 0;
            $pendingReviewerRequests = 0;
            if ($journal) {
                $pending = Submission::query()
                    ->where('journal_id', $journal->id)
                    ->whereIn('status', ['submitted', 'under_review', 'resubmitted', 'revision_requested'])
                    ->count();
                $pendingReviewerRequests = \App\Models\JournalReviewerRequest::query()
                    ->where('journal_id', $journal->id)
                    ->pending()
                    ->count();
            }
            $view->with('journalPendingSubmissions', $pending);
            $view->with('journalPendingReviewerRequests', $pendingReviewerRequests);
            if (! $view->offsetExists('journal') && $journal) {
                $view->with('journal', $journal);
            }
        });

        View::composer('layouts.member', function ($view) {
            $user = auth()->user();
            $openSubs = 0;
            $openReviews = 0;

            if ($user) {
                $openSubs = Submission::query()
                    ->where('author_id', $user->id)
                    ->whereIn('status', ['submitted', 'under_review', 'resubmitted', 'revision_requested'])
                    ->count();

                if ($user->canAccessReviewQueue()) {
                    $openReviews = \App\Models\ReviewerAssignment::query()
                        ->where('reviewer_id', $user->id)
                        ->whereIn('status', ['assigned', 'in_progress', 'pending', 'accepted'])
                        ->count();
                }
            }

            $view->with([
                'memberOpenSubmissions' => $openSubs,
                'memberOpenReviews' => $openReviews,
            ]);
        });
    }
}
