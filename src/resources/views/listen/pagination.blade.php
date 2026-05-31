@if ($paginator->hasPages())
    <nav class="protocol-pagination" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <div class="protocol-pagination-status">
            @if ($paginator->firstItem())
                <span>{{ number_format($paginator->firstItem()) }}</span>
                <span>-</span>
                <span>{{ number_format($paginator->lastItem()) }}</span>
                <span>/</span>
                <span>{{ number_format($paginator->total()) }}</span>
            @else
                <span>{{ number_format($paginator->count()) }}</span>
                <span>/</span>
                <span>{{ number_format($paginator->total()) }}</span>
            @endif
        </div>

        <div class="protocol-pagination-controls">
            @if ($paginator->onFirstPage())
                <span class="protocol-pagination-link is-disabled" aria-disabled="true">
                    Prev
                </span>
            @else
                <a class="protocol-pagination-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                    Prev
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="protocol-pagination-link is-ellipsis" aria-disabled="true">
                        {{ $element }}
                    </span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page === $paginator->currentPage())
                            <span class="protocol-pagination-link is-current" aria-current="page">
                                {{ $page }}
                            </span>
                        @else
                            <a class="protocol-pagination-link" href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="protocol-pagination-link" href="{{ $paginator->nextPageUrl() }}" rel="next">
                    Next
                </a>
            @else
                <span class="protocol-pagination-link is-disabled" aria-disabled="true">
                    Next
                </span>
            @endif
        </div>
    </nav>
@endif
