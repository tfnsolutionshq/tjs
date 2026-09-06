@extends('layouts.public')

@section('title', 'Articles | '.config('tjs.name'))
@section('meta_description', 'Browse the latest open articles published across '.config('tjs.full_name').'.')
@section('canonical', route('articles.index'))
@section('seo')
    @include('seo.page-meta', ['meta' => app(\App\Services\Seo\PageMeta::class)->site(
        'Articles | '.config('tjs.name'),
        'Browse the latest open articles published across '.config('tjs.full_name').'.',
        route('articles.index')
    )])
@endsection

@section('content')
@include('public.partials.pagination-styles')

<section style="background: linear-gradient(180deg, #f7f8fa 0%, #ffffff 70%)">
    <div class="container-x py-12 sm:py-16">
        <p class="text-xs font-bold uppercase tracking-[0.18em]" style="color:var(--blue)">Reading</p>
        <h1 class="tjs-serif mt-3 text-4xl font-bold text-slate-900">Articles</h1>
        <p class="mt-3 max-w-2xl text-slate-500">Open articles from journals published on {{ config('tjs.full_name') }}.</p>

        <div class="mt-8 grid gap-5 lg:grid-cols-2">
            @forelse($articles as $article)
                @include('public.partials.article-card', [
                    'article' => $article,
                    'revealDelay' => 50 + ($loop->index * 70),
                ])
            @empty
                <div class="col-span-full rounded-2xl border border-slate-200 bg-white p-10 text-center">
                    <p class="text-lg font-semibold text-slate-800">No public articles yet</p>
                    <p class="mt-2 text-sm text-slate-500">Published articles will appear here once they are released.</p>
                </div>
            @endforelse
        </div>

        @if($articles->hasPages())
            {{ $articles->links('vendor.pagination.journal') }}
        @endif
    </div>
</section>
@endsection
