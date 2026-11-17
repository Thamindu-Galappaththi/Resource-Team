@php
    $total = $payments->total();
@endphp
<div class="rph-results" data-total="{{ $total }}">
    <div class="table-responsive">
        <table class="table rsv-table mb-0 rph-table">
            <thead>
                <tr>
                    <th>Invoice number</th>
                    <th>Reservation</th>
                    <th>Requester</th>
                    <th>Resource</th>
                    <th class="text-end">Amount (LKR)</th>
                    <th>Status</th>
                    <th>Issue date</th>
                    <th>Paid date</th>
                    <th class="text-center">View</th>
                </tr>
            </thead>
            <tbody>
            @forelse($payments as $invoice)
                @php
                    $reservation = $invoice->reservation;
                    $resourceNames = $reservation?->items->map(fn ($item) => $item->resource_name_snapshot ?: $item->resource?->name_model)->filter()->unique()->values() ?? collect();
                    $rowStatus = $invoice->status === 'paid' ? 'Paid' : ($invoice->invoice_number ? 'Unpaid' : 'Draft');
                @endphp
                <tr>
                    <td data-label="Invoice number"><strong>{{ $invoice->invoice_number ?: 'Draft' }}</strong></td>
                    <td data-label="Reservation">{{ $reservation?->reference ?? '—' }}</td>
                    <td data-label="Requester">{{ $reservation?->requester?->name ?? $reservation?->createdBy?->name ?? '—' }}</td>
                    <td data-label="Resource"><span class="rph-resource-name" title="{{ $resourceNames->implode(', ') }}">{{ $resourceNames->isNotEmpty() ? $resourceNames->implode(', ') : '—' }}</span></td>
                    <td data-label="Amount (LKR)" class="rph-money">{{ number_format((float) data_get($invoice->fee_breakdown, 'total', 0), 2) }}</td>
                    <td data-label="Status"><span @class(['rph-status-badge', 'is-paid' => $rowStatus === 'Paid', 'is-unpaid' => $rowStatus === 'Unpaid', 'is-draft' => $rowStatus === 'Draft'])>{{ $rowStatus }}</span></td>
                    <td data-label="Issue date">{{ $invoice->issued_at?->format('d M Y') ?? '—' }}</td>
                    <td data-label="Paid date">{{ $invoice->paid_at?->format('d M Y') ?? '—' }}</td>
                    <td data-label="View" class="rsv-actions rph-view-cell">
                        @if($reservation)
                            <a class="rph-view-button" href="{{ route('payments.resources.print', ['reference' => $reservation->reference]) }}" title="View invoice {{ $invoice->invoice_number }}" aria-label="View invoice {{ $invoice->invoice_number }}"><i class="ti ti-eye"></i></a>
                        @else
                            <span class="rph-view-disabled" aria-label="Invoice preview unavailable"><i class="ti ti-eye-off"></i></span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr class="rr-empty-row">
                    <td colspan="9">{{ ($filters['search'] ?? '') || ($filters['status'] ?? '') || ($filters['resource_type'] ?? '') || ($filters['date_from'] ?? '') || ($filters['date_to'] ?? '') ? 'No invoices match those filters.' : 'No invoices found.' }}</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @include('payments.partials.history-pager', ['paginator' => $payments, 'section' => 'payments'])
</div>
