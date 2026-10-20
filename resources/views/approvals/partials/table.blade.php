@php
    $rows = $rows ?? [];
    $statusLabel = $statusLabel ?? 'Status';
    $emptyTitle = $emptyTitle ?? 'No records found.';
    $emptyCopy = $emptyCopy ?? 'Try a different search or clear the filters.';
    $emptyIcon = $emptyIcon ?? 'ti-calendar-off';
    $showArchive = $showArchive ?? ($canDecide ?? false);
@endphp
@if(! count($rows))
    <div class="rsv-empty">
        <i class="ti {{ $emptyIcon }} d-block mb-2"></i>
        <p class="mb-0 fw-semibold">{{ $emptyTitle }}</p>
        <p class="small mb-0">{{ $emptyCopy }}</p>
    </div>
@else
    <div class="table-responsive">
        <table class="table rsv-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Reservation ID</th>
                    <th>Purpose</th>
                    <th>Resource</th>
                    <th class="rsv-hide-md">Requester</th>
                    <th>Schedule</th>
                    <th>{{ $statusLabel }}</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        <td class="rsv-ref" data-label="Reservation ID">{{ $row['reference'] }}</td>
                        <td class="rsv-purpose" data-label="Purpose" title="{{ $row['title'] }}">{{ \Illuminate\Support\Str::limit($row['title'] ?? '', 40) }}</td>
                        <td class="rsv-resource" data-label="Resource">{{ $row['resources'] }}</td>
                        <td class="rsv-hide-md" data-label="Requester">{{ $row['requester'] }}</td>
                        <td class="text-nowrap" data-label="Schedule"><div>{{ $row['short_date'] }}</div><div class="small text-muted">{{ $row['time'] }}</div></td>
                        <td data-label="{{ $statusLabel }}"><span class="badge {{ $row['status_badge'] }}">{{ $row['status_label'] }}</span></td>
                        <td class="text-end text-nowrap rsv-actions" data-label="Actions">
                            <span class="rsv-action-group">
                                <button type="button" class="btn btn-sm btn-outline-secondary rsv-icon-btn" data-ap-view="{{ $row['id'] }}" title="View" aria-label="View {{ $row['reference'] }}"><i class="ti ti-eye"></i></button>
                                @if($showArchive)
                                    <button type="button" class="btn btn-sm btn-outline-danger rsv-icon-btn" data-ap-archive="{{ $row['id'] }}" title="Remove" aria-label="Remove {{ $row['reference'] }} from approvals"><i class="ti ti-trash"></i></button>
                                @endif
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @include('approvals.partials.pager', ['pager' => $pager])
@endif
