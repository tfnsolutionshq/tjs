<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Submission;
use App\Services\Journal\SubmissionProductionService;
use App\Services\Storage\HybridDisk;
use App\Support\ProductionChecklist;
use App\Support\SubmissionStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductionController extends Controller
{
    public function __construct(
        private SubmissionProductionService $production,
        private HybridDisk $disks,
    ) {
    }

    public function index(Request $request): View
    {
        $journalId = $request->integer('journal_id') ?: null;
        $user = $request->user();

        $journalIds = $user->isAdmin()
            ? null
            : $user->productionJournals()->pluck('journals.id')->all();

        $base = Submission::query()
            ->with(['journal:id,title,slug', 'author:id,name,email', 'issue.volume', 'productionAssignee:id,name'])
            ->when($journalIds !== null, fn ($q) => $q->whereIn('journal_id', $journalIds))
            ->when($journalId, fn ($q) => $q->where('journal_id', $journalId));

        $stats = [
            'awaiting' => (clone $base)->where('status', SubmissionStatus::READY_FOR_PRODUCTION)->count(),
            'in_production' => (clone $base)->where('status', SubmissionStatus::IN_PRODUCTION)->count(),
            'ready' => (clone $base)->where('status', SubmissionStatus::READY_TO_PUBLISH)->count(),
            'completed' => (clone $base)->where('status', SubmissionStatus::PUBLISHED)->count(),
        ];

        $filter = $request->get('filter', 'awaiting');
        $query = clone $base;
        if ($filter === 'in_production') {
            $query->where('status', SubmissionStatus::IN_PRODUCTION);
        } elseif ($filter === 'ready') {
            $query->where('status', SubmissionStatus::READY_TO_PUBLISH);
        } elseif ($filter === 'completed') {
            $query->where('status', SubmissionStatus::PUBLISHED)->orderByDesc('updated_at')->limit(20);
        } else {
            $query->where('status', SubmissionStatus::READY_FOR_PRODUCTION);
        }

        $submissions = $query->orderByDesc('reviewed_at')->orderByDesc('updated_at')->paginate(15)->withQueryString();
        $journals = $user->productionJournals();

        return view('production.queue.index', compact('submissions', 'stats', 'filter', 'journals', 'journalId'));
    }

    public function show(Request $request, Submission $submission): View
    {
        $this->assertAccessible($request, $submission);

        $submission->load([
            'journal',
            'author',
            'issue.volume',
            'announcement',
            'productionFiles.uploader',
            'productionAssignee',
            'productionCompleter',
            'timelines.user',
            'publicationJournalFee',
        ]);

        $checklist = ProductionChecklist::normalize($submission->production_checklist);
        $currentFile = $submission->currentProductionFile();

        return view('production.queue.show', compact('submission', 'checklist', 'currentFile'));
    }

    public function start(Request $request, Submission $submission): RedirectResponse
    {
        $this->assertAccessible($request, $submission);

        try {
            $this->production->startProduction($submission, $request->user());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('production.queue.show', $submission)
            ->with('status', 'Production started.');
    }

    public function upload(Request $request, Submission $submission): RedirectResponse
    {
        $this->assertAccessible($request, $submission);

        $data = $request->validate([
            'production_document' => ['required', 'file', 'mimes:doc,docx,pdf', 'max:51200'],
        ]);

        try {
            $this->production->uploadProductionDocument(
                $submission,
                $request->user(),
                $data['production_document']
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('production.queue.show', $submission)
            ->with('status', 'Production document uploaded.');
    }

    public function complete(Request $request, Submission $submission): RedirectResponse
    {
        $this->assertAccessible($request, $submission);

        $checklistInput = $request->input('checklist', []);
        if (! is_array($checklistInput)) {
            $checklistInput = [];
        }

        try {
            $this->production->completeProduction($submission, $request->user(), $checklistInput);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('production.queue.show', $submission)
            ->with('status', 'Production marked complete. Editors can now publish this manuscript.');
    }

    public function downloadSource(Request $request, Submission $submission): StreamedResponse
    {
        $this->assertAccessible($request, $submission);
        abort_unless($submission->document_path, 404, 'Author manuscript not found.');

        return $this->disks->download(
            $submission->document_path,
            HybridDisk::KIND_DOCUMENTS,
            $submission->document_disk,
            'author-manuscript-'.basename($submission->document_path)
        );
    }

    public function downloadProduction(Request $request, Submission $submission): StreamedResponse
    {
        $this->assertAccessible($request, $submission);
        $file = $submission->currentProductionFile();
        abort_unless($file?->document_path, 404, 'Production document not found.');

        $name = $file->original_filename ?: basename($file->document_path);

        return $this->disks->download(
            $file->document_path,
            HybridDisk::KIND_DOCUMENTS,
            $file->document_disk,
            $name
        );
    }

    private function assertAccessible(Request $request, Submission $submission): void
    {
        abort_if(
            $submission->blocksEditorialProgress(),
            422,
            'This submission is awaiting payment and is not in the production queue yet.'
        );

        $user = $request->user();
        abort_unless($user, 403);

        if ($user->isAdmin()) {
            return;
        }

        $allowed = $user->journals()
            ->where('journals.id', $submission->journal_id)
            ->wherePivot('role', \App\Support\JournalTeamRoles::PRODUCTION_EDITOR)
            ->exists();

        abort_unless($allowed, 403);
    }
}
