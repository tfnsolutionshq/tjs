@extends('layouts.journal')

@section('title', $announcement->title.' | '.$journal->title)
@section('meta_description', $announcement->summary ?: Str::limit(strip_tags($announcement->body ?? ''), 160))
@section('canonical', route('journals.announcements.show', [$journal, $announcement]))
@section('seo')
    @include('seo.page-meta', ['meta' => app(\App\Services\Seo\PageMeta::class)->announcement($journal, $announcement)])
@endsection

@section('content')
@include('public.journals.partials.page-styles')

<div class="jp">
    <div class="jp-intro">
        <div>
            <div class="flex flex-wrap gap-2" style="margin-bottom:.65rem">
                <span class="jp-tag">{{ \App\Support\AnnouncementType::label($announcement->type) }}</span>
                @if($announcement->isCallForSubmissions())
                    <span class="jp-tag">{{ $announcement->submissionStatusLabel() }}</span>
                @endif
            </div>
            <h1 class="j-section-title" style="margin:0;font-size:1.85rem">{{ $announcement->title }}</h1>
            @if($announcement->issueLabel())
                <p class="jp-lead" style="margin-top:.75rem">Target issue: <strong style="color:var(--j-text)">{{ $announcement->issueLabel() }}</strong></p>
            @endif
            @if($announcement->isCallForSubmissions())
                <p class="jp-lead" style="margin-top:.45rem">
                    @if($announcement->opens_at)
                        Opens {{ $announcement->opens_at->format('M j, Y g:i A') }}
                    @endif
                    @if($announcement->closes_at)
                        · Closes {{ $announcement->closes_at->format('M j, Y g:i A') }}
                    @endif
                </p>
            @endif
        </div>
        @if($announcement->acceptsSubmissions())
            <div style="display:flex;flex-wrap:wrap;gap:.55rem">
                @auth
                    <a href="{{ route('author.submissions.create', ['announcement' => $announcement->id]) }}" class="j-btn">Submit manuscript</a>
                @else
                    <a href="{{ route('journals.login', ['journal' => $journal, 'redirect' => url()->current()]) }}" class="j-btn">Log in to submit</a>
                @endauth
            </div>
        @endif
    </div>

    @if($announcement->body || ($announcement->isCallForSubmissions() && ($submissionFees->isNotEmpty() || $publicationFees->isNotEmpty())))
        <div class="jp-card" style="margin-top:1.25rem">
            <div class="jp-card__head" style="padding:1rem 1.15rem .35rem">
                <h2 class="j-section-title" style="margin:0;font-size:1.05rem">
                    {{ $announcement->isCallForSubmissions() ? 'Submission guidelines' : 'Details' }}
                </h2>
            </div>
            <div class="jp-card__body" style="display:grid;gap:1.15rem;padding-top:.65rem">
                @if($announcement->body)
                    <div class="tjs-prose" style="line-height:1.65;color:var(--j-text)">{!! \App\Support\SafeHtml::display($announcement->body) !!}</div>
                @endif

                @if($announcement->isCallForSubmissions() && ($submissionFees->isNotEmpty() || $publicationFees->isNotEmpty()))
                    <div style="display:grid;gap:.85rem">
                        @if($submissionFees->isNotEmpty())
                            <div>
                                <p style="margin:0 0 .45rem;font-size:.72rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:var(--j-muted,#64748b)">Submission fees</p>
                                <div style="display:grid;gap:.55rem">
                                    @foreach($submissionFees as $fee)
                                        <div style="padding:.75rem .85rem;border:1px solid var(--j-line,#e2e8f0);border-radius:.75rem;background:var(--j-surface,#f8fafc)">
                                            <p style="margin:0;font-size:.88rem;font-weight:800;color:var(--j-text)">{{ $fee['name'] }} — {{ number_format($fee['amount']) }} {{ $fee['currency'] }}</p>
                                            @if(! empty($fee['description']))
                                                <p style="margin:.3rem 0 0;font-size:.78rem;color:var(--j-muted,#64748b);line-height:1.45">{{ $fee['description'] }}</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @if($publicationFees->isNotEmpty())
                            <div>
                                <p style="margin:0 0 .45rem;font-size:.72rem;font-weight:800;letter-spacing:.06em;text-transform:uppercase;color:var(--j-muted,#64748b)">Publication fees (APC)</p>
                                <div style="display:grid;gap:.55rem">
                                    @foreach($publicationFees as $fee)
                                        <div style="padding:.75rem .85rem;border:1px solid var(--j-line,#e2e8f0);border-radius:.75rem;background:var(--j-surface,#f8fafc)">
                                            <p style="margin:0;font-size:.88rem;font-weight:800;color:var(--j-text)">{{ $fee['name'] }} — {{ number_format($fee['amount']) }} {{ $fee['currency'] }}</p>
                                            @if(! empty($fee['description']))
                                                <p style="margin:.3rem 0 0;font-size:.78rem;color:var(--j-muted,#64748b);line-height:1.45">{{ $fee['description'] }}</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif

    <p style="margin-top:1.25rem">
        <a href="{{ route('journals.announcements', $journal) }}" class="j-link">&larr; All announcements</a>
    </p>
</div>
@endsection
