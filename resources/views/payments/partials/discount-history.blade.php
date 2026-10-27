<div class="table-responsive">
    <table class="table rsv-table mb-0">
        <thead>
            <tr><th>When</th><th>Item</th><th>Action</th><th>Actor</th><th>Details</th></tr>
        </thead>
        <tbody>
        @forelse($discountChanges as $change)
            <tr>
                <td data-label="When">{{ $change['at']?->format('d M Y H:i') }}</td>
                <td data-label="Item">{{ $change['item'] }}</td>
                <td data-label="Action">{{ $change['action'] }}</td>
                <td data-label="Actor">{{ $change['actor'] }}</td>
                <td data-label="Details">@include('payments.partials.history-details')</td>
            </tr>
        @empty
            <tr class="rr-empty-row"><td colspan="5">No discount changes recorded.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@include('payments.partials.history-pager', ['paginator' => $discountAudits, 'section' => 'discount_history'])
