@extends('layouts.journal')

@section('title', $article->title.' | '.$journal->title)
@section('meta_description', $meta['description'] ?? '')
@section('canonical', $meta['canonical'] ?? url()->current())

@section('seo')
    @include('seo.article-meta', ['article' => $article, 'journal' => $journal, 'meta' => $meta])
@endsection

@section('hero')
<section class="j-hero j-hero--compact">
    @if($journal->headerImageUrl())
        <div class="j-hero__media" style="background-image: url('{{ $journal->headerImageUrl() }}')"></div>
        <div class="j-hero__shade"></div>
    @endif
    <div class="j-hero__inner">
        <div class="j-hero__brand">
            @if($journal->logoUrl())
                <img src="{{ $journal->logoUrl() }}" alt="" class="j-hero__logo">
            @endif
            <div>
                <p class="j-hero__eyebrow text-xs font-semibold uppercase tracking-[0.18em] opacity-80">Article</p>
                <h1 class="j-display mt-1 text-2xl font-semibold leading-tight sm:text-3xl">{{ $journal->title }}</h1>
                @if($journal->subtitle)
                    <p class="j-hero__sub mt-1 max-w-2xl text-sm opacity-90">{{ $journal->subtitle }}</p>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection

@section('content')
@php
    $membershipPlan = null;
    if (! $canAccess && auth()->check() && $article->visibility === 'members_only') {
        $membershipPlan = \App\Models\MembershipPlan::query()
            ->where('is_active', true)
            ->where(function ($q) use ($journal) {
                $q->where('scope', 'platform')
                    ->orWhere(function ($q2) use ($journal) {
                        $q2->where('scope', 'journal')->where('journal_id', $journal->id);
                    });
            })
            ->orderBy('price_amount')
            ->first();
    }
    $parts = $apa['parts'] ?? [];
    $priceLabel = null;
    if ($article->visibility === 'paid' && $article->price_amount) {
        $currency = strtoupper($article->currency ?: config('tjs.currency', 'NGN'));
        $symbol = $currency === 'NGN' ? '₦' : ($currency.' ');
        $priceLabel = $symbol.number_format((int) $article->price_amount);
    }
    $buyLoginUrl = route('login', ['redirect' => route('journals.articles.show', [$journal, $article])]);
@endphp

