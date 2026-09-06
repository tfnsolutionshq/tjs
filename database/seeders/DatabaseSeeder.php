<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\ArticleAuthor;
use App\Models\EditorialBoardMember;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Models\Volume;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@tfnsolutions.us'],
            [
                'name' => 'TJS Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        $editor = User::query()->updateOrCreate(
            ['email' => 'editor@tfnsolutions.us'],
            [
                'name' => 'Journal Admin',
                'password' => Hash::make('password'),
                'role' => 'member',
            ]
        );

        $reviewer = User::query()->updateOrCreate(
            ['email' => 'reviewer@tfnsolutions.us'],
            [
                'name' => 'Ada Reviewer',
                'password' => Hash::make('password'),
                'role' => 'reviewer',
                'is_public_reviewer' => true,
                'position' => 'Senior Reviewer',
                'affiliation' => 'Turbo Flux Network Solutions',
                'bio' => 'Peer reviewer for TJS journals.',
            ]
        );

        $author = User::query()->updateOrCreate(
            ['email' => 'author@tfnsolutions.us'],
            [
                'name' => 'Chidi Author',
                'password' => Hash::make('password'),
                'role' => 'member',
            ]
        );

        MembershipPlan::query()->updateOrCreate(
            ['name' => 'Platform Annual Membership', 'scope' => 'platform'],
            [
                'journal_id' => null,
                'price_amount' => 15000,
                'currency' => 'NGN',
                'duration_days' => 365,
                'is_active' => true,
            ]
        );

        $journal = Journal::query()->updateOrCreate(
            ['slug' => 'tfn-open-research'],
            [
                'title' => 'TFN Open Research Journal',
                'subtitle' => 'Applied research across technology, systems, and society',
                'description' => "TFN Open Research Journal publishes peer-reviewed work from Turbo Flux Network Solutions and collaborating researchers.\n\nFocus areas include software systems, digital infrastructure, and applied socio-technical studies.",
                'issn' => '0000-0000',
                'publisher' => 'Turbo Flux Network Solutions',
                'default_license' => 'CC BY 4.0',
                'is_active' => true,
                'is_featured' => true,
                'theme' => [
                    'primary' => '#1b4f72',
                    'accent' => '#148f77',
                    'nav_bg' => '#0f2f44',
                    'nav_text' => '#ffffff',
                    'header_bg' => '#1b4f72',
                    'header_text' => '#ffffff',
                    'page_bg' => '#f7f8fa',
                    'surface' => '#ffffff',
                    'text' => '#1a2332',
                    'muted' => '#5b6b7c',
                    'header_size' => 'large',
                    'header_align' => 'left',
                    'font_style' => 'serif',
                    'show_subtitle' => true,
                    'hero_overlay' => 0.4,
                ],
            ]
        );

        app(\App\Services\Journal\CategoryService::class)->seedDefaults($journal);

        $journal->users()->detach([$editor->id, $reviewer->id]);
        $journal->assignTeamMember($editor, 'admin');
        $journal->assignTeamMember($reviewer, 'reviewer');

        EditorialBoardMember::query()->updateOrCreate(
            ['journal_id' => $journal->id, 'name' => 'Editor-in-Chief (Interim)'],
            [
                'role_title' => 'Editor-in-Chief',
                'affiliation' => 'Turbo Flux Network Solutions',
                'sort_order' => 1,
            ]
        );

        $volume = Volume::query()->updateOrCreate(
            [
                'journal_id' => $journal->id,
                'volume_number' => 1,
                'year' => (int) date('Y'),
            ],
            [
                'title' => 'Inaugural Volume',
                'status' => 'published',
                'issn' => $journal->issn,
            ]
        );

        $issue = Issue::query()->updateOrCreate(
            [
                'volume_id' => $volume->id,
                'issue_number' => 1,
            ],
            [
                'title' => 'Issue 1',
                'status' => 'published',
                'period_start' => now()->startOfYear()->toDateString(),
                'period_end' => now()->toDateString(),
            ]
        );

        $article = Article::query()->updateOrCreate(
            [
                'journal_id' => $journal->id,
                'slug' => 'welcome-to-tjs',
            ],
            [
                'issue_id' => $issue->id,
                'author_user_id' => $author->id,
                'title' => 'Welcome to the TFN Journal System',
                'abstract' => 'This inaugural article introduces the TFN Journal System (TJS): a multi-journal platform with private galley storage, access-controlled full text, DOI-ready scholarly metadata, and APA citation support.',
                'category' => 'Editorial',
                'keywords' => 'TJS, open access, scholarly publishing',
                'doi' => '10.0000/tjs.2026.001',
                'license' => 'CC BY 4.0',
                'page_range' => '1-8',
                'visibility' => 'open',
                'status' => 'published',
                'published_at' => now(),
            ]
        );

        ArticleAuthor::query()->where('article_id', $article->id)->delete();
        ArticleAuthor::query()->create([
            'article_id' => $article->id,
            'name' => 'Chidi Author',
            'email' => 'author@tfnsolutions.us',
            'affiliation' => 'Turbo Flux Network Solutions',
            'is_corresponding' => true,
            'sort_order' => 0,
        ]);

        $dir = "journals/{$journal->slug}/volumes/{$volume->volume_number}/issues/{$issue->issue_number}/articles/{$article->id}";
        $path = $dir.'/manuscript.pdf';
        Storage::disk('local')->makeDirectory($dir);
        $pdf = "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n";
        Storage::disk('local')->put($path, $pdf);
        $article->update(['document_path' => $path, 'document_disk' => 'local']);

        MembershipPlan::query()->updateOrCreate(
            ['name' => $journal->title.' Membership', 'scope' => 'journal', 'journal_id' => $journal->id],
            [
                'price_amount' => 10000,
                'currency' => 'NGN',
                'duration_days' => 365,
                'is_active' => true,
            ]
        );

        unset($admin); // silence unused in some linters
    }
}
