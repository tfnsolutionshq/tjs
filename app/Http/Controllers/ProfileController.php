<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();

        $layout = 'public';
        if ($user->isAdmin()) {
            $layout = 'admin';
        } elseif ($user->managedJournals()->isNotEmpty()) {
            $layout = 'journal-manage';
        } else {
            $layout = 'member';
        }

        return view('profile.edit', [
            'user' => $user,
            'profileLayout' => $layout,
            'journal' => $layout === 'journal-manage' ? $user->managedJournals()->first() : null,
            'managedJournals' => $user->managedJournals(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->safe()->only([
            'name',
            'email',
            'affiliation',
            'position',
            'orcid',
            'bio',
        ]);

        $user->fill($data);
        $user->is_public_reviewer = $request->boolean('is_public_reviewer');

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        if ($request->user()->isAdmin()) {
            return Redirect::route('profile.edit')
                ->withErrors(['password' => 'Platform admin accounts cannot be deleted from this page.'], 'userDeletion');
        }

        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
