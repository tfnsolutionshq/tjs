<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\JournalFee;
use App\Models\Volume;
use App\Support\JournalFeePurpose;
use App\Services\Storage\ArticleStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IssueAdminController extends Controller
{
    public function __construct(
        private ArticleStorage $storage,
    ) {
    }

    public function store(Request $request, Journal $journal, Volume $volume): RedirectResponse
    {
        abort_unless((int) $volume->journal_id === (int) $journal->id, 404);
        abort_unless($journal->userMayMutate($request->user()), 403, 'This journal has disabled edits by platform administrators.');

        $data = $request->validate([
            'issue_number' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('issues', 'issue_number')->where(fn ($q) => $q->where('volume_id', $volume->id)),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date', 'after_or_equal:period_start'],
            'status' => ['required', 'in:draft,published'],
            'cover' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'journal_fee_id' => ['nullable', 'integer'],
        ]);

        $publicationFee = $this->resolvePublicationFee($journal, $data['journal_fee_id'] ?? null);

        $issue = $volume->issues()->create([
            'issue_number' => $data['issue_number'],
            'title' => $data['title'] ?? null,
            'period_start' => $data['period_start'] ?? null,
            'period_end' => $data['period_end'] ?? null,
            'status' => $data['status'],
            'journal_fee_id' => $publicationFee?->id,
        ]);

        if ($request->hasFile('cover')) {
            [$issue->cover_path, $issue->cover_disk] = $this->storage->storeIssueCover($issue, $request->file('cover'));
            $issue->save();
        }

        return redirect()
            ->to($this->volumesIndexUrl($request, $journal))
            ->with('status', 'Issue created.');
    }

    public function update(Request $request, Journal $journal, Volume $volume, Issue $issue): RedirectResponse
    {
        abort_unless((int) $volume->journal_id === (int) $journal->id, 404);
        abort_unless((int) $issue->volume_id === (int) $volume->id, 404);
        abort_unless($journal->userMayMutate($request->user()), 403, 'This journal has disabled edits by platform administrators.');

        $data = $request->validate([
            'issue_number' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('issues', 'issue_number')
                    ->where(fn ($q) => $q->where('volume_id', $volume->id))
                    ->ignore($issue->id),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date', 'after_or_equal:period_start'],
            'status' => ['required', 'in:draft,published'],
            'cover' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'journal_fee_id' => ['nullable', 'integer'],
        ]);

        $publicationFee = $this->resolvePublicationFee($journal, $data['journal_fee_id'] ?? null);

        $issue->update([
            'issue_number' => $data['issue_number'],
            'title' => $data['title'] ?? null,
            'period_start' => $data['period_start'] ?? null,
            'period_end' => $data['period_end'] ?? null,
            'status' => $data['status'],
            'journal_fee_id' => $publicationFee?->id,
        ]);

        if ($request->hasFile('cover')) {
            [$issue->cover_path, $issue->cover_disk] = $this->storage->storeIssueCover($issue, $request->file('cover'));
            $issue->save();
        }

        return redirect()
            ->to($this->volumesIndexUrl($request, $journal))
            ->with('status', 'Issue updated.');
    }

    private function volumesIndexUrl(Request $request, Journal $journal): string
    {
        if ($request->attributes->get('manage_journal') || $request->routeIs('journal.manage.*')) {
            return route('journal.manage.volumes.index', $journal);
        }

        return route('admin.volumes.index', $journal);
    }

    private function resolvePublicationFee(Journal $journal, mixed $feeId): ?JournalFee
    {
        if (! $feeId) {
            return null;
        }

        return JournalFee::query()
            ->where('journal_id', $journal->id)
            ->where('id', (int) $feeId)
            ->active()
            ->forPurpose(JournalFeePurpose::ARTICLE)
            ->first();
    }
}
