<nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="ops-pagination">
    <p class="ops-pagination-summary">
        <span>{{ __('Showing') }}</span>
        @if ($paginator->firstItem())
            <strong>{{ $paginator->firstItem() }}</strong>
            <span>{{ __('to') }}</span>
            <strong>{{ $paginator->lastItem() }}</strong>
        @else
            <strong>{{ $paginator->count() }}</strong>
        @endif
        <span>{{ __('of') }}</span>
        <strong>{{ $paginator->total() }}</strong>
        <span>{{ __('results') }}</span>
    </p>

    @if ($paginator->hasPages())
        <div class="ops-pagination-controls" aria-label="{{ __('Pagination pages') }}">
            @if ($paginator->onFirstPage())
                <span class="ops-pagination-button is-disabled" aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                    <span class="sr-only">{{ __('pagination.previous') }}</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="ops-pagination-button" aria-label="{{ __('pagination.previous') }}">
                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                    <span class="sr-only">{{ __('pagination.previous') }}</span>
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="ops-pagination-button is-ellipsis" aria-hidden="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="ops-pagination-button is-current" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="ops-pagination-button" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="ops-pagination-button" aria-label="{{ __('pagination.next') }}">
                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                    <span class="sr-only">{{ __('pagination.next') }}</span>
                </a>
            @else
                <span class="ops-pagination-button is-disabled" aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                    <svg aria-hidden="true" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" /></svg>
                    <span class="sr-only">{{ __('pagination.next') }}</span>
                </span>
            @endif
        </div>
    @endif
</nav>
