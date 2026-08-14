<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\MembershipPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MembershipPlanAdminController extends Controller
{
    public function index(Request $request): View
    {
        $scope = $request->get('scope');
        $scope = in_array($scope, ['platform', 'journal'], true) ? $scope : null;
        $active = $request->get('active');
        $active = in_array($active, ['1', '0'], true) ? $active : null;
        $q = trim((string) $request->get('q', ''));

        $plans = MembershipPlan::query()
            ->with('journal:id,title,slug')
            ->withCount('memberships')
            ->when($scope, fn ($query) => $query->where('scope', $scope))
            ->when($active !== null, fn ($query) => $query->where('is_active', $active === '1'))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', "%{$q}%")
                        ->orWhere('currency', 'like', "%{$q}%")
                        ->orWhereHas('journal', fn ($j) => $j->where('title', 'like', "%{$q}%"));
                });
            })
            ->orderByDesc('is_active')
            ->orderBy('scope')
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => MembershipPlan::query()->count(),
            'platform' => MembershipPlan::query()->where('scope', 'platform')->count(),
            'journal' => MembershipPlan::query()->where('scope', 'journal')->count(),
            'active' => MembershipPlan::query()->where('is_active', true)->count(),
            'inactive' => MembershipPlan::query()->where('is_active', false)->count(),
        ];

        $journals = Journal::query()->orderBy('title')->get(['id', 'title', 'slug']);

        return view('admin.membership-plans.index', compact(
            'plans',
            'journals',
            'stats',
            'scope',
            'active',
            'q',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        MembershipPlan::query()->create([
            'name' => $data['name'],
            'scope' => $data['scope'],
            'journal_id' => $data['scope'] === 'journal' ? ($data['journal_id'] ?? null) : null,
            'price_amount' => $data['price_amount'],
            'currency' => strtoupper($data['currency'] ?? 'NGN'),
            'duration_days' => $data['duration_days'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.membership-plans.index')
            ->with('status', 'Membership plan created.');
    }

    public function update(Request $request, MembershipPlan $membershipPlan): RedirectResponse
    {
        $data = $this->validated($request);

        $membershipPlan->update([
            'name' => $data['name'],
            'scope' => $data['scope'],
            'journal_id' => $data['scope'] === 'journal' ? ($data['journal_id'] ?? null) : null,
            'price_amount' => $data['price_amount'],
            'currency' => strtoupper($data['currency'] ?? 'NGN'),
            'duration_days' => $data['duration_days'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.membership-plans.index')
            ->with('status', 'Membership plan updated.');
    }

    public function toggle(MembershipPlan $membershipPlan): RedirectResponse
    {
        $membershipPlan->update(['is_active' => ! $membershipPlan->is_active]);

        return redirect()
            ->route('admin.membership-plans.index')
            ->with('status', $membershipPlan->is_active ? 'Plan activated.' : 'Plan deactivated.');
    }

    public function destroy(MembershipPlan $membershipPlan): RedirectResponse
    {
        if ($membershipPlan->memberships()->exists()) {
            return redirect()
                ->route('admin.membership-plans.index')
                ->with('status', 'Cannot delete a plan that already has memberships. Deactivate it instead.');
        }

        $membershipPlan->delete();

        return redirect()
            ->route('admin.membership-plans.index')
            ->with('status', 'Membership plan deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'scope' => ['required', 'in:platform,journal'],
            'journal_id' => ['nullable', 'required_if:scope,journal', 'exists:journals,id'],
            'price_amount' => ['required', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'max:8'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
