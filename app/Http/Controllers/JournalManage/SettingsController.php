<?php

namespace App\Http\Controllers\JournalManage;

use App\Http\Controllers\Admin\JournalAdminController as PlatformJournalAdminController;
use App\Http\Controllers\Controller;
use App\Models\Journal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        private PlatformJournalAdminController $platform,
    ) {
    }

    public function edit(Journal $journal): View
    {
        $journal->load(['editorialBoard']);

        return view('journal-manage.settings', [
            'journal' => $journal,
            'manageJournal' => $journal,
            'theme' => $journal->themeConfig(),
            'board' => $journal->editorialBoard,
            'canMutate' => $journal->userMayMutate(auth()->user()),
        ]);
    }

    public function update(Request $request, Journal $journal): RedirectResponse
    {
        $this->platform->persistProfile($request, $journal, fromJournalManage: true);

        return redirect()
            ->route('journal.manage.settings.edit', $journal)
            ->with('status', 'Journal settings saved.');
    }
}
