<?php

namespace App\Http\Controllers\Api\V1\JournalManage;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\JournalAnnouncementResource;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\JournalAnnouncement;
use App\Services\Journal\CallForSubmissionService;
use App\Support\AnnouncementType;
use App\Support\SafeHtml;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function __construct(
        private CallForSubmissionService $calls,
    ) {
    }

    public function index(Journal $journal): JsonResponse
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

        return JournalAnnouncementResource::collection($announcements)
            ->additional(['meta' => ['stats' => $stats]])
            ->response();
    }

    public function store(Request $request, Journal $journal): JsonResponse
    {
        $data = $this->validated($request, $journal);
        $data['created_by'] = $request->user()?->id;

        $announcement = $journal->announcements()->create($data);
        $announcement->load(['issue.volume', 'creator:id,name']);

        return response()->json([
            'data' => new JournalAnnouncementResource($announcement),
        ], 201);
    }

    public function close(Journal $journal, JournalAnnouncement $announcement): JsonResponse
    {
        abort_unless((int) $announcement->journal_id === (int) $journal->id, 404);
        abort_unless($announcement->isCallForSubmissions(), 422, 'Only calls for submissions can be closed.');

        $announcement->update(['closes_at' => now()]);
        $announcement->load(['issue.volume', 'creator:id,name']);

        return response()->json([
            'data' => [
                'message' => 'Call for submissions closed.',
                'announcement' => new JournalAnnouncementResource($announcement),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, Journal $journal): array
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
}
