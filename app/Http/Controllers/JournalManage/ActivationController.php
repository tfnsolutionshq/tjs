<?php

namespace App\Http\Controllers\JournalManage;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Support\JournalActivation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivationController extends Controller
{
    public function show(Journal $journal): View
    {
        return view('journal-manage.activation', [
            'journal' => $journal,
            'price' => JournalActivation::price(),
            'days' => JournalActivation::durationDays(),
            'currency' => JournalActivation::currency(),
            'unlocked' => $journal->managementUnlocked(),
        ]);
    }

    public function skip(Request $request, Journal $journal): RedirectResponse
    {
        abort_unless($request->user()?->canManageJournal($journal), 403);

        return redirect()
            ->route('journal.manage.dashboard', $journal)
            ->with('status', 'You can pay the activation fee anytime. Major management tools stay locked until then.');
    }
}
