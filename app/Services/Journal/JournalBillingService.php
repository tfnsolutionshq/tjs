<?php

namespace App\Services\Journal;

use App\Models\Article;
use App\Models\DoiCreditTopup;
use App\Models\Journal;
use App\Models\MembershipPlan;
use App\Models\PaymentTransaction;
use App\Models\Submission;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class JournalBillingService
{
    public const TYPE_ACTIVATION = 'activation';

    public const TYPE_ARTICLE = 'article_sale';

    public const TYPE_MEMBERSHIP = 'membership';

    public const TYPE_SUBMISSION = 'submission_fee';

    /**
     * @return array{
     *     income: int,
     *     spent: int,
     *     pending: int,
     *     success_count: int,
     *     currency: string,
     *     income_articles: int,
     *     income_memberships: int,
     *     income_submissions: int,
     *     spent_activation: int,
     *     spent_doi: int,
     *     platform_charges: int
     * }
     */
    public function summary(Journal $journal): array
    {
        $rows = $this->baseQuery($journal)->with(['user:id,name,email', 'payable'])->get();
        $enriched = $rows->map(fn (PaymentTransaction $tx) => $this->enrich($tx, $journal));

        $currency = (string) ($enriched->firstWhere('status', 'success')['currency']
            ?? $enriched->first()['currency']
            ?? config('tjs.currency', 'NGN'));

        $spentActivation = (int) $enriched
            ->where('status', 'success')
            ->where('type', self::TYPE_ACTIVATION)
            ->sum('amount');

        $spentDoi = (int) DoiCreditTopup::query()
            ->where('journal_id', $journal->id)
            ->sum('amount_ngn');

        return [
            'income' => (int) $enriched
                ->where('status', 'success')
                ->where('direction', 'in')
                ->sum('amount'),
            'spent' => (int) $enriched
                ->where('status', 'success')
                ->where('direction', 'out')
                ->sum('amount'),
            'pending' => (int) $enriched
                ->where('status', 'pending')
                ->sum('amount'),
            'success_count' => $enriched->where('status', 'success')->count(),
            'currency' => strtoupper($currency),
            'income_articles' => (int) $enriched
                ->where('status', 'success')
                ->where('type', self::TYPE_ARTICLE)
                ->sum('amount'),
            'income_memberships' => (int) $enriched
                ->where('status', 'success')
                ->where('type', self::TYPE_MEMBERSHIP)
                ->sum('amount'),
            'income_submissions' => (int) $enriched
                ->where('status', 'success')
                ->where('type', self::TYPE_SUBMISSION)
                ->sum('amount'),
            'spent_activation' => $spentActivation,
            'spent_doi' => $spentDoi,
            'platform_charges' => $spentActivation + $spentDoi,
        ];
    }

    public function paginate(Journal $journal, Request $request): LengthAwarePaginator
    {
        $query = $this->filteredQuery($journal, $request)->with(['user:id,name,email', 'payable']);

        $paginator = $query->paginate(20)->withQueryString();

        $paginator->setCollection(
            $paginator->getCollection()->map(fn (PaymentTransaction $tx) => $this->enrich($tx, $journal))
        );

        return $paginator;
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function exportRows(Journal $journal, Request $request): Collection
    {
        return $this->filteredQuery($journal, $request)
            ->with(['user:id,name,email', 'payable'])
            ->limit(5000)
            ->get()
            ->map(fn (PaymentTransaction $tx) => $this->enrich($tx, $journal));
    }

    public function filteredQuery(Journal $journal, Request $request): Builder
    {
        $query = $this->baseQuery($journal);

        $status = $request->string('status')->toString();
        if (in_array($status, ['pending', 'success', 'failed'], true)) {
            $query->where('status', $status);
        }

        $type = $request->string('type')->toString();
        if (in_array($type, [self::TYPE_ACTIVATION, self::TYPE_ARTICLE, self::TYPE_MEMBERSHIP, self::TYPE_SUBMISSION], true)) {
            $query->where(function (Builder $q) use ($journal, $type) {
                if ($type === self::TYPE_ACTIVATION) {
                    $q->where('payable_type', Journal::class)
                        ->where('payable_id', (string) $journal->id);
                } elseif ($type === self::TYPE_ARTICLE) {
                    $articleIds = Article::query()->where('journal_id', $journal->id)->pluck('id')->map(fn ($id) => (string) $id);
                    $q->where('payable_type', Article::class)->whereIn('payable_id', $articleIds);
                } elseif ($type === self::TYPE_SUBMISSION) {
                    $submissionIds = Submission::query()->where('journal_id', $journal->id)->pluck('id')->map(fn ($id) => (string) $id);
                    $q->where('payable_type', Submission::class)->whereIn('payable_id', $submissionIds);
                } else {
                    $planIds = MembershipPlan::query()
                        ->where('journal_id', $journal->id)
                        ->where('scope', 'journal')
                        ->pluck('id')
                        ->map(fn ($id) => (string) $id);
                    $q->where('payable_type', MembershipPlan::class)->whereIn('payable_id', $planIds);
                }
            });
        }

        $direction = $request->string('direction')->toString();
        if (in_array($direction, ['in', 'out'], true)) {
            if ($direction === 'out') {
                $query->where('payable_type', Journal::class)->where('payable_id', (string) $journal->id);
            } else {
                $query->where(function (Builder $q) use ($journal) {
                    $articleIds = Article::query()->where('journal_id', $journal->id)->pluck('id')->map(fn ($id) => (string) $id);
                    $planIds = MembershipPlan::query()
                        ->where('journal_id', $journal->id)
                        ->where('scope', 'journal')
                        ->pluck('id')
                        ->map(fn ($id) => (string) $id);
                    $submissionIds = Submission::query()->where('journal_id', $journal->id)->pluck('id')->map(fn ($id) => (string) $id);
                    $q->where(function (Builder $inner) use ($articleIds) {
                        $inner->where('payable_type', Article::class)->whereIn('payable_id', $articleIds);
                    })->orWhere(function (Builder $inner) use ($planIds) {
                        $inner->where('payable_type', MembershipPlan::class)->whereIn('payable_id', $planIds);
                    })->orWhere(function (Builder $inner) use ($submissionIds) {
                        $inner->where('payable_type', Submission::class)->whereIn('payable_id', $submissionIds);
                    });
                });
            }
        }

        if ($from = $request->date('from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->date('to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        $q = trim($request->string('q')->toString());
        if ($q !== '') {
            $like = '%'.$q.'%';
            $query->where(function (Builder $builder) use ($like) {
                $builder->where('reference', 'like', $like)
                    ->orWhere('status', 'like', $like)
                    ->orWhereHas('user', function (Builder $user) use ($like) {
                        $user->where('name', 'like', $like)->orWhere('email', 'like', $like);
                    });
            });
        }

        return $query;
    }

    public function baseQuery(Journal $journal): Builder
    {
        $articleIds = Article::query()->where('journal_id', $journal->id)->pluck('id')->map(fn ($id) => (string) $id);
        $planIds = MembershipPlan::query()
            ->where('journal_id', $journal->id)
            ->where('scope', 'journal')
            ->pluck('id')
            ->map(fn ($id) => (string) $id);
        $submissionIds = Submission::query()->where('journal_id', $journal->id)->pluck('id')->map(fn ($id) => (string) $id);

        return PaymentTransaction::query()
            ->where(function (Builder $q) use ($journal, $articleIds, $planIds, $submissionIds) {
                $q->where(function (Builder $inner) use ($journal) {
                    $inner->where('payable_type', Journal::class)
                        ->where('payable_id', (string) $journal->id);
                });

                if ($articleIds->isNotEmpty()) {
                    $q->orWhere(function (Builder $inner) use ($articleIds) {
                        $inner->where('payable_type', Article::class)
                            ->whereIn('payable_id', $articleIds);
                    });
                }

                if ($planIds->isNotEmpty()) {
                    $q->orWhere(function (Builder $inner) use ($planIds) {
                        $inner->where('payable_type', MembershipPlan::class)
                            ->whereIn('payable_id', $planIds);
                    });
                }

                if ($submissionIds->isNotEmpty()) {
                    $q->orWhere(function (Builder $inner) use ($submissionIds) {
                        $inner->where('payable_type', Submission::class)
                            ->whereIn('payable_id', $submissionIds);
                    });
                }
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /**
     * @return array<string, mixed>
     */
    public function enrich(PaymentTransaction $tx, Journal $journal): array
    {
        $type = self::TYPE_ACTIVATION;
        $direction = 'out';
        $label = 'Journal activation';
        $detail = $journal->title;

        if ($tx->payable_type === Article::class) {
            $type = self::TYPE_ARTICLE;
            $direction = 'in';
            $label = 'Article sale';
            $article = $tx->relationLoaded('payable') ? $tx->payable : Article::query()->find($tx->payable_id);
            $detail = ($article instanceof Article ? $article->title : null) ?: 'Article #'.$tx->payable_id;
        } elseif ($tx->payable_type === MembershipPlan::class) {
            $type = self::TYPE_MEMBERSHIP;
            $direction = 'in';
            $label = 'Membership plan';
            $plan = $tx->relationLoaded('payable') ? $tx->payable : MembershipPlan::query()->find($tx->payable_id);
            $detail = ($plan instanceof MembershipPlan ? $plan->name : null) ?: 'Plan #'.$tx->payable_id;
        } elseif ($tx->payable_type === Submission::class) {
            $type = self::TYPE_SUBMISSION;
            $direction = 'in';
            $label = 'Submission fee';
            $submission = $tx->relationLoaded('payable') ? $tx->payable : Submission::query()->find($tx->payable_id);
            $detail = ($submission instanceof Submission ? $submission->title : null) ?: 'Submission #'.$tx->payable_id;
        }

        return [
            'id' => $tx->id,
            'reference' => $tx->reference,
            'amount' => (int) $tx->amount,
            'currency' => strtoupper((string) $tx->currency),
            'status' => $tx->status,
            'provider' => $tx->provider,
            'type' => $type,
            'type_label' => $label,
            'direction' => $direction,
            'detail' => $detail,
            'payer_name' => $tx->user?->name,
            'payer_email' => $tx->user?->email,
            'paid_at' => $tx->paid_at,
            'created_at' => $tx->created_at,
        ];
    }
}
