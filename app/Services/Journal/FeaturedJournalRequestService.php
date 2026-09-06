<?php

namespace App\Services\Journal;

use App\Models\Journal;
use App\Models\User;
use App\Notifications\JournalFeaturedRequestNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Notification;
use RuntimeException;

class FeaturedJournalRequestService
{
    public function request(Journal $journal, User $requester): void
    {
        if ($journal->is_featured) {
            throw new RuntimeException('This journal is already featured on the homepage.');
        }

        if (
            $journal->featured_requested_at
            && $journal->featured_requested_at->greaterThan(now()->subDays(7))
        ) {
            throw new RuntimeException('A featured request was already sent recently. The platform team will respond soon.');
        }

        $journal->forceFill(['featured_requested_at' => now()])->save();

        $admins = User::query()->where('role', 'admin')->get();
        if ($admins->isEmpty()) {
            return;
        }

        Notification::send($admins, new JournalFeaturedRequestNotification($journal, $requester));
    }

    public function approve(Journal $journal): void
    {
        if ($journal->is_featured) {
            throw new RuntimeException('This journal is already featured.');
        }

        $journal->forceFill([
            'is_featured' => true,
            'featured_requested_at' => null,
        ])->save();
    }

    public function dismiss(Journal $journal): void
    {
        $journal->forceFill(['featured_requested_at' => null])->save();
    }

    /**
     * @param  Builder<Journal>  $query
     * @return Builder<Journal>
     */
    public function scopePendingRequests(Builder $query): Builder
    {
        return $query
            ->where('is_featured', false)
            ->whereNotNull('featured_requested_at');
    }
}
