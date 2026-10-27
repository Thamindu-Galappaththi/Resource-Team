@php
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();
    $pages = [];
    for ($page = 1; $page <= $last; $page++) {
        if ($page === 1 || $page === $last || abs($page - $current) <= 1) {
            $pages[] = $page;
        }
    }
@endphp
@if($paginator->total())
    <div class="rsv-pager">
        <p class="small text-muted mb-0">Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ number_format($paginator->total()) }}</p>
        @if($paginator->hasPages())
            <ul class="pagination mb-0">
                <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                    @if($paginator->onFirstPage())
                        <span class="page-link">‹</span>
                    @else
                        <a class="page-link" href="{{ $paginator->previousPageUrl() }}" data-rr-history="{{ $section }}" rel="prev">‹</a>
                    @endif
                </li>
                @foreach($pages as $index => $page)
                    @if($index && $page - $pages[$index - 1] > 1)
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    @endif
                    <li class="page-item {{ $page === $current ? 'active' : '' }}">
                        @if($page === $current)
                            <span class="page-link">{{ $page }}</span>
                        @else
                            <a class="page-link" href="{{ $paginator->url($page) }}" data-rr-history="{{ $section }}">{{ $page }}</a>
                        @endif
                    </li>
                @endforeach
                <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
                    @if($paginator->hasMorePages())
                        <a class="page-link" href="{{ $paginator->nextPageUrl() }}" data-rr-history="{{ $section }}" rel="next">›</a>
                    @else
                        <span class="page-link">›</span>
                    @endif
                </li>
            </ul>
        @endif
    </div>
@endif
