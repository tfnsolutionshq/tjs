@extends('layouts.journal-manage')

@section('title', 'Manage | '.$journal->title)
@section('page_title', 'Journal dashboard')
@section('page_subtitle', $journal->title)

@section('page_actions')
    <a href="{{ route('journal.manage.articles.create', $journal) }}" class="admin-btn admin-btn-primary">New article</a>
@endsection

@section('content')
<style>
    .jmd-stats { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; margin-bottom:1rem; }
    @media (min-width:860px){ .jmd-stats{ grid-template-columns:repeat(4,minmax(0,1fr)); } }
    .jmd-stat {
        background:#fff; border:1px solid var(--line); border-radius:.95rem; padding:.9rem 1rem;
        box-shadow:0 8px 24px rgba(15,23,42,.035); text-decoration:none; color:inherit;
    }
    .jmd-stat__label{ font-size:.68rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:var(--muted); }
    .jmd-stat__value{ margin-top:.35rem; font-size:1.55rem; font-weight:800; letter-spacing:-.03em; color:var(--ink); line-height:1; }
    .jmd-grid{ display:grid; gap:1rem; }
    @media (min-width:960px){ .jmd-grid{ grid-template-columns:1fr 1fr; } }
    .jmd-card{ background:#fff; border:1px solid var(--line); border-radius:1.05rem; box-shadow:0 8px 24px rgba(15,23,42,.035); overflow:hidden; }
    .jmd-card__head{ display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:1rem 1.1rem .2rem; }
    .jmd-card__title{ margin:0; font-size:.95rem; font-weight:800; color:var(--ink); }
    .jmd-card__body{ padding:.5rem 1.1rem 1.1rem; }
    .jmd-row{ display:block; padding:.7rem 0; border-bottom:1px solid #f1f5f9; text-decoration:none; color:inherit; }
    .jmd-row:last-child{ border-bottom:0; }
    .jmd-row__title{ margin:0; font-size:.88rem; font-weight:750; color:var(--ink); }
    .jmd-row__meta{ margin:.2rem 0 0; font-size:.74rem; color:var(--muted); }
    .jmd-empty{ margin:0; padding:.85rem 0; font-size:.84rem; color:var(--muted); }
</style>

<div class="jmd-stats">
    <a class="jmd-stat" href="{{ route('journal.manage.articles.index', $journal) }}">
        <p class="jmd-stat__label">Articles</p>
        <p class="jmd-stat__value">{{ number_format($stats['articles']) }}</p>
    </a>
    <a class="jmd-stat" href="{{ route('journal.manage.articles.index', [$journal, 'status' => 'published']) }}">
        <p class="jmd-stat__label">Published</p>
        <p class="jmd-stat__value">{{ number_format($stats['published']) }}</p>
    </a>
    <a class="jmd-stat" href="{{ route('journal.manage.submissions.index', $journal) }}">
        <p class="jmd-stat__label">Open submissions</p>
        <p class="jmd-stat__value">{{ number_format($stats['pending']) }}</p>
    </a>
    <a class="jmd-stat" href="{{ route('journal.manage.membership-plans.index', $journal) }}">
        <p class="jmd-stat__label">Active plans</p>
        <p class="jmd-stat__value">{{ number_format($stats['active_plans']) }}</p>
    </a>
</div>

<div class="jmd-grid">
    <section class="jmd-card">
        <div class="jmd-card__head">
            <h2 class="jmd-card__title">Recent articles</h2>
            <a href="{{ route('journal.manage.articles.index', $journal) }}" class="admin-btn admin-btn-secondary" style="padding:.35rem .65rem;font-size:.72rem">View all</a>
        </div>
        <div class="jmd-card__body">
            @forelse($recentArticles as $article)
                <a class="jmd-row" href="{{ route('journal.manage.articles.edit', [$journal, $article]) }}">
                    <p class="jmd-row__title">{{ $article->title }}</p>
                    <p class="jmd-row__meta">{{ ucfirst($article->status) }} · {{ optional($article->updated_at)->diffForHumans() }}</p>
                </a>
            @empty
                <p class="jmd-empty">No articles yet for this journal.</p>
            @endforelse
        </div>
    </section>

    <section class="jmd-card">
        <div class="jmd-card__head">
            <h2 class="jmd-card__title">Recent submissions</h2>
            <a href="{{ route('journal.manage.submissions.index', $journal) }}" class="admin-btn admin-btn-secondary" style="padding:.35rem .65rem;font-size:.72rem">View all</a>
        </div>
        <div class="jmd-card__body">
            @forelse($recentSubmissions as $submission)
                <a class="jmd-row" href="{{ route('journal.manage.submissions.show', [$journal, $submission]) }}">
                    <p class="jmd-row__title">{{ $submission->title }}</p>
                    <p class="jmd-row__meta">{{ $submission->author?->name ?: 'Author' }} · {{ str_replace('_', ' ', $submission->status) }}</p>
                </a>
            @empty
                <p class="jmd-empty">No submissions in this journal yet.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
