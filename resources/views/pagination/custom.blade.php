@if ($paginator->hasPages())
    <nav aria-label="Page navigation">
        <ul class="pagination">
            {{-- Previous Page Link --}}
            @dd($paginator);
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link">&laquo;</span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">&laquo;</a>
                </li>
            @endif

            {{-- Custom Pagination Links --}}
            @foreach ($paginator->getCustomLinks() as $link)
                @php
                    $isCurrent = $paginator->currentPage() == $link['pageText'];
                @endphp
                <li class="page-item {{ $isCurrent ? 'active' : '' }}" aria-current="{{ $isCurrent ? 'page' : '' }}">
                    <a class="page-link" href="{{ $link['url'] }}">{{ $link['pageText'] }}</a>
                </li>
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">&raquo;</a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link">&raquo;</span>
                </li>
            @endif
        </ul>
    </nav>
@endif