@props([
    'paginator' => null,
    'layout' => 'grid',
    'showLayoutToggle' => true,
])

<div class="jp-list-controls">
    <div class="jp-list-controls__meta">
        @if($paginator && $paginator->total() > 0)
            <span>
                Showing {{ number_format($paginator->firstItem()) }}–{{ number_format($paginator->lastItem()) }}
                of {{ number_format($paginator->total()) }}
            </span>
        @endif
    </div>

    @if($showLayoutToggle)
        <div class="jp-layout-toggle" role="group" aria-label="Layout">
            <a
                href="{{ \App\Support\ListLayout::toggleUrl(request(), 'grid') }}"
                @class(['jp-layout-toggle__btn', 'is-active' => $layout === 'grid'])
                aria-label="Grid view"
                @if($layout === 'grid') aria-current="true" @endif
            >
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z"/>
                </svg>
                Grid
            </a>
            <a
                href="{{ \App\Support\ListLayout::toggleUrl(request(), 'list') }}"
                @class(['jp-layout-toggle__btn', 'is-active' => $layout === 'list'])
                aria-label="List view"
                @if($layout === 'list') aria-current="true" @endif
            >
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/>
                </svg>
                List
            </a>
        </div>
    @endif
</div>
