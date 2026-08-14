<?php

namespace App\Http\Controllers\JournalManage;

use App\Http\Controllers\Admin\EditorialBoardAdminController as PlatformEditorialBoardAdminController;
use App\Http\Controllers\Controller;
use App\Models\EditorialBoardMember;
use App\Models\Journal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EditorialBoardController extends Controller
{
    public function __construct(
        private PlatformEditorialBoardAdminController $platform,
    ) {
    }

    public function store(Request $request, Journal $journal): RedirectResponse
    {
        $request->attributes->set('manage_journal', $journal);

        return $this->platform->store($request, $journal);
    }

    public function destroy(Request $request, Journal $journal, EditorialBoardMember $member): RedirectResponse
    {
        $request->attributes->set('manage_journal', $journal);

        return $this->platform->destroy($request, $journal, $member);
    }
}
