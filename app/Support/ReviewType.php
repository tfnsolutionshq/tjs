<?php

namespace App\Support;

use App\Models\Journal;
use App\Models\Submission;
use Illuminate\Validation\Rule;

final class ReviewType
{
    public const CLOSED = 'closed';

    public const OPEN = 'open';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [self::CLOSED, self::OPEN];
    }

    public static function rule(): string
    {
        return 'in:'.implode(',', self::all());
    }

    public static function validationRule(): array
    {
        return ['nullable', Rule::in(self::all())];
    }

    public static function requiredRule(): array
    {
        return ['required', Rule::in(self::all())];
    }

    public static function label(?string $type): string
    {
        return match ($type) {
            self::OPEN => 'Open review',
            self::CLOSED => 'Closed review',
            default => 'Closed review',
        };
    }

    public static function description(?string $type): string
    {
        return match ($type) {
            self::OPEN => 'Reviewers see the author identity while evaluating the manuscript.',
            self::CLOSED => 'Reviewers do not see the author identity (blind review).',
            default => 'Reviewers do not see the author identity (blind review).',
        };
    }

    public static function authorTip(?string $type): string
    {
        return match ($type) {
            self::OPEN => 'This journal uses open review. Reviewers will see your name during evaluation.',
            self::CLOSED => 'This journal uses closed review. Remove author names from the manuscript file before upload.',
            default => 'Remove author names from the manuscript file if the journal requires anonymous review.',
        };
    }

    public static function forJournal(Journal $journal): string
    {
        $type = $journal->review_type ?? self::CLOSED;

        return in_array($type, self::all(), true) ? $type : self::CLOSED;
    }

    public static function forSubmission(Submission $submission): string
    {
        $type = $submission->review_type;

        if ($type !== null && in_array($type, self::all(), true)) {
            return $type;
        }

        $submission->loadMissing('journal');

        return $submission->journal
            ? self::forJournal($submission->journal)
            : self::CLOSED;
    }

    public static function isOpen(?string $type): bool
    {
        return $type === self::OPEN;
    }
}
