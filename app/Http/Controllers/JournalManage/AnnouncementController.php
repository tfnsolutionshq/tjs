<?php

namespace App\Http\Controllers\JournalManage;

use App\Http\Controllers\Controller;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\JournalAnnouncement;
use App\Services\Journal\CallForSubmissionService;
use App\Support\AnnouncementType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function __construct(
        private CallForSubmissionService $calls,
    ) {
    }

    public function index(Journal $journal): View
    {
        $announcements = $journal->announcements()
            ->with(['issue.volume', 'creator:id,name'])
            ->withCount('submissions')
            ->orderByDesc('is_published')
            ->orderByDesc('opens_at')
            ->orderByDesc('created_at')
            ->paginate(12);

        $stats = [
            'total' => $journal->announcements()->count(),
            'published' => $journal->announcements()->where('is_published', true)->count(),
            'accepting' => $this->calls->openCallsQuery()->where('journal_id', $journal->id)->count(),
        ];

        return view('journal-manage.announcements.index', compact('journal', 'announcements', 'stats'));
    }

    public function create(Journal $journal): View
    {
        return view('journal-manage.announcements.form', [
            'journal' => $journal,
            'announcement' => new JournalAnnouncement([
                'type' => AnnouncementType::CALL_FOR_SUBMISSIONS,
                'is_published' => false,
            ]),
            'issues' => $this->issuesForJournal($journal),
        ]);
    }

    public function store(Request $request, Journal $journal): RedirectResponse
    {
        $data = $this->validated($request, $journal);
        $data['created_by'] = $request->user()?->id;

        $journal->announcements()->create($data);

        return redirect()
            ->route('journal.manage.announcements.index', $journal)
            ->with('status', 'Announcement created.');
    }

    public function edit(Journal $journal, JournalAnnouncement $announcement): View
    {
        abort_unless((int) $announcement->journal_id === (int) $journal->id, 404);

        return view('journal-manage.announcements.form', [
            'journal' => $journal,
            'announcement' => $announcement,
            'issues' => $this->issuesForJournal($journal),
        ]);
    }

    public function update(Request $request, Journal $journal, JournalAnnouncement $announcement): RedirectResponse
    {
        abort_unless((int) $announcement->journal_id === (int) $journal->id, 404);

        $announcement->update($this->validated($request, $journal, $announcement));

        return redirect()
            ->route('journal.manage.announcements.index', $journal)
            ->with('status', 'Announcement updated.');
    }

    public function close(Journal $journal, JournalAnnouncement $announcement): RedirectResponse
    {
        abort_unless((int) $announcement->journal_id === (int) $journal->id, 404);
        abort_unless($announcement->isCallForSubmissions(), 422, 'Only calls for submissions can be closed.');

        $announcement->update([
            'closes_at' => now(),
        ]);

        return redirect()
            ->route('journal.manage.announcements.index', $journal)
            ->with('status', 'Call for submissions closed.');
    }

    public function destroy(Journal $journal, JournalAnnouncement $announcement): RedirectResponse
    {
        abort_unless((int) $announcement->journal_id === (int) $journal->id, 404);
        abort_if($announcement->submissions()->exists(), 422, 'Cannot delete an announcement that already has submissions.');

        $announcement->delete();

        return redirect()
            ->route('journal.manage.announcements.index', $journal)
            ->with('status', 'Announcement deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, Journal $journal, ?JournalAnnouncement $announcement = null): array
    {
        $type = $request->string('type')->toString();

        $rules = [
            'type' => AnnouncementType::requiredRule(),
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string'],
            'is_published' => ['nullable', 'boolean'],
            'issue_id' => ['nullable', 'exists:issues,id'],
            'opens_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date', 'after:opens_at'],
        ];

        if (AnnouncementType::isCallForSubmissions($type)) {
            $rules['issue_id'] = ['required', 'exists:issues,id'];
            $rules['opens_at'] = ['required', 'date'];
            $rules['closes_at'] = ['required', 'date', 'after:opens_at'];
        }

        $data = $request->validate($rules);

        if (AnnouncementType::isCallForSubmissions($data['type'])) {
            $issue = Issue::query()->with('volume')->findOrFail($data['issue_id']);
            $this->calls->assertIssueBelongsToJournal($journal, $issue);
        } else {
            $data['issue_id'] = null;
            $data['opens_at'] = null;
            $data['closes_at'] = null;
        }

        $data['is_published'] = $request->boolean('is_published');

        return $data;
    }

    private function issuesForJournal(Journal $journal)
    {
        return Issue::query()
            ->with('volume')
            ->whereHas('volume', fn ($query) => $query->where('journal_id', $journal->id))
            ->orderByDesc('id')
            ->get();
    }
}
