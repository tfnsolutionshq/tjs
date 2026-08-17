@extends('layouts.journal-manage')

@section('title', 'Announcements | '.$journal->title)
@section('page_title', 'Announcements')
@section('page_subtitle', 'News and calls for submissions')

@section('page_actions')
    <a href="{{ route('journals.announcements', $journal) }}" class="admin-chip admin-topbar__public" target="_blank" rel="noopener">
        Public page
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H18v4.5M18 6l-7 7M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4"/></svg>
    </a>
    <a href="{{ route('journal.manage.announcements.create', $journal) }}" class="admin-btn admin-btn-primary">New announcement</a>
@endsection

@section('content')
<style>
    .ja-stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .75rem;
        margin-bottom: 1rem;
    }
    @media (max-width: 640px) {
        .ja-stats { grid-template-columns: 1fr; }
    }
    .ja-stat {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: .95rem;
        padding: .9rem 1rem;
        box-shadow: 0 8px 24px rgba(15,23,42,.035);
    }
    .ja-stat__label {
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--muted);
    }
    .ja-stat__value {
        margin-top: .35rem;
        font-size: 1.55rem;
        font-weight: 800;
        letter-spacing: -.03em;
        color: var(--ink);
        line-height: 1;
    }
    .ja-stat__hint {
        margin-top: .35rem;
        font-size: .72rem;
        color: var(--muted);
        line-height: 1.35;
    }
    .ja-tip {
        display: flex;
        gap: .85rem;
        align-items: flex-start;
        margin-bottom: 1rem;
        padding: .95rem 1.05rem;
        border-radius: .95rem;
        border: 1px solid #dbeafe;
        background: linear-gradient(135deg, #f8fbff 0%, #eff6ff 100%);
    }
    .ja-tip__icon {
        flex-shrink: 0;
        width: 2.25rem;
        height: 2.25rem;
        border-radius: .7rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #dbeafe;
        color: #2563eb;
    }
    .ja-tip__icon svg { width: 1.05rem; height: 1.05rem; }
    .ja-tip__title {
        margin: 0;
        font-size: .84rem;
        font-weight: 800;
        color: var(--ink);
    }
    .ja-tip__text {
        margin: .3rem 0 0;
        font-size: .78rem;
        line-height: 1.5;
        color: #475569;
    }
    .ja-tip__types {
        display: flex;
        flex-wrap: wrap;
        gap: .45rem;
        margin-top: .65rem;
    }
    .ja-tip__type {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .28rem .55rem;
        border-radius: 999px;
        background: rgba(255,255,255,.85);
        border: 1px solid #bfdbfe;
        font-size: .7rem;
        font-weight: 700;
        color: #1e40af;
    }
    .ja-item {
        display: flex;
        flex-wrap: wrap;
        gap: .85rem;
        align-items: flex-start;
        justify-content: space-between;
        padding: 1rem 1.15rem;
        border-top: 1px solid var(--line);
        transition: background .15s ease;
    }
    .ja-item:first-child { border-top: 0; }
    .ja-item:hover { background: #f8fafc; }
    .ja-item__main {
        display: flex;
        gap: .85rem;
        align-items: flex-start;
        min-width: 0;
        flex: 1;
    }
    .ja-item__icon {
        flex-shrink: 0;
        width: 2.35rem;
        height: 2.35rem;
        border-radius: .75rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .ja-item__icon svg { width: 1.05rem; height: 1.05rem; }
    .ja-item__icon--news { background: #f1f5f9; color: #475569; }
    .ja-item__icon--call { background: #eff6ff; color: #2563eb; }
    .ja-item__body { min-width: 0; flex: 1; }
    .ja-item__title {
        margin: 0;
        font-size: .92rem;
        font-weight: 800;
        color: var(--ink);
        line-height: 1.35;
    }
    .ja-item__meta {
        margin: .25rem 0 0;
        font-size: .74rem;
        color: var(--muted);
        line-height: 1.45;
    }
    .ja-item__summary {
        margin: .55rem 0 0;
        font-size: .8rem;
        line-height: 1.45;
        color: #475569;
    }
    .ja-item__aside {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: .55rem;
        flex-shrink: 0;
    }
    .ja-badges {
        display: flex;
        flex-wrap: wrap;
        gap: .35rem;
        justify-content: flex-end;
    }
    .ja-badge {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: .22rem .55rem;
        font-size: .68rem;
        font-weight: 800;
        letter-spacing: .02em;
    }
    .ja-badge--draft { background: #f1f5f9; color: #64748b; }
    .ja-badge--published { background: #ecfdf5; color: #15803d; }
    .ja-badge--open { background: #eff6ff; color: #1d4ed8; }
    .ja-badge--scheduled { background: #fef3c7; color: #92400e; }
    .ja-badge--closed { background: #fee2e2; color: #991b1b; }
    .ja-badge--type { background: #f8fafc; color: #334155; border: 1px solid #e2e8f0; }
    .ja-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .4rem;
        justify-content: flex-end;
    }
    .ja-actions .admin-btn { padding: .42rem .72rem; font-size: .76rem; }
    .ja-pagination { margin-top: 1rem; }
</style>

<div class="ja-stats">
    <div class="ja-stat">
        <p class="ja-stat__label">Total</p>
        <p class="ja-stat__value">{{ number_format($stats['total']) }}</p>
        <p class="ja-stat__hint">All announcements in this journal</p>
    </div>
    <div class="ja-stat">
        <p class="ja-stat__label">Published</p>
        <p class="ja-stat__value">{{ number_format($stats['published']) }}</p>
        <p class="ja-stat__hint">Visible on the public site</p>
    </div>
    <div class="ja-stat">
        <p class="ja-stat__label">Open calls</p>
        <p class="ja-stat__value">{{ number_format($stats['accepting']) }}</p>
        <p class="ja-stat__hint">Currently accepting author submissions</p>
    </div>
</div>

<div class="ja-tip">
    <span class="ja-tip__icon" aria-hidden="true">
        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
    </span>
    <div>
        <p class="ja-tip__title">How announcements work</p>
        <p class="ja-tip__text">
            Publish <strong>news</strong> for readers, or open a <strong>call for submissions</strong> tied to a specific issue.
            Authors can only submit while a call is open — closing it stops new manuscripts for that issue.
        </p>
        <div class="ja-tip__types">
            <span class="ja-tip__type">News — general updates</span>
            <span class="ja-tip__type">Call for submissions — author intake</span>
        </div>
    </div>
</div>

<section class="admin-panel">
    <div class="admin-panel__head">
        <div>
            <h2 style="margin:0;font-size:.95rem;font-weight:800;color:var(--ink)">All announcements</h2>
            <p style="margin:.25rem 0 0;font-size:.76rem;color:var(--muted)">Manage drafts, published news, and submission calls.</p>
        </div>
        @if($announcements->isNotEmpty())
            <a href="{{ route('journal.manage.announcements.create', $journal) }}" class="admin-btn admin-btn-secondary" style="padding:.42rem .72rem;font-size:.76rem">Add new</a>
        @endif
    </div>

    @forelse($announcements as $announcement)
        @php
            $status = $announcement->submissionStatusLabel();
            $statusClass = match ($status) {
                'Open' => 'ja-badge--open',
                'Scheduled' => 'ja-badge--scheduled',
                'Closed' => 'ja-badge--closed',
                'Published' => 'ja-badge--published',
                default => 'ja-badge--draft',
            };
            $isCall = $announcement->isCallForSubmissions();
        @endphp
        <article class="ja-item">
            <div class="ja-item__main">
                <span @class(['ja-item__icon', 'ja-item__icon--call' => $isCall, 'ja-item__icon--news' => ! $isCall]) aria-hidden="true">
                    @if($isCall)
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                    @else
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6v-3z"/></svg>
                    @endif
                </span>
                <div class="ja-item__body">
                    <h3 class="ja-item__title">{{ $announcement->title }}</h3>
                    <p class="ja-item__meta">
                        {{ \App\Support\AnnouncementType::label($announcement->type) }}
                        @if($announcement->issueLabel())
                            · {{ $announcement->issueLabel() }}
                        @endif
                        · {{ $announcement->submissions_count }} submission{{ $announcement->submissions_count === 1 ? '' : 's' }}
                        @if($announcement->creator)
                            · by {{ $announcement->creator->name }}
                        @endif
                        @if($isCall && $announcement->closes_at)
                            · closes {{ $announcement->closes_at->format('M j, Y g:i A') }}
                        @elseif($announcement->updated_at)
                            · updated {{ $announcement->updated_at->diffForHumans() }}
                        @endif
                    </p>
                    @if($announcement->summary)
                        <p class="ja-item__summary">{{ $announcement->summary }}</p>
                    @endif
                </div>
            </div>
            <div class="ja-item__aside">
                <div class="ja-badges">
                    <span class="ja-badge ja-badge--type">{{ \App\Support\AnnouncementType::label($announcement->type) }}</span>
                    @if($announcement->is_published)
                        <span @class(['ja-badge', $statusClass])>{{ $status }}</span>
                    @else
                        <span class="ja-badge ja-badge--draft">Draft</span>
                    @endif
                    @if($isCall && $announcement->acceptsSubmissions())
                        <span class="ja-badge ja-badge--open">Accepting</span>
                    @endif
                </div>
                <div class="ja-actions">
                    <a href="{{ route('journal.manage.announcements.edit', [$journal, $announcement]) }}" class="admin-btn admin-btn-secondary">Edit</a>
                    @if($isCall && $announcement->acceptsSubmissions())
                        <form method="POST" action="{{ route('journal.manage.announcements.close', [$journal, $announcement]) }}" onsubmit="return confirm('Close this call for submissions now?')">
                            @csrf
                            <button type="submit" class="admin-btn admin-btn-secondary">Close call</button>
                        </form>
                    @endif
                    @if($announcement->submissions_count === 0)
                        <form method="POST" action="{{ route('journal.manage.announcements.destroy', [$journal, $announcement]) }}" onsubmit="return confirm('Delete this announcement?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="admin-btn admin-btn-secondary" style="color:#b91c1c">Delete</button>
                        </form>
                    @endif
                </div>
            </div>
        </article>
    @empty
        <div class="admin-empty">
            <div class="admin-empty__icon">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5v9M7.5 12h9M6.5 4.5h11A2 2 0 0119.5 6.5v11a2 2 0 01-2 2h-11a2 2 0 01-2-2v-11a2 2 0 012-2z"/></svg>
            </div>
            <p class="admin-empty__title">No announcements yet</p>
            <p class="admin-empty__text">Create news for readers or open a call for submissions so authors can submit to a specific issue.</p>
            <a href="{{ route('journal.manage.announcements.create', $journal) }}" class="admin-btn admin-btn-primary" style="margin-top:1rem">Create announcement</a>
        </div>
    @endforelse

    @if($announcements->hasPages())
        <div class="admin-panel__foot ja-pagination">
            {{ $announcements->links() }}
        </div>
    @endif
</section>
@endsection
