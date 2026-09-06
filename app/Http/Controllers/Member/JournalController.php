<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Services\Journal\CategoryService;
use App\Support\JournalTeamRoles;
use App\Support\ReviewType;
use App\Support\SafeHtml;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JournalController extends Controller
{
    public function create(): View
    {
        return view('member.journals.create', [
            'journal' => new Journal([
                'review_type' => ReviewType::CLOSED,
                'language' => 'en',
                'is_active' => true,
            ]),
        ]);
    }

    public function store(Request $request, CategoryService $categories): RedirectResponse
    {
        if ($request->filled('slug')) {
            $request->merge(['slug' => Str::slug($request->string('slug')->trim())]);
        } elseif ($request->filled('title')) {
            $request->merge(['slug' => Str::slug($request->string('title')->trim())]);
        }

        if ($request->filled('initials')) {
            $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $request->string('initials')->trim()) ?? '');
            $request->merge(['initials' => $clean !== '' ? $clean : null]);
        } else {
            $request->merge(['initials' => null]);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('journals', 'slug')],
            'initials' => ['nullable', 'string', 'max:8', 'regex:/^[A-Z0-9]+$/'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:20000'],
            'review_type' => ReviewType::requiredRule(),
            'publisher' => ['nullable', 'string', 'max:255'],
            'language' => ['nullable', 'string', 'max:16', Rule::in(\App\Support\JournalLanguages::codes())],
        ], [
            'initials.max' => 'Initials may be at most 8 characters.',
            'initials.regex' => 'Use only letters and numbers in the initials.',
            'slug.unique' => 'That URL slug is already taken. Choose another.',
        ]);

        $data['is_active'] = true;
        $data['is_featured'] = false;
        $data['allow_platform_admin_edits'] = true;
        $data['language'] = ($data['language'] ?? null) ?: 'en';
        $data['initials'] = ($data['initials'] ?? null) ?: Journal::initialsFromTitle($data['title']);
        $data['description'] = SafeHtml::clean($data['description'] ?? null);

        $requireActivation = \App\Support\JournalActivation::required();
        if ($requireActivation) {
            $data['activation_status'] = \App\Support\JournalActivation::STATUS_UNPAID;
            $data['activation_paid_at'] = null;
            $data['activation_expires_at'] = null;
            $data['activation_reminders_sent'] = [];
        } else {
            $data['activation_status'] = \App\Support\JournalActivation::STATUS_ACTIVE;
            $data['activation_paid_at'] = now();
            $data['activation_expires_at'] = null;
            $data['activation_reminders_sent'] = [];
        }

        $journal = DB::transaction(function () use ($data, $request, $categories) {
            $journal = Journal::query()->create($data);
            $journal->assignTeamMember($request->user(), JournalTeamRoles::ADMIN);
            $categories->seedDefaults($journal);

            return $journal;
        });

        if (! $requireActivation) {
            return redirect()
                ->route('journal.manage.settings.edit', $journal)
                ->with('status', 'Journal created. Activation fees are currently waived by the platform — management is unlocked.');
        }

        return redirect()
            ->route('journal.manage.activation.show', $journal)
            ->with('status', 'Journal created. Pay the activation fee to list it publicly, or skip and finish setup with tools locked.');
    }
}
