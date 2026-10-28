@if ($paginator->hasPages())
    <div class="midad-pagination" role="navigation" aria-label="ترقيم الصفحات">
        <p class="midad-pagination-summary">
            عرض {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} من {{ $paginator->total() }}
        </p>
        <ul class="midad-pagination-list">
            @if ($paginator->onFirstPage())
                <li><span class="midad-page is-disabled" aria-disabled="true">→ @lang('pagination.previous')</span></li>
            @else
                <li><a class="midad-page" href="{{ $paginator->previousPageUrl() }}" rel="prev">→ @lang('pagination.previous')</a></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="midad-page is-disabled" aria-disabled="true">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li><span class="midad-page is-current" aria-current="page">{{ $page }}</span></li>
                        @else
                            <li><a class="midad-page" href="{{ $url }}" aria-label="الانتقال إلى الصفحة {{ $page }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li><a class="midad-page" href="{{ $paginator->nextPageUrl() }}" rel="next">@lang('pagination.next') ←</a></li>
            @else
                <li><span class="midad-page is-disabled" aria-disabled="true">@lang('pagination.next') ←</span></li>
            @endif
        </ul>
    </div>
@endif
