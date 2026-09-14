<?php

namespace App\Support;

use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApiNotFoundMessage
{
    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>|null  $model
     */
    public static function forModel(?string $model): string
    {
        $name = $model !== null ? class_basename($model) : null;

        return match ($name) {
            'Journal' => 'Journal not found.',
            'Article' => 'Article not found.',
            'Submission' => 'Submission not found.',
            'PaymentTransaction' => 'Payment not found.',
            'MembershipPlan' => 'Membership plan not found.',
            'JournalReviewerRequest' => 'Reviewer request not found.',
            'Announcement' => 'Announcement not found.',
            'SubmissionRevision' => 'Submission revision not found.',
            default => 'Resource not found.',
        };
    }

    public static function forHttpException(HttpExceptionInterface $exception): string
    {
        $message = trim($exception->getMessage());

        if ($message === '') {
            return 'Resource not found.';
        }

        if (preg_match('/No query results for model \[([^\]]+)\]/', $message, $matches) === 1) {
            return self::forModel($matches[1]);
        }

        if ($exception instanceof NotFoundHttpException && str_contains($message, 'could not be found')) {
            return 'The requested endpoint was not found.';
        }

        return $message;
    }
}
