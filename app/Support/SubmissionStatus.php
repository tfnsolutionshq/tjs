<?php

namespace App\Support;

final class SubmissionStatus
{
    public const FEE_PENDING = 'fee_pending';

    public const SUBMITTED = 'submitted';

    public const UNDER_REVIEW = 'under_review';

    public const REVISION_REQUESTED = 'revision_requested';

    public const RESUBMITTED = 'resubmitted';

    public const PUBLICATION_FEE_PENDING = 'publication_fee_pending';

    /** @deprecated Use READY_FOR_PRODUCTION — kept for queries during transition */
    public const APPROVED = 'approved';

    public const READY_FOR_PRODUCTION = 'ready_for_production';

    public const IN_PRODUCTION = 'in_production';

    public const READY_TO_PUBLISH = 'ready_to_publish';

    public const PUBLISHED = 'published';

    public const REJECTED = 'rejected';

    /**
     * Statuses where production editors may work on the manuscript.
     *
     * @return list<string>
     */
    public static function productionQueueStatuses(): array
    {
        return [
            self::READY_FOR_PRODUCTION,
            self::IN_PRODUCTION,
            self::READY_TO_PUBLISH,
        ];
    }

    /**
     * Statuses counted as editorially accepted for authors.
     *
     * @return list<string>
     */
    public static function acceptedStatuses(): array
    {
        return [
            self::PUBLICATION_FEE_PENDING,
            self::READY_FOR_PRODUCTION,
            self::IN_PRODUCTION,
            self::READY_TO_PUBLISH,
            self::APPROVED,
            self::PUBLISHED,
        ];
    }

    public static function label(string $status): string
    {
        return match ($status) {
            self::FEE_PENDING => 'Submission fee pending',
            self::SUBMITTED => 'Submitted',
            self::UNDER_REVIEW => 'Under review',
            self::REVISION_REQUESTED => 'Revision requested',
            self::RESUBMITTED => 'Resubmitted',
            self::PUBLICATION_FEE_PENDING => 'Publication fee pending',
            self::APPROVED => 'Approved',
            self::READY_FOR_PRODUCTION => 'Awaiting production',
            self::IN_PRODUCTION => 'In production',
            self::READY_TO_PUBLISH => 'Ready for publication',
            self::PUBLISHED => 'Published',
            self::REJECTED => 'Rejected',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }
}
