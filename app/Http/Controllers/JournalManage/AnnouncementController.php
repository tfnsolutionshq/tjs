<?php

namespace App\Http\Controllers\JournalManage;

use App\Http\Controllers\Controller;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\JournalAnnouncement;
use App\Models\Volume;
use App\Services\Journal\CallForSubmissionService;
use App\Support\AnnouncementType;
use App\Support\SafeHtml;
use Illuminate\Http\JsonResponse;
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
        return view('journal-manage.announcements.form', $this->formData($journal, new JournalAnnouncement([
            'type' => AnnouncementType::CALL_FOR_SUBMISSIONS,
            'is_published' => false,
        ])));
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

        return view('journal-manage.announcements.form', $this->formData($journal, $announcement));
    }

    public function storeQuickIssue(Request $request, Journal $journal): JsonResponse
    {
        abort_unless($journal->userMayMutate($request->user()), 403);

        $data = $request->validate([
            'volume_number' => ['required', 'integer', 'min:1'],
            'year' => ['required', 'integer', 'min:1900', 'max:2100'],
            'volume_title' => ['nullable', 'string', 'max:255'],
            'issue_number' => ['required', 'integer', 'min:1'],
            'issue_title' => ['nullable', 'string', 'max:255'],
        ]);

        $volume = Volume::query()->firstOrCreate(
            [
                'journal_id' => $journal->id,
                'volume_number' => $data['volume_number'],
                'year' => $data['year'],
            ],
            [
                'title' => $data['volume_title'] ?? null,
                'status' => 'published',
            ]
        );

        if ($data['volume_title'] ?? null) {
            $volume->fill(['title' => $data['volume_title']])->save();
        }

        $issueExists = $volume->issues()
            ->where('issue_number', $data['issue_number'])
            ->exists();

        if ($issueExists) {
            return response()->json([
                'message' => 'That issue number already exists in this volume.',
                'errors' => ['issue_number' => ['Issue '.$data['issue_number'].' already exists in Vol. '.$volume->volume_number.'.']],
            ], 422);
        }

        $issue = $volume->issues()->create([
            'issue_number' => $data['issue_number'],
            'title' => $data['issue_title'] ?? null,
            'status' => 'draft',
        ]);

        $issue->load('volume');

        return response()->json([
            'issue' => [
                'id' => (string) $issue->id,
                'label' => $issue->label().($issue->title ? ' — '.$issue->title : ''),
            ],
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
        $data['body'] = SafeHtml::clean($data['body'] ?? null);

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

    /**
     * @return array<string, mixed>
     */
    private function formData(Journal $journal, JournalAnnouncement $announcement): array
    {
        $issues = $this->issuesForJournal($journal);

        return [
            'journal' => $journal,
            'announcement' => $announcement,
            'issues' => $issues,
            'issuesPayload' => $issues->map(fn (Issue $issue) => [
                'id' => (string) $issue->id,
                'label' => $issue->label().($issue->title ? ' — '.$issue->title : ''),
            ])->values(),
            'catalogDefaults' => $this->catalogDefaults($journal),
        ];
    }

    /**
     * @return array{volume_number: int, year: int, issue_number: int, volume_title: string, issue_title: string}
     */
    private function catalogDefaults(Journal $journal): array
    {
        $latestVolume = $journal->volumes()->orderByDesc('volume_number')->orderByDesc('year')->first();
        $volumeNumber = $latestVolume ? (int) $latestVolume->volume_number : 1;
        $year = $latestVolume ? (int) $latestVolume->year : (int) date('Y');
        $issueNumber = $latestVolume
            ? ((int) $latestVolume->issues()->max('issue_number')) + 1
            : 1;

        return [
            'volume_number' => max(1, $volumeNumber),
            'year' => $year,
            'issue_number' => max(1, $issueNumber),
            'volume_title' => '',
            'issue_title' => '',
        ];
    }
}
