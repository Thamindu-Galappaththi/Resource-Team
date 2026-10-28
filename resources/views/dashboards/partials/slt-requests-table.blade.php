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
                        <th class="slt-table__action">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($requests as $item)
                        <tr>
                            <td data-label="{{ $columns[0] }}">
                                <a class="slt-table__ref" href="{{ $item['href'] }}">{{ $item['reference'] }}</a>
                            </td>
                            <td data-label="{{ $columns[1] }}">
                                <strong>{{ $item['title'] }}</strong>
                            </td>
                            <td data-label="{{ $columns[2] }}">{{ $item['when'] ?: '—' }}</td>
                            <td data-label="{{ $columns[3] }}">{{ $item['meta'] ?: '—' }}</td>
                            <td data-label="{{ $columns[4] }}">
                                <span class="slt-status slt-status--{{ $item['tone'] }}">{{ $item['status'] }}</span>
                            </td>
                            <td class="slt-table__action" data-label="Actions">
                                <span class="slt-action-group">
                                    <a class="slt-icon-btn" href="{{ $item['href'] }}" title="View" aria-label="View {{ $item['reference'] }}">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                    @if(!empty($item['edit_href']))
                                        <a class="slt-icon-btn" href="{{ $item['edit_href'] }}" title="Edit" aria-label="Edit {{ $item['reference'] }}">
                                            <i class="ti ti-pencil"></i>
                                        </a>
                                    @else
                                        <span class="slt-icon-btn is-disabled" title="Editing is not available" aria-disabled="true">
                                            <i class="ti ti-pencil"></i>
                                        </span>
                                    @endif
                                    @if(!empty($item['delete_action']))
                                        <button type="button" class="slt-icon-btn slt-icon-btn--danger" title="Delete" aria-label="Delete {{ $item['reference'] }}"
                                            data-slt-delete
                                            data-action="{{ $item['delete_action'] }}"
                                            data-method="{{ $item['delete_method'] ?? 'POST' }}"
                                            data-reason="{{ !empty($item['delete_needs_reason']) ? '1' : '0' }}"
                                            data-title="{{ $item['delete_title'] ?? 'Delete this request?' }}"
                                            data-sub="{{ $item['reference'] }} · {{ $item['title'] }}">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    @else
                                        <span class="slt-icon-btn is-disabled" title="Delete is not available" aria-disabled="true">
                                            <i class="ti ti-trash"></i>
                                        </span>
                                    @endif
                                </span>
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
