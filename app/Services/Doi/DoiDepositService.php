<?php

namespace App\Services\Doi;

use App\Models\Article;
use App\Models\DoiDeposit;
use App\Models\Journal;
use App\Models\User;
use App\Support\Doi;
use App\Support\DoiSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class DoiDepositService
{
    public function __construct(
        private DoiCreditService $credits,
        private CrossrefDepositClient $crossref,
    ) {
    }

    /**
     * Assign a DOI (if needed) and deposit to Crossref.
     *
     * @param  ?string  $doi  Existing or desired DOI; null mints under platform/journal prefix
     */
    public function depositArticle(Article $article, ?User $actor = null, ?string $doi = null, bool $autoMint = true): DoiDeposit
    {
        if (! DoiSettings::enabled()) {
            throw new RuntimeException('DOI deposits are disabled by the platform administrator.');
        }

        $article->loadMissing(['journal', 'authors', 'issue.volume']);
        $journal = $article->journal;
        if (! $journal) {
            throw new RuntimeException('Article journal is missing.');
        }

        $mode = $journal->doi_mode ?: 'unset';
        if (! in_array($mode, ['platform', 'own'], true)) {
            throw new RuntimeException('Configure DOI mode (platform pool or own Crossref) before depositing.');
        }

        $doi = Doi::normalize($doi ?? $article->doi);
        if (! $doi && $autoMint) {
            $doi = $this->mintDoi($journal, $article);
        }
        if (! $doi) {
            throw new RuntimeException('A DOI is required to deposit. Enter one or enable minting.');
        }

        // Uniqueness against other articles
        $taken = Article::query()
            ->where('doi', $doi)
            ->where('id', '!=', $article->id)
            ->exists();
        if ($taken) {
            throw new RuntimeException('That DOI is already used by another article.');
        }

        return DB::transaction(function () use ($article, $journal, $actor, $doi, $mode) {
            $spent = 0;
            if ($mode === 'platform') {
                $this->credits->consume($journal->fresh(), 1);
                $spent = 1;
            }

            $deposit = DoiDeposit::query()->create([
                'journal_id' => $journal->id,
                'article_id' => $article->id,
                'doi' => $doi,
                'mode' => $mode,
                'status' => 'pending',
                'credits_spent' => $spent,
                'deposited_by' => $actor?->id,
            ]);

            try {
                $credentials = $this->crossref->credentialsForJournal($journal, $mode);
                $xml = $this->crossref->buildArticleXml($article, $journal, $doi, $credentials);
                $result = $this->crossref->deposit($xml, $credentials);

                if (! $result['ok']) {
                    throw new RuntimeException('Crossref rejected the deposit: '.Str::limit(strip_tags($result['body']), 240));
                }

                $deposit->update([
                    'status' => 'success',
                    'provider_payload' => $result,
                    'deposited_at' => now(),
                    'error_message' => null,
                ]);

                $article->update([
                    'doi' => $doi,
                    'doi_deposit_status' => 'deposited',
                    'doi_deposited_at' => now(),
                ]);

                return $deposit->fresh();
            } catch (\Throwable $e) {
                if ($spent > 0) {
                    $this->credits->refund($journal->fresh(), $spent);
                }

                $deposit->update([
                    'status' => 'failed',
                    'credits_spent' => 0,
                    'error_message' => $e->getMessage(),
                    'provider_payload' => ['error' => $e->getMessage()],
                ]);

                $article->update([
                    'doi_deposit_status' => 'failed',
                ]);

                throw $e;
            }
        });
    }

    public function mintDoi(Journal $journal, Article $article): string
    {
        if ($journal->doi_mode === 'own') {
            $prefix = rtrim((string) ($journal->doi_prefix ?: ''), '/');
            if ($prefix === '') {
                throw new RuntimeException('Set your Crossref DOI prefix before minting.');
            }
        } else {
            $prefix = DoiSettings::platformPrefix();
            $journalSuffix = $journal->slug ?: ('j'.$journal->id);
            $prefix = $prefix.'/'.$journalSuffix;
        }

        $suffix = Str::lower(Str::substr(str_replace('-', '', (string) $article->id), 0, 10));
        if ($suffix === '') {
            $suffix = Str::lower(Str::random(8));
        }

        $candidate = $prefix.'.'.$suffix;
        $n = 0;
        while (Article::query()->where('doi', $candidate)->where('id', '!=', $article->id)->exists()) {
            $n++;
            $candidate = $prefix.'.'.$suffix.$n;
        }

        return $candidate;
    }

    public function maybeAutoDeposit(Article $article, ?User $actor = null): ?DoiDeposit
    {
        $article->loadMissing('journal');
        $journal = $article->journal;
        if (! $journal || ! $journal->doi_auto_deposit) {
            return null;
        }
        if (! in_array($journal->doi_mode, ['platform', 'own'], true)) {
            return null;
        }
        if ($article->doi_deposit_status === 'deposited') {
            return null;
        }
        if ($article->status !== 'published') {
            return null;
        }

        try {
            return $this->depositArticle($article, $actor, $article->doi, autoMint: true);
        } catch (\Throwable) {
            return null;
        }
    }
}
