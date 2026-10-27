<div id="slt-results" class="slt-results">
    @if($requests->isEmpty())
        <div class="slt-empty">
            <i class="ti ti-inbox"></i>
            <p>You have no requests here yet.</p>
        </div>
    @else
        <div class="slt-table-wrap">
            <table class="slt-table">
                <thead>
                    <tr>
                        @foreach($columns as $column)
                            <th>{{ $column }}</th>
                        @endforeach
                        <th class="slt-table__action"><span class="visually-hidden">Open</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requests as $item)
                        <tr>
                            <td>
                                <a class="slt-table__ref" href="{{ $item['href'] }}">{{ $item['reference'] }}</a>
                            </td>
                            <td>
                                <strong>{{ $item['title'] }}</strong>
                            </td>
                            <td>{{ $item['when'] ?: '—' }}</td>
                            <td>{{ $item['meta'] ?: '—' }}</td>
                            <td>
                                <span class="slt-status slt-status--{{ $item['tone'] }}">{{ $item['status'] }}</span>
                            </td>
                            <td class="slt-table__action">
                                <a href="{{ $item['href'] }}" aria-label="Open request">
                                    <i class="ti ti-chevron-right"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($requests->hasPages())
            <nav class="slt-pager" aria-label="Pagination">
                <p>Showing {{ $requests->firstItem() }}–{{ $requests->lastItem() }} of {{ $requests->total() }}</p>
                <ul class="pagination mb-0">
                    <li class="page-item {{ $requests->onFirstPage() ? 'disabled' : '' }}">
                        @if($requests->onFirstPage())
                            <span class="page-link">‹</span>
                        @else
                            <a class="page-link" href="{{ $requests->previousPageUrl() }}">‹</a>
                        @endif
                    </li>
                    @for($page = 1; $page <= $requests->lastPage(); $page++)
                        <li class="page-item {{ $page === $requests->currentPage() ? 'active' : '' }}">
                            <a class="page-link" href="{{ $requests->url($page) }}">{{ $page }}</a>
                        </li>
                    @endfor
                    <li class="page-item {{ $requests->hasMorePages() ? '' : 'disabled' }}">
                        @if($requests->hasMorePages())
                            <a class="page-link" href="{{ $requests->nextPageUrl() }}">›</a>
                        @else
                            <span class="page-link">›</span>
                        @endif
                    </li>
                </ul>
            </nav>
        @endif
    @endif
</div>
