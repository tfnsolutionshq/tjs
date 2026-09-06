<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\User;
use App\Support\JournalTeamRoles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserAdminController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()
            ->withCount(['journals', 'submissions', 'memberships'])
            ->with(['journals' => fn ($q) => $q->orderBy('title')])
            ->orderByDesc('created_at');

        if ($search = trim((string) $request->get('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('affiliation', 'like', "%{$search}%")
                    ->orWhere('orcid', 'like', "%{$search}%");
            });
        }

        $role = $request->get('role');
        if (in_array($role, ['admin', 'member', 'reviewer', 'editor'], true)) {
            $query->where('role', $role);
        }

        $journalFilter = $request->get('journal');
        if ($journalFilter === 'assigned') {
            $query->whereHas('journals');
        } elseif ($journalFilter === 'unassigned') {
            $query->whereDoesntHave('journals');
        }

        $journalId = $request->get('journal_id');
        if ($journalId && is_numeric($journalId)) {
            $query->whereHas('journals', fn ($q) => $q->where('journals.id', (int) $journalId));
        }

        $perPage = (int) $request->integer('per_page', 12);
        if (! in_array($perPage, [12, 24, 48], true)) {
            $perPage = 12;
        }

        $users = $query->paginate($perPage)->withQueryString();

        $journals = Journal::query()->orderBy('title')->get(['id', 'title', 'slug', 'subtitle', 'issn']);

        $stats = [
            'total' => User::query()->count(),
            'admin' => User::query()->where('role', 'admin')->count(),
            'reviewer' => User::query()->where('role', 'reviewer')->count(),
            'member' => User::query()->where('role', 'member')->count(),
            'with_journals' => User::query()->whereHas('journals')->count(),
        ];

        return view('admin.users.index', compact('users', 'stats', 'perPage', 'role', 'journalFilter', 'journalId', 'journals'));
    }

    public function show(User $user): View
    {
        $user->load([
            'journals' => fn ($q) => $q->orderBy('title'),
            'memberships' => fn ($q) => $q->with('plan')->latest()->limit(10),
            'submissions' => fn ($q) => $q->with('journal:id,title,slug')->latest()->limit(8),
        ]);

        $user->loadCount(['journals', 'submissions', 'memberships', 'purchases']);

        return view('admin.users.show', [
            'user' => $user,
            'teamRoles' => JournalTeamRoles::all(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(['admin', 'member', 'reviewer'])],
            'affiliation' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'orcid' => ['nullable', 'string', 'max:64'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'is_public_reviewer' => ['sometimes', 'boolean'],
        ]);

        // Never remove the last platform admin.
        if ($user->isAdmin() && $data['role'] !== 'admin') {
            $otherAdmins = User::query()->where('role', 'admin')->where('id', '!=', $user->id)->count();
            if ($otherAdmins === 0) {
                return back()->withErrors(['role' => 'Cannot demote the last platform admin.']);
            }
        }

        if ((int) $user->id === (int) $request->user()->id && $data['role'] !== 'admin') {
            return back()->withErrors(['role' => 'You cannot demote your own platform admin account.']);
        }

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'affiliation' => $data['affiliation'] ?? null,
            'position' => $data['position'] ?? null,
            'orcid' => $data['orcid'] ?? null,
            'bio' => $data['bio'] ?? null,
            'is_public_reviewer' => $request->boolean('is_public_reviewer'),
        ]);
        $user->save();

        return redirect()
            ->route('admin.users.show', $user)
            ->with('status', 'User updated.');
    }
}
