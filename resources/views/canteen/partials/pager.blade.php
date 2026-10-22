@if($paginator->hasPages())
<nav class="cd-pager" aria-label="Pagination">
    <p>Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}</p>
    <ul class="pagination mb-0">
        <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
            @if($paginator->onFirstPage())
                <span class="page-link">‹</span>
            @else
                <a class="page-link" href="{{ $paginator->previousPageUrl() }}">‹</a>
            @endif
        </li>
        @for($page = 1; $page <= $paginator->lastPage(); $page++)
            <li class="page-item {{ $page === $paginator->currentPage() ? 'active' : '' }}">
                <a class="page-link" href="{{ $paginator->url($page) }}">{{ $page }}</a>
            </li>
        @endfor
        <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
            @if($paginator->hasMorePages())
                <a class="page-link" href="{{ $paginator->nextPageUrl() }}">›</a>
            @else
                <span class="page-link">›</span>
            @endif
        </li>
    </ul>
</nav>
@endif
