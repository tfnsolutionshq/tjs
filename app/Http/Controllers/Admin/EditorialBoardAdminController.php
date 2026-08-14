<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EditorialBoardMember;
use App\Models\Journal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EditorialBoardAdminController extends Controller
{
    public function store(Request $request, Journal $journal): RedirectResponse
    {
        abort_unless($journal->userMayMutate($request->user()), 403, 'This journal has disabled edits by platform administrators.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'role_title' => ['nullable', 'string', 'max:255'],
            'affiliation' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'orcid' => ['nullable', 'string', 'max:64'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $journal->editorialBoard()->create([
            'name' => $data['name'],
            'role_title' => $data['role_title'] ?? null,
            'affiliation' => $data['affiliation'] ?? null,
            'email' => $data['email'] ?? null,
            'orcid' => $data['orcid'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return redirect()
            ->to($this->returnUrl($request, $journal))
            ->with('status', 'Editorial board member added.');
    }

    public function destroy(Request $request, Journal $journal, EditorialBoardMember $member): RedirectResponse
    {
        abort_unless((int) $member->journal_id === (int) $journal->id, 404);
        abort_unless($journal->userMayMutate($request->user()), 403, 'This journal has disabled edits by platform administrators.');

        $member->delete();

        return redirect()
            ->to($this->returnUrl($request, $journal))
            ->with('status', 'Editorial board member removed.');
    }

    private function returnUrl(Request $request, Journal $journal): string
    {
        if ($request->attributes->get('manage_journal') || $request->routeIs('journal.manage.*')) {
            return route('journal.manage.settings.edit', $journal).'#editorial-board';
        }

        return route('admin.journals.edit', $journal).'#editorial-board';
    }
}