<style>
    .art-layout { display:grid; gap:1.25rem; max-width:72rem; margin:0 auto; padding:2rem 1rem 3rem; }
    @media (min-width: 640px) { .art-layout { padding:2.5rem 1.5rem 3.5rem; } }
    @media (min-width: 1000px) {
        .art-layout { grid-template-columns:minmax(0,1fr) 17.5rem; align-items:start; gap:1.5rem; }
        .art-side { position:sticky; top:5.5rem; }
    }
    .art-side { display:grid; gap:1rem; }
    .art-actions { display:flex; flex-wrap:wrap; gap:.55rem; }
    .art-cite-box {
        border-radius:.9rem; background:color-mix(in srgb, var(--j-page-bg) 65%, white);
        border:1px solid color-mix(in srgb, var(--j-text) 10%, transparent); padding:1rem;
    }
    .art-copy {
        display:inline-flex; align-items:center; gap:.4rem;
        border:1px solid color-mix(in srgb, var(--j-text) 12%, transparent);
        background:#fff; color:color-mix(in srgb, var(--j-text) 72%, transparent);
        border-radius:.5rem; padding:.4rem .7rem; font-size:.72rem; font-weight:650;
        cursor:pointer; transition:background .15s ease, color .15s ease, border-color .15s ease;
    }
    .art-copy:hover { background:color-mix(in srgb, var(--j-page-bg) 80%, white); }
    .art-copy.is-copied {
        color:#047857; border-color:#a7f3d0; background:#ecfdf5;
    }
    .art-copy svg { width:.9rem; height:.9rem; flex-shrink:0; }
    .art-modal {
        position:fixed; inset:0; z-index:60; display:flex; align-items:flex-end; justify-content:center;
        background:rgba(15,23,42,.4); padding:1rem;
    }
    @media (min-width:640px) { .art-modal { align-items:center; } }
    .art-modal__panel {
        width:100%; max-width:36rem; max-height:90vh; overflow:auto;
        background:#fff; border-radius:1.1rem; box-shadow:0 24px 60px rgba(15,23,42,.22);
    }
    .art-modal__head {
        position:sticky; top:0; background:#fff; display:flex; align-items:flex-start; justify-content:space-between;
        gap:.75rem; padding:1rem 1.15rem; border-bottom:1px solid #eaecf0; z-index:1;
    }
    .art-modal__body { display:grid; gap:1.15rem; padding:1.15rem; }
    .art-modal__close {
        border:0; background:transparent; color:#64748b; border-radius:.55rem; padding:.35rem; cursor:pointer;
    }
    .art-modal__close:hover { background:#f1f5f9; color:#0f172a; }
</style>

<div
    class="art-layout"
    x-data="{
        citeOpen: false,
        copiedKey: '',
        async copyText(text) {
            try {
                if (navigator.clipboard?.writeText) {
                    await navigator.clipboard.writeText(text);
                    return true;
                }
            } catch (e) {}
            try {
                const ta = document.createElement('textarea');
                ta.value = text;
                ta.setAttribute('readonly', '');
                ta.style.position = 'fixed';
                ta.style.left = '-9999px';
                document.body.appendChild(ta);
                ta.select();
                const ok = document.execCommand('copy');
                document.body.removeChild(ta);
                return ok;
            } catch (e) {
                return false;
            }
        },
        async copy(id, text) {
            const ok = await this.copyText(text);
            if (!ok) return;
            this.copiedKey = id;
            window.setTimeout(() => {
                if (this.copiedKey === id) this.copiedKey = '';
            }, 2000);
        },
        openCite() {
            this.citeOpen = true;
            document.body.style.overflow = 'hidden';
        },
        closeCite() {
            this.citeOpen = false;
            document.body.style.overflow = '';
        }
    }"
    @keydown.escape.window="if (citeOpen) closeCite()"
