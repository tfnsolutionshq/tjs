<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\User;
use App\Support\JournalTeamRoles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class JournalTeamAdminController extends Controller
{
    public function store(Request $request, Journal $journal): RedirectResponse
    {
        $mode = $request->input('mode', 'existing');

        if ($mode === 'create') {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'confirmed', Password::defaults()],
                'role' => ['required', Rule::in(JournalTeamRoles::all())],
            ]);

            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => 'member',
            ]);

            $journal->assignTeamMember($user, $data['role']);

            $user->sendEmailVerificationNotification();

            return back()->with('status', 'Journal team member created and assigned. A verification code was emailed to them.');
        }

        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'role' => ['required', Rule::in(JournalTeamRoles::all())],
        ]);

        $user = User::query()->where('email', $data['email'])->first();
        if (! $user) {
            return back()
                ->withInput()
                ->withErrors(['email' => 'No user found with that email. Create a new account instead.']);
        }

        if ($user->isAdmin() && $data['role'] !== JournalTeamRoles::ADMIN) {
            // Platform admins already have full access; still allow recording an explicit journal role.
        }

        $journal->assignTeamMember($user, $data['role']);

        return back()->with('status', "{$user->name} assigned as ".JournalTeamRoles::label($data['role']).'.');
    }

    public function update(Request $request, Journal $journal, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(JournalTeamRoles::all())],
        ]);

        if (! $journal->users()->where('users.id', $user->id)->exists()) {
            abort(404);
        }

        $journal->assignTeamMember($user, $data['role']);

        return back()->with('status', 'Team role updated.');
    }

    public function destroy(Journal $journal, User $user): RedirectResponse
    {
        if (! $journal->users()->where('users.id', $user->id)->exists()) {
            abort(404);
        }

        $journal->removeTeamMember($user);

        return back()->with('status', "{$user->name} removed from this journal’s team.");
    }
}
