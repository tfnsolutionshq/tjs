<?php

namespace App\Http\Controllers\JournalManage;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\MembershipPlan;
use App\Support\Licenses;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MembershipPlanController extends Controller
{
    public function index(Request $request, Journal $journal): View
    {
        $active = $request->get('active');
        $active = in_array($active, ['1', '0'], true) ? $active : null;
        $q = trim((string) $request->get('q', ''));

        $plans = MembershipPlan::query()
            ->with('journal:id,title,slug')
            ->withCount('memberships')
            ->where('scope', 'journal')
            ->where('journal_id', $journal->id)
            ->when($active !== null, fn ($query) => $query->where('is_active', $active === '1'))
            ->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => MembershipPlan::query()->where('scope', 'journal')->where('journal_id', $journal->id)->count(),
            'platform' => 0,
            'journal' => MembershipPlan::query()->where('scope', 'journal')->where('journal_id', $journal->id)->count(),
            'active' => MembershipPlan::query()->where('scope', 'journal')->where('journal_id', $journal->id)->where('is_active', true)->count(),
            'inactive' => MembershipPlan::query()->where('scope', 'journal')->where('journal_id', $journal->id)->where('is_active', false)->count(),
        ];

        $journals = collect([$journal]);
        $scope = 'journal';
        $manageJournal = $journal;

        return view('admin.membership-plans.index', compact(
            'plans',
            'journals',
            'stats',
            'scope',
            'active',
            'q',
            'manageJournal',
            'journal',
        ));
    }

    public function store(Request $request, Journal $journal): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price_amount' => ['required', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'max:8'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        MembershipPlan::query()->create([
            'name' => $data['name'],
            'scope' => 'journal',
            'journal_id' => $journal->id,
            'price_amount' => $data['price_amount'],
            'currency' => strtoupper($data['currency'] ?? 'NGN'),
            'duration_days' => $data['duration_days'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('journal.manage.membership-plans.index', $journal)
            ->with('status', 'Membership plan created.');
    }

    public function update(Request $request, Journal $journal, MembershipPlan $membershipPlan): RedirectResponse
    {
        abort_unless(
            $membershipPlan->scope === 'journal' && (int) $membershipPlan->journal_id === (int) $journal->id,
            404
        );

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'price_amount' => ['required', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'max:8'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $membershipPlan->update([
            'name' => $data['name'],
            'scope' => 'journal',
            'journal_id' => $journal->id,
            'price_amount' => $data['price_amount'],
            'currency' => strtoupper($data['currency'] ?? 'NGN'),
            'duration_days' => $data['duration_days'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('journal.manage.membership-plans.index', $journal)
            ->with('status', 'Membership plan updated.');
    }

    public function toggle(Journal $journal, MembershipPlan $membershipPlan): RedirectResponse
    {
        abort_unless(
            $membershipPlan->scope === 'journal' && (int) $membershipPlan->journal_id === (int) $journal->id,
            404
        );

        $membershipPlan->update(['is_active' => ! $membershipPlan->is_active]);

        return redirect()
            ->route('journal.manage.membership-plans.index', $journal)
            ->with('status', $membershipPlan->is_active ? 'Plan activated.' : 'Plan deactivated.');
    }

    public function destroy(Journal $journal, MembershipPlan $membershipPlan): RedirectResponse
    {
        abort_unless(
            $membershipPlan->scope === 'journal' && (int) $membershipPlan->journal_id === (int) $journal->id,
            404
        );

        if ($membershipPlan->memberships()->exists()) {
            return redirect()
                ->route('journal.manage.membership-plans.index', $journal)
                ->with('status', 'Cannot delete a plan that already has memberships. Deactivate it instead.');
        }

        $membershipPlan->delete();

        return redirect()
            ->route('journal.manage.membership-plans.index', $journal)
            ->with('status', 'Membership plan deleted.');
    }
}