>
    <div class="min-w-0">
        <div class="flex flex-wrap gap-2">
            @foreach($article->categories as $cat)
                <span class="j-badge">{{ $cat->name }}</span>
            @endforeach
            @if($article->categories->isEmpty() && $article->category)
                <span class="j-badge">{{ $article->category }}</span>
            @endif
            <span class="j-badge">{{ str_replace('_', ' ', $article->visibility) }}</span>
            @if($article->issue)
                <a class="j-badge" href="{{ route('journals.issues.show', [$journal, $article->issue]) }}" style="text-decoration:none">{{ $article->issue->label() }}</a>
            @endif
        </div>

        <h1 class="j-display mt-4 text-3xl font-semibold leading-tight sm:text-4xl">{{ $article->title }}</h1>

        <p class="j-meta mt-4">
            {{ $article->authors->pluck('name')->join(', ') }}
            @if($article->page_range) · Pages {{ $article->page_range }} @endif
            @if($article->published_at) · {{ $article->published_at->format('F j, Y') }} @endif
        </p>

        <div class="mt-3 flex flex-wrap gap-3 text-xs" style="color: var(--j-muted)">
            @if(!empty($meta['doi']))
                <a class="j-link" href="https://doi.org/{{ $meta['doi'] }}" target="_blank" rel="noopener">doi:{{ $meta['doi'] }}</a>
            @endif
            @if(!empty($meta['license']))
                @if(!empty($meta['license_url']))
                    <a class="j-link" href="{{ $meta['license_url'] }}" target="_blank" rel="noopener license">{{ $meta['license'] }}</a>
                @else
                    <span>{{ $meta['license'] }}</span>
                @endif
            @endif
            @if($journal->issn)<span>ISSN {{ $journal->issn }}</span>@endif
        </div>

        <div class="art-actions mt-6">
            @if($canAccess)
                <a href="{{ route('journals.articles.pdf-viewer', [$journal, $article]) }}" class="j-btn">View PDF</a>
                <a href="{{ route('journals.articles.pdf', [$journal, $article->slug]) }}" class="j-btn-ghost" target="_blank" rel="noopener">Download PDF</a>
            @elseif($article->visibility === 'paid')
                @guest
                    <a href="{{ $buyLoginUrl }}" class="j-btn">Log in to buy{{ $priceLabel ? ' · '.$priceLabel : '' }}</a>
                @else
                    <form method="POST" action="{{ route('payments.articles.buy', [$journal, $article]) }}" x-data="{ submitting: false }" @submit="if (submitting) { $event.preventDefault() } else { submitting = true }">
                        @csrf
                        <button class="j-btn" type="submit" :disabled="submitting" :aria-busy="submitting">
                            <span x-text="submitting ? 'Redirecting to Paystack…' : 'Buy full text{{ $priceLabel ? ' · '.$priceLabel : '' }}'"></span>
                        </button>
                    </form>
                @endguest
            @elseif($article->visibility === 'members_only')
                @guest
                    <a href="{{ $buyLoginUrl }}" class="j-btn">Log in to access</a>
                @else
                    @if($membershipPlan)
                        <form method="POST" action="{{ route('payments.memberships.buy', $membershipPlan) }}">
                            @csrf
                            <button class="j-btn" type="submit">Become a member (₦{{ number_format($membershipPlan->price_amount) }})</button>
                        </form>
                    @endif
                @endguest
            @else
                @guest
                    <a href="{{ route('login') }}" class="j-btn">Log in to access</a>
                @endguest
            @endif
            <button type="button" class="j-btn-ghost" @click="openCite()">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h8a2 2 0 0 1 2 2v8M9 3h8a2 2 0 0 1 2 2v8M5 11h8a2 2 0 0 1 2 2v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6a2 2 0 0 1 2-2z"/></svg>
                Cite
            </button>
        </div>

        @if(! $canAccess && $article->visibility === 'paid')
            <div class="j-card mt-5 p-5" style="border-color: color-mix(in srgb, var(--j-accent) 28%, transparent); background: color-mix(in srgb, var(--j-accent) 6%, white)">
                <p class="text-xs font-bold uppercase tracking-[0.14em]" style="color: var(--j-accent)">Paid full text</p>
                <p class="mt-2 text-sm leading-relaxed" style="color: var(--j-text)">
                    Purchase unlocks View PDF and Download for your account.
                    @if($priceLabel)
                        Price: <strong>{{ $priceLabel }}</strong>
                        ({{ strtoupper($article->currency ?: 'NGN') }}).
                    @endif
                    Checkout is secured by Paystack.
                </p>
                @if($denial)
                    <p class="mt-2 text-sm text-amber-800">{{ $denial }}</p>
                @endif
            </div>
        @elseif(! $canAccess && $denial)
            <p class="mt-3 text-sm text-amber-700">{{ $denial }}</p>
        @endif

        @if($article->authors->isNotEmpty())
            <div class="mt-8 border-t pt-5" style="border-color: color-mix(in srgb, var(--j-text) 10%, transparent)">
                <h2 class="text-sm font-semibold">Authors</h2>
                <ul class="mt-2 space-y-2 text-sm" style="color: var(--j-muted)">
                    @foreach($article->authors as $author)
                        <li>
                            <span class="font-medium" style="color: var(--j-text)">{{ $author->name }}</span>
                            @if($author->is_corresponding)<span class="j-badge ml-1">Corresponding</span>@endif
                            @if($author->affiliation) — {{ $author->affiliation }} @endif
                            @if($author->orcid)
                                <a class="j-link ml-1" href="https://orcid.org/{{ preg_replace('#^https?://orcid.org/#i','',$author->orcid) }}" target="_blank" rel="noopener">ORCID</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mt-8 border-t pt-5" style="border-color: color-mix(in srgb, var(--j-text) 10%, transparent)">
            <h2 class="text-sm font-semibold">Abstract</h2>
            <p class="mt-2 whitespace-pre-line text-sm leading-relaxed">{{ $article->abstract }}</p>
        </div>

        @if($article->keywords)
            <div class="mt-6">
                <h2 class="text-sm font-semibold">Keywords</h2>
                <p class="j-meta mt-2">{{ $article->keywords }}</p>
            </div>
        @endif

        <div class="mt-8 border-t pt-5" style="border-color: color-mix(in srgb, var(--j-text) 10%, transparent)">
            <div class="mb-2 flex items-center justify-between gap-2">
                <h2 class="text-sm font-semibold">How to cite <span class="font-normal" style="color: var(--j-muted)">(APA 7th)</span></h2>
                <button
                    type="button"
                    class="art-copy"
                    :class="copiedKey === 'inline' && 'is-copied'"
                    @click="copy('inline', {{ \Illuminate\Support\Js::from($apa['reference']) }})"
                >
                    <template x-if="copiedKey !== 'inline'">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="9" y="9" width="11" height="11" rx="2"/><path stroke-linecap="round" d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
                    </template>
                    <template x-if="copiedKey === 'inline'">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </template>
                    <span x-text="copiedKey === 'inline' ? 'Copied' : 'Copy citation'"></span>
                </button>
            </div>
            <div class="art-cite-box text-sm leading-relaxed">
                @include('public.articles.partials.apa-reference', ['parts' => $parts])
            </div>
            <p class="j-meta mt-2 text-xs">In-text: {{ $apa['parenthetical'] }} · {{ $apa['narrative'] }}</p>
            <button type="button" class="j-link mt-3 text-sm" @click="openCite()">Open citation tools →</button>
        </div>
    </div>

    <aside class="art-side">
        <div class="j-card p-5">
            <h3 class="text-xs font-bold uppercase tracking-[0.16em]" style="color: var(--j-muted)">Article tools</h3>
            <div class="mt-3 flex flex-col gap-2">
                @if($canAccess)
                    <a class="j-btn justify-center text-sm" href="{{ route('journals.articles.pdf-viewer', [$journal, $article]) }}">View PDF</a>
                    <a class="j-btn-ghost justify-center text-sm" href="{{ route('journals.articles.pdf', [$journal, $article->slug]) }}" target="_blank" rel="noopener">Download PDF</a>
                @elseif($article->visibility === 'paid')
                    @guest
                        <a class="j-btn justify-center text-sm" href="{{ $buyLoginUrl }}">Log in to buy{{ $priceLabel ? ' · '.$priceLabel : '' }}</a>
                    @else
                        <form method="POST" action="{{ route('payments.articles.buy', [$journal, $article]) }}">
                            @csrf
                            <button class="j-btn w-full justify-center text-sm" type="submit">Buy full text{{ $priceLabel ? ' · '.$priceLabel : '' }}</button>
                        </form>
                    @endguest
                @endif
                <button type="button" class="j-btn-ghost justify-center text-sm" @click="openCite()">Cite this article</button>
                @if($article->issue)
                    <a class="j-btn-ghost justify-center text-sm" href="{{ route('journals.issues.show', [$journal, $article->issue]) }}">View issue</a>
                @endif
                <a class="j-btn-ghost justify-center text-sm" href="{{ route('journals.browse', $journal) }}">Browse all</a>
            </div>
        </div>
        <div class="j-card space-y-2 p-5 text-sm">
            @if($article->issue)<p><span class="font-semibold">Issue</span> · {{ $article->issue->label() }}</p>@endif
            @if($article->published_at)<p><span class="font-semibold">Published</span> · {{ $article->published_at->format('M j, Y') }}</p>@endif
            @if($article->page_range)<p><span class="font-semibold">Pages</span> · {{ $article->page_range }}</p>@endif
            <p><span class="font-semibold">Access</span> · {{ str_replace('_', ' ', $article->visibility) }}</p>
            @if($priceLabel)
                <p><span class="font-semibold">Price</span> · {{ $priceLabel }}</p>
            @endif
            @if(! empty($accessViaPurchase))
                <p style="color:#047857"><span class="font-semibold">Your access</span> · Purchased</p>
            @endif
        </div>
    </aside>

    {{-- Cite modal (PAEC-style) --}}
    <div
        class="art-modal"
        x-show="citeOpen"
        x-cloak
        x-transition.opacity.duration.150ms
        @click.self="closeCite()"
        role="dialog"
        aria-modal="true"
        aria-labelledby="cite-title"
    >
        <div class="art-modal__panel" @click.stop>
            <div class="art-modal__head">
                <div>
                    <h2 id="cite-title" class="text-lg font-bold" style="color: var(--j-text)">Cite this article</h2>
                    <p class="mt-0.5 text-xs" style="color: var(--j-muted)">APA Style (7th edition)</p>
                </div>
                <button type="button" class="art-modal__close" @click="closeCite()" aria-label="Close">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
            </div>
            <div class="art-modal__body">
                <section>
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold">Reference list</h3>
                        <button
                            type="button"
                            class="art-copy"
                            :class="copiedKey === 'reference' && 'is-copied'"
                            @click="copy('reference', {{ \Illuminate\Support\Js::from($apa['reference']) }})"
                        >
                            <template x-if="copiedKey !== 'reference'">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="9" y="9" width="11" height="11" rx="2"/><path stroke-linecap="round" d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
                            </template>
                            <template x-if="copiedKey === 'reference'">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </template>
                            <span x-text="copiedKey === 'reference' ? 'Copied' : 'Copy'"></span>
                        </button>
                    </div>
                    <div class="art-cite-box text-sm leading-relaxed">
                        @include('public.articles.partials.apa-reference', ['parts' => $parts])
                    </div>
                </section>

                <section>
                    <h3 class="mb-2 text-sm font-semibold">In-text citations</h3>
                    <div class="space-y-3">
                        <div class="art-cite-box">
                            <div class="mb-1.5 flex items-center justify-between gap-2">
                                <span class="text-[11px] font-semibold uppercase tracking-wide" style="color: var(--j-muted)">Parenthetical</span>
                                <button
                                    type="button"
                                    class="art-copy"
                                    :class="copiedKey === 'parenthetical' && 'is-copied'"
                                    @click="copy('parenthetical', {{ \Illuminate\Support\Js::from($apa['parenthetical']) }})"
                                >
                                    <template x-if="copiedKey !== 'parenthetical'">
                                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="9" y="9" width="11" height="11" rx="2"/><path stroke-linecap="round" d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
                                    </template>
                                    <template x-if="copiedKey === 'parenthetical'">
                                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </template>
                                    <span x-text="copiedKey === 'parenthetical' ? 'Copied' : 'Copy'"></span>
                                </button>
                            </div>
                            <p class="text-sm">{{ $apa['parenthetical'] }}</p>
                        </div>
                        <div class="art-cite-box">
                            <div class="mb-1.5 flex items-center justify-between gap-2">
                                <span class="text-[11px] font-semibold uppercase tracking-wide" style="color: var(--j-muted)">Narrative</span>
                                <button
                                    type="button"
                                    class="art-copy"
                                    :class="copiedKey === 'narrative' && 'is-copied'"
                                    @click="copy('narrative', {{ \Illuminate\Support\Js::from($apa['narrative']) }})"
                                >
                                    <template x-if="copiedKey !== 'narrative'">
                                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="9" y="9" width="11" height="11" rx="2"/><path stroke-linecap="round" d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
                                    </template>
                                    <template x-if="copiedKey === 'narrative'">
                                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </template>
                                    <span x-text="copiedKey === 'narrative' ? 'Copied' : 'Copy'"></span>
                                </button>
                            </div>
                            <p class="text-sm">{{ $apa['narrative'] }}</p>
                        </div>
                    </div>
                </section>

                <p class="text-[11px] leading-relaxed" style="color: var(--j-muted)">
                    Formatted using APA Style (7th edition). Journal title and volume are italicized in the reference display; copied text is plain for pasting into Word or a reference manager.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
