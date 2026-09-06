<?php

namespace App\Http\Controllers\JournalManage;

use App\Http\Controllers\Admin\IssueAdminController as PlatformIssueAdminController;
use App\Http\Controllers\Admin\VolumeAdminController as PlatformVolumeAdminController;
use App\Http\Controllers\Controller;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\Volume;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VolumeController extends Controller
{
    public function __construct(
        private PlatformVolumeAdminController $volumes,
        private PlatformIssueAdminController $issues,
    ) {
    }

    public function index(Journal $journal): View
    {
        $request = request();
        $request->attributes->set('manage_journal', $journal);

        return $this->volumes->index($journal)->with([
            'manageJournal' => $journal,
        ]);
    }

    public function store(Request $request, Journal $journal): RedirectResponse
    {
        $request->attributes->set('manage_journal', $journal);

        return $this->volumes->store($request, $journal);
    }

    public function update(Request $request, Journal $journal, Volume $volume): RedirectResponse
    {
        $request->attributes->set('manage_journal', $journal);

        return $this->volumes->update($request, $journal, $volume);
    }

    public function storeIssue(Request $request, Journal $journal, Volume $volume): RedirectResponse
    {
        $request->attributes->set('manage_journal', $journal);

        return $this->issues->store($request, $journal, $volume);
    }

    public function updateIssue(Request $request, Journal $journal, Volume $volume, Issue $issue): RedirectResponse
    {
        $request->attributes->set('manage_journal', $journal);

        return $this->issues->update($request, $journal, $volume, $issue);
    }
}
