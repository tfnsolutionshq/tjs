@if ($paginator->hasPages())
    <nav class="jp-pagination" role="navigation" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="jp-pagination__btn is-disabled" aria-disabled="true">&larr; Prev</span>
        @else
            <a class="jp-pagination__btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">&larr; Prev</a>
        @endif

        <div class="jp-pagination__pages">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="jp-pagination__ellipsis">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="jp-pagination__page is-active" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="jp-pagination__page" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>

        @if ($paginator->hasMorePages())
            <a class="jp-pagination__btn" href="{{ $paginator->nextPageUrl() }}" rel="next">Next &rarr;</a>
        @else
            <span class="jp-pagination__btn is-disabled" aria-disabled="true">Next &rarr;</span>
        @endif
    </nav>
@endif
