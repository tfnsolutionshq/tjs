<?php

namespace App\Http\Controllers;

use App\Models\Membership;
use App\Models\MembershipPlan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MembershipController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $activeMemberships = Membership::query()
            ->with(['plan', 'journal:id,title,slug'])
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->orderByDesc('ends_at')
            ->get();

        $plans = MembershipPlan::query()
            ->where('is_active', true)
            ->with('journal:id,title,slug')
            ->orderBy('scope')
            ->orderBy('price_amount')
            ->get();

        return view('memberships.index', compact('activeMemberships', 'plans'));
    }
}
