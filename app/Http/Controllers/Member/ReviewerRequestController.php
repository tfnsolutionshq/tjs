<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Services\Journal\ReviewerRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewerRequestController extends Controller
{
    public function store(Request $request, Journal $journal, ReviewerRequestService $service): RedirectResponse
    {
        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $service->submit(
            $request->user(),
            $journal,
            $data['message'] ?? null,
        );

        return back()->with('status', 'Your reviewer request for '.$journal->title.' has been submitted.');
    }

    public function destroy(Request $request, Journal $journal, ReviewerRequestService $service): RedirectResponse
    {
        $service->withdraw($request->user(), $journal);

        return back()->with('status', 'Your reviewer request has been withdrawn.');
    }
}
