<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DoiCreditTopup;
use App\Models\Journal;
use App\Services\Doi\DoiCreditService;
use App\Support\DoiSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DoiAdminController extends Controller
{
    public function __construct(private DoiCreditService $credits)
    {
    }

    public function index(Request $request): View
    {
        $journals = Journal::query()
            ->orderByDesc('doi_credits_balance')
            ->orderBy('title')
            ->paginate(30)
            ->withQueryString();

        $recentTopups = DoiCreditTopup::query()
            ->with(['journal:id,title,slug', 'creator:id,name'])
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        return view('admin.doi.index', [
            'journals' => $journals,
            'recentTopups' => $recentTopups,
            'rate' => DoiSettings::usdToNgn(),
            'priceUsd' => DoiSettings::creditPriceUsd(),
            'priceNgn' => DoiSettings::creditPriceNgn(),
            'thresholdAbsolute' => DoiSettings::thresholdAbsolute(),
            'thresholdPercent' => DoiSettings::thresholdPercent(),
        ]);
    }

    public function topUp(Request $request, Journal $journal): RedirectResponse
    {
        $data = $request->validate([
            'credits' => ['required', 'integer', 'min:1', 'max:100000'],
            'amount_usd' => ['nullable', 'numeric', 'min:0'],
            'amount_ngn' => ['nullable', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $this->credits->topUp(
            $journal,
            (int) $data['credits'],
            $request->user(),
            isset($data['amount_usd']) ? (float) $data['amount_usd'] : null,
            isset($data['amount_ngn']) ? (int) $data['amount_ngn'] : null,
            $data['note'] ?? null,
        );

        return redirect()
            ->route('admin.doi.index')
            ->with('status', 'Added '.$data['credits'].' DOI credit(s) to '.$journal->title.'.');
    }
}
