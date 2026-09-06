<?php

namespace App\Http\Controllers\JournalManage;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use App\Models\JournalFee;
use App\Support\JournalFeePurpose;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class JournalFeeController extends Controller
{
    public function index(Request $request, Journal $journal): View
    {
        $purpose = $request->get('purpose');
        $purpose = in_array($purpose, JournalFeePurpose::all(), true) ? $purpose : null;
        $active = $request->get('active');
        $active = in_array($active, ['1', '0'], true) ? $active : null;
        $q = trim((string) $request->get('q', ''));

        $fees = JournalFee::query()
            ->where('journal_id', $journal->id)
            ->when($purpose, fn ($query) => $query->where('purpose', $purpose))
            ->when($active !== null, fn ($query) => $query->where('is_active', $active === '1'))
            ->when($q !== '', fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->orderByDesc('is_active')
            ->orderBy('purpose')
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => JournalFee::query()->where('journal_id', $journal->id)->count(),
            'submission' => JournalFee::query()->where('journal_id', $journal->id)->where('purpose', JournalFeePurpose::SUBMISSION)->count(),
            'membership' => JournalFee::query()->where('journal_id', $journal->id)->where('purpose', JournalFeePurpose::MEMBERSHIP)->count(),
            'article' => JournalFee::query()->where('journal_id', $journal->id)->where('purpose', JournalFeePurpose::ARTICLE)->count(),
            'active' => JournalFee::query()->where('journal_id', $journal->id)->where('is_active', true)->count(),
        ];

        return view('journal-manage.fees.index', compact('journal', 'fees', 'stats', 'purpose', 'active', 'q'));
    }

    public function store(Request $request, Journal $journal): RedirectResponse
    {
        abort_unless($journal->userMayMutate($request->user()), 403);

        $data = $this->validated($request);

        JournalFee::query()->create([
            'journal_id' => $journal->id,
            'name' => $data['name'],
            'purpose' => $data['purpose'],
            'amount' => $data['amount'],
            'currency' => strtoupper($data['currency'] ?? 'NGN'),
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('journal.manage.fees.index', $journal)
            ->with('status', 'Fee created.');
    }

    public function update(Request $request, Journal $journal, JournalFee $fee): RedirectResponse
    {
        abort_unless($journal->userMayMutate($request->user()), 403);
        abort_unless((int) $fee->journal_id === (int) $journal->id, 404);

        $data = $this->validated($request);

        $fee->update([
            'name' => $data['name'],
            'purpose' => $data['purpose'],
            'amount' => $data['amount'],
            'currency' => strtoupper($data['currency'] ?? 'NGN'),
            'description' => $data['description'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('journal.manage.fees.index', $journal)
            ->with('status', 'Fee updated.');
    }

    public function toggle(Journal $journal, JournalFee $fee): RedirectResponse
    {
        abort_unless($journal->userMayMutate(auth()->user()), 403);
        abort_unless((int) $fee->journal_id === (int) $journal->id, 404);

        $fee->update(['is_active' => ! $fee->is_active]);

        return redirect()
            ->route('journal.manage.fees.index', $journal)
            ->with('status', $fee->is_active ? 'Fee activated.' : 'Fee deactivated.');
    }

    public function destroy(Journal $journal, JournalFee $fee): RedirectResponse
    {
        abort_unless($journal->userMayMutate(auth()->user()), 403);
        abort_unless((int) $fee->journal_id === (int) $journal->id, 404);

        if ($fee->membershipPlans()->exists() || $fee->submissions()->exists() || $fee->articles()->exists()) {
            return redirect()
                ->route('journal.manage.fees.index', $journal)
                ->with('error', 'Cannot delete a fee that is linked to plans, submissions, or articles. Deactivate it instead.');
        }

        $fee->delete();

        return redirect()
            ->route('journal.manage.fees.index', $journal)
            ->with('status', 'Fee deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'purpose' => ['required', Rule::in(JournalFeePurpose::all())],
            'amount' => ['required', 'integer', 'min:0'],
            'currency' => ['nullable', 'string', 'max:8'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
