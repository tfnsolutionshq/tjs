<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\Volume;
use App\Services\Storage\ArticleStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VolumeAdminController extends Controller
{
    public function __construct(
        private ArticleStorage $storage,
    ) {
    }

    public function index(Journal $journal): View
    {
        $volumes = $journal->volumes()
            ->with(['issues' => fn ($q) => $q->orderBy('issue_number')])
            ->withCount('issues')
            ->orderByDesc('year')
            ->orderByDesc('volume_number')
            ->get();

        $stats = [
            'volumes' => $volumes->count(),
            'issues' => $volumes->sum('issues_count'),
            'published' => $volumes->where('status', 'published')->count(),
            'draft' => $volumes->where('status', 'draft')->count(),
        ];

        $canMutate = $journal->userMayMutate(auth()->user());

        return view('admin.volumes.index', compact('journal', 'volumes', 'stats', 'canMutate'));
    }

    public function store(Request $request, Journal $journal): RedirectResponse
    {
        abort_unless($journal->userMayMutate($request->user()), 403, 'This journal has disabled edits by platform administrators.');

        $data = $request->validate([
            'volume_number' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('volumes', 'volume_number')
                    ->where(fn ($q) => $q->where('journal_id', $journal->id)->where('year', $request->integer('year'))),
            ],
            'year' => ['required', 'integer', 'min:1900', 'max:2100'],
            'title' => ['nullable', 'string', 'max:255'],
            'introduction' => ['nullable', 'string'],
            'issn' => ['nullable', 'string', 'max:32'],
            'status' => ['required', 'in:draft,published'],
            'cover' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $volume = $journal->volumes()->create([
            'volume_number' => $data['volume_number'],
            'year' => $data['year'],
            'title' => $data['title'] ?? null,
            'introduction' => $data['introduction'] ?? null,
            'issn' => $data['issn'] ?? null,
            'status' => $data['status'],
        ]);

        if ($request->hasFile('cover')) {
            $volume->cover_path = $this->storage->storeVolumeCover($volume, $request->file('cover'));
            $volume->save();
        }

        return redirect()
            ->to($this->indexUrl($request, $journal))
            ->with('status', 'Volume created.');
    }

    public function update(Request $request, Journal $journal, Volume $volume): RedirectResponse
    {
        abort_unless((int) $volume->journal_id === (int) $journal->id, 404);
        abort_unless($journal->userMayMutate($request->user()), 403, 'This journal has disabled edits by platform administrators.');

        $data = $request->validate([
            'volume_number' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('volumes', 'volume_number')
                    ->where(fn ($q) => $q->where('journal_id', $journal->id)->where('year', $request->integer('year')))
                    ->ignore($volume->id),
            ],
            'year' => ['required', 'integer', 'min:1900', 'max:2100'],
            'title' => ['nullable', 'string', 'max:255'],
            'introduction' => ['nullable', 'string'],
            'issn' => ['nullable', 'string', 'max:32'],
            'status' => ['required', 'in:draft,published'],
            'cover' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $volume->update([
            'volume_number' => $data['volume_number'],
            'year' => $data['year'],
            'title' => $data['title'] ?? null,
            'introduction' => $data['introduction'] ?? null,
            'issn' => $data['issn'] ?? null,
            'status' => $data['status'],
        ]);

        if ($request->hasFile('cover')) {
            $volume->cover_path = $this->storage->storeVolumeCover($volume, $request->file('cover'));
            $volume->save();
        }

        return redirect()
            ->to($this->indexUrl($request, $journal))
            ->with('status', 'Volume updated.');
    }

    public function indexUrl(Request $request, Journal $journal): string
    {
        if ($request->attributes->get('manage_journal') || $request->routeIs('journal.manage.*')) {
            return route('journal.manage.volumes.index', $journal);
        }

        return route('admin.volumes.index', $journal);
    }
}
