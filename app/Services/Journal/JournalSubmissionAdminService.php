<?php

namespace App\Services\Journal;

use App\Models\Article;
use App\Models\Issue;
use App\Models\ReviewerAssignment;
use App\Models\Submission;
use App\Models\SubmissionTimeline;
use App\Models\User;
use App\Support\Licenses;
use App\Support\ReviewType;
use App\Support\SubmissionStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JournalSubmissionAdminService
{
    /**
     * @param  array{review_type: string}  $data
     */
    public function updateReviewType(User $actor, Submission $submission, array $data): Submission
    {
        $this->assertEditorialProgress($submission);

        $submission->update([
            'review_type' => $data['review_type'],
        ]);

        SubmissionTimeline::query()->create([
            'submission_id' => $submission->id,
            'user_id' => $actor->id,
            'event' => 'review_type_updated',
            'metadata' => [
                'review_type' => $data['review_type'],
            ],
        ]);

        return $submission->fresh(['journal', 'author', 'reviewer', 'assignments.reviewer']);
    }

    /**
     * @param  array{reviewer_id: int, priority?: int|null, due_at?: string|null}  $data
     */
    public function assignReviewer(User $actor, Submission $submission, array $data): Submission
    {
        $this->assertEditorialProgress($submission);

        DB::transaction(function () use ($actor, $submission, $data) {
            $submission->update([
                'status' => 'under_review',
                'reviewer_id' => $data['reviewer_id'],
            ]);

            ReviewerAssignment::query()->create([
                'submission_id' => $submission->id,
                'reviewer_id' => $data['reviewer_id'],
                'status' => 'assigned',
                'priority' => $data['priority'] ?? 3,
                'due_at' => $data['due_at'] ?? null,
            ]);

            SubmissionTimeline::query()->create([
                'submission_id' => $submission->id,
                'user_id' => $actor->id,
                'event' => 'reviewer_assigned',
                'metadata' => [
                    'reviewer_id' => (int) $data['reviewer_id'],
                ],
            ]);
        });

        return $submission->fresh(['journal', 'author', 'reviewer', 'assignments.reviewer']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function publishToIssue(User $actor, Submission $submission, array $data): Article
    {
        $submission->loadMissing('productionFiles');

        abort_unless(
            in_array($submission->status, [SubmissionStatus::READY_TO_PUBLISH, SubmissionStatus::APPROVED], true),
            422,
            'Only manuscripts ready for publication can be published.'
        );

        abort_unless(
            $submission->hasProductionDocument(),
            422,
            'A final production document must be uploaded and production completed before this manuscript can be published.'
        );

        $productionFile = $submission->currentProductionFile();
        abort_unless($productionFile, 422, 'Production document is missing.');

        $issue = Issue::query()->with('volume')->findOrFail($data['issue_id']);
        abort_unless((int) $issue->volume?->journal_id === (int) $submission->journal_id, 422, 'Issue must belong to the submission journal.');
        if ($submission->issue_id) {
            abort_unless((int) $issue->id === (int) $submission->issue_id, 422, 'Approved submissions must be published to their target issue.');
        }

        $article = DB::transaction(function () use ($actor, $submission, $issue, $data, $productionFile) {
            $slugBase = $data['slug'] ?? Str::slug($submission->title);
            $slug = $this->uniqueSlug((int) $submission->journal_id, $slugBase !== '' ? $slugBase : 'article', $submission->id);

            /** @var Article $article */
            $article = Article::query()->updateOrCreate(
                ['submission_id' => $submission->id],
                [
                    'journal_id' => $submission->journal_id,
                    'issue_id' => $issue->id,
                    'author_user_id' => $submission->author_id,
                    'slug' => $slug,
                    'title' => $submission->title,
                    'abstract' => $submission->abstract,
                    'category' => $submission->category,
                    'keywords' => $submission->keywords,
                    'doi' => $data['doi'] ?? null,
                    'license' => ! empty($data['license']) ? Licenses::normalize($data['license']) : null,
                    'page_range' => $data['page_range'] ?? null,
                    'visibility' => $data['visibility'] ?? 'open',
                    'document_path' => $productionFile->document_path,
                    'document_disk' => $productionFile->document_disk,
                    'status' => 'published',
                    'published_at' => now(),
                ]
            );

            $submission->update(['status' => SubmissionStatus::PUBLISHED]);

            $article->authors()->delete();
            $submission->loadMissing('author');

            $authorsText = trim((string) ($data['authors_text'] ?? ''));
            if ($authorsText !== '') {
                $this->createAuthorsFromText($article, $authorsText);
            } else {
                $name = trim((string) ($data['author_name'] ?? ''));
                if ($name === '') {
                    $name = $submission->author?->name ?: 'Author';
                }

                $article->authors()->create([
                    'name' => $name,
                    'email' => $submission->author?->email,
                    'affiliation' => $submission->author?->affiliation,
                    'orcid' => $submission->author?->orcid,
                    'role' => 'author',
                    'is_corresponding' => true,
                    'sort_order' => 0,
                ]);
            }

            SubmissionTimeline::query()->create([
                'submission_id' => $submission->id,
                'user_id' => $actor->id,
                'event' => 'published_to_issue',
                'metadata' => [
                    'issue_id' => $issue->id,
                    'article_id' => $article->id,
                    'production_file_id' => $productionFile->id,
                ],
            ]);

            return $article;
        });

        app(\App\Services\Doi\DoiDepositService::class)->maybeAutoDeposit($article->fresh(), $actor);

        return $article->fresh(['journal', 'authors', 'issue.volume']);
    }

    public function assertEditorialProgress(Submission $submission): void
    {
        abort_if(
            $submission->blocksEditorialProgress(),
            422,
            'This submission is awaiting payment. Editorial actions are unavailable until the author pays the submission fee.'
        );
    }

    private function uniqueSlug(int $journalId, string $slug, string $submissionId): string
    {
        $base = $slug;
        $candidate = $base;
        $i = 2;

        while (
            Article::query()
                ->where('journal_id', $journalId)
                ->where('slug', $candidate)
                ->where(function ($q) use ($submissionId) {
                    $q->whereNull('submission_id')
                        ->orWhere('submission_id', '!=', $submissionId);
                })
                ->exists()
        ) {
            $candidate = $base.'-'.$i;
            $i++;
        }

        return $candidate;
    }

    private function createAuthorsFromText(Article $article, string $authorsText): void
    {
        $lines = preg_split('/\r\n|\r|\n/', $authorsText) ?: [];
        $order = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', explode('|', $line));
            $name = $parts[0] ?? '';
            if ($name === '') {
                continue;
            }

            $article->authors()->create([
                'name' => $name,
                'email' => ($parts[1] ?? '') !== '' ? $parts[1] : null,
                'affiliation' => ($parts[2] ?? '') !== '' ? $parts[2] : null,
                'orcid' => ($parts[3] ?? '') !== '' ? $parts[3] : null,
                'role' => 'author',
                'is_corresponding' => $order === 0,
                'sort_order' => $order,
            ]);

            $order++;
        }
    }
}
