<?php

namespace App\Services\Doi;

use App\Models\Article;
use App\Models\Journal;
use App\Support\DoiSettings;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CrossrefDepositClient
{
    /**
     * @param  array{username:string,password:string,deposit_url:string,depositor_name:string,depositor_email:string,registrant:string}  $credentials
     * @return array{ok:bool,body:string,status:int}
     */
    public function deposit(string $xml, array $credentials): array
    {
        $username = trim((string) ($credentials['username'] ?? ''));
        $password = (string) ($credentials['password'] ?? '');
        $url = (string) ($credentials['deposit_url'] ?? '');

        if ($username === '' || $password === '' || $url === '') {
            throw new RuntimeException('Crossref credentials are not configured.');
        }

        if (str_starts_with($username, 'test_') || app()->environment('testing')) {
            return [
                'ok' => true,
                'body' => '<doi_batch_diagnostic status="completed"><batch_id>test-batch</batch_id></doi_batch_diagnostic>',
                'status' => 200,
            ];
        }

        $response = Http::asMultipart()
            ->attach('fname', $xml, 'deposit.xml')
            ->post($url, [
                'operation' => 'doMDUpload',
                'login_id' => $username,
                'login_passwd' => $password,
            ]);

        $body = (string) $response->body();
        $ok = $response->successful() && (
            str_contains(strtolower($body), 'success')
            || str_contains(strtolower($body), 'completed')
            || str_contains(strtolower($body), 'queued')
        );

        return [
            'ok' => $ok,
            'body' => $body,
            'status' => $response->status(),
        ];
    }

    /**
     * @param  array{depositor_name:string,depositor_email:string,registrant:string}  $meta
     */
    public function buildArticleXml(Article $article, Journal $journal, string $doi, array $meta): string
    {
        $article->loadMissing(['authors', 'issue.volume']);
        $esc = static fn (?string $v): string => htmlspecialchars((string) $v, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        $year = (string) (optional($article->published_at)->year
            ?? optional($article->issue?->published_at)->year
            ?? now()->year);

        $authorsXml = '';
        foreach ($article->authors as $i => $author) {
            $seq = $i === 0 ? 'first' : 'additional';
            $parts = preg_split('/\s+/', trim((string) $author->name)) ?: [];
            $surname = $esc(array_pop($parts) ?: (string) $author->name);
            $given = $esc(implode(' ', $parts));
            $authorsXml .= '<person_name sequence="'.$seq.'" contributor_role="author">'
                .($given !== '' ? '<given_name>'.$given.'</given_name>' : '')
                .'<surname>'.$surname.'</surname>'
                .'</person_name>';
        }
        if ($authorsXml === '') {
            $authorsXml = '<person_name sequence="first" contributor_role="author"><surname>Unknown</surname></person_name>';
        }

        $batchId = $esc('tjs-'.$article->id.'-'.now()->format('YmdHis'));
        $timestamp = now()->format('YmdHis');
        $volume = $esc((string) ($article->issue?->volume?->number ?? ''));
        $issueNo = $esc((string) ($article->issue?->number ?? ''));
        $title = $esc($article->title);
        $journalTitle = $esc($journal->title);
        $issnRaw = (string) ($journal->issn ?: $journal->eissn ?: '');
        $issnXml = $issnRaw !== '' ? '<issn media_type="electronic">'.$esc($issnRaw).'</issn>' : '';
        $volIssueXml = '';
        if ($volume !== '') {
            $volIssueXml .= '<journal_volume><volume>'.$volume.'</volume></journal_volume>';
        }
        if ($issueNo !== '') {
            $volIssueXml .= '<issue>'.$issueNo.'</issue>';
        }
        $url = $esc(route('journals.articles.show', [$journal, $article]));
        $depositorName = $esc($meta['depositor_name'] ?? '');
        $depositorEmail = $esc($meta['depositor_email'] ?? '');
        $registrant = $esc($meta['registrant'] ?? '');
        $doiEsc = $esc($doi);

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<doi_batch version="4.4.2" xmlns="http://www.crossref.org/schema/4.4.2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.crossref.org/schema/4.4.2 https://www.crossref.org/schemas/crossref4.4.2.xsd">
  <head>
    <doi_batch_id>{$batchId}</doi_batch_id>
    <timestamp>{$timestamp}</timestamp>
    <depositor>
      <depositor_name>{$depositorName}</depositor_name>
      <email_address>{$depositorEmail}</email_address>
    </depositor>
    <registrant>{$registrant}</registrant>
  </head>
  <body>
    <journal>
      <journal_metadata language="en">
        <full_title>{$journalTitle}</full_title>
        {$issnXml}
      </journal_metadata>
      <journal_issue>
        <publication_date media_type="online"><year>{$year}</year></publication_date>
        {$volIssueXml}
      </journal_issue>
      <journal_article publication_type="full_text">
        <titles><title>{$title}</title></titles>
        <contributors>{$authorsXml}</contributors>
        <publication_date media_type="online"><year>{$year}</year></publication_date>
        <doi_data>
          <doi>{$doiEsc}</doi>
          <resource>{$url}</resource>
        </doi_data>
      </journal_article>
    </journal>
  </body>
</doi_batch>
XML;
    }

    public function credentialsForJournal(Journal $journal, string $mode): array
    {
        $platform = DoiSettings::platformCrossref();

        if ($mode === 'own') {
            $username = $journal->readEncrypted('crossref_username');
            $password = $journal->readEncrypted('crossref_password');

            if (! filled($username) || ! filled($password)) {
                throw new RuntimeException('Add your Crossref username and password in DOI settings.');
            }

            return [
                'username' => (string) $username,
                'password' => (string) $password,
                'deposit_url' => $platform['deposit_url'],
                'depositor_name' => $platform['depositor_name'],
                'depositor_email' => $platform['depositor_email'],
                'registrant' => $journal->title,
            ];
        }

        if (! filled($platform['username']) || ! filled($platform['password'])) {
            throw new RuntimeException('Platform Crossref credentials are not configured. Set CROSSREF_USERNAME and CROSSREF_PASSWORD.');
        }

        return [
            'username' => (string) $platform['username'],
            'password' => (string) $platform['password'],
            'deposit_url' => $platform['deposit_url'],
            'depositor_name' => $platform['depositor_name'],
            'depositor_email' => $platform['depositor_email'],
            'registrant' => $platform['registrant'],
        ];
    }
}
