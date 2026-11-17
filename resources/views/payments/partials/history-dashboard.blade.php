@php
    $formatMoney = fn ($amount) => 'LKR '.number_format((float) $amount, 2);
    $paidShare = $summary['total_amount'] > 0 ? round($summary['paid_amount'] / $summary['total_amount'] * 100, 1) : 0;
@endphp
@if($summary['total_count'] > 0)
    <section class="rph-dashboard-grid" aria-label="Payment charts">
        <article class="rph-card rph-chart-card">
            <div class="rph-card-heading"><div><h2>Paid vs unpaid</h2><p>Invoice amount by payment status.</p></div></div>
            <div class="rph-donut-layout">
                <div class="rph-donut" role="img" aria-label="{{ number_format($paidShare, 1) }} percent of invoice value collected" style="--paid-share: {{ $paidShare }}%"><div><strong>{{ number_format($paidShare, 1) }}%</strong><span>Collected</span></div></div>
                <div class="rph-legend">
                    <div><span class="rph-legend-dot paid"></span><div><strong>Paid</strong><small>{{ number_format($summary['paid_count']) }} invoices · {{ $formatMoney($summary['paid_amount']) }}</small></div></div>
                    <div><span class="rph-legend-dot unpaid"></span><div><strong>Unpaid</strong><small>{{ number_format($summary['unpaid_count']) }} invoices · {{ $formatMoney($summary['unpaid_amount']) }}</small></div></div>
                </div>
            </div>
        </article>
        <article class="rph-card rph-chart-card rph-month-card">
            <div class="rph-card-heading"><div><h2>Payments per month</h2><p>Last six months · LKR</p></div></div>
            <div class="rph-month-chart" role="img" aria-label="Monthly paid and unpaid invoice amounts for the last six months">
                @foreach($monthlyAmounts as $month)
                    @php
                        $paidHeight = $month['paid'] > 0 ? max(3, $month['paid'] / $maxMonthlyAmount * 100) : 0;
                        $unpaidHeight = $month['unpaid'] > 0 ? max(3, $month['unpaid'] / $maxMonthlyAmount * 100) : 0;
                    @endphp
                    <div class="rph-month-column" title="{{ $month['label'] }} · Paid {{ $formatMoney($month['paid']) }} · Unpaid {{ $formatMoney($month['unpaid']) }}">
                        <div class="rph-month-bars"><span class="paid" style="height:{{ $paidHeight }}%"></span><span class="unpaid" style="height:{{ $unpaidHeight }}%"></span></div>
                        <small>{{ $month['label'] }}</small>
                    </div>
                @endforeach
            </div>
            <div class="rph-month-legend"><span><i class="paid"></i> Paid</span><span><i class="unpaid"></i> Unpaid</span></div>
        </article>
    </section>
@else
    <section class="rph-card rph-empty-dashboard"><span><i class="ti ti-chart-donut"></i></span><strong>No payment data for this range</strong><small>Try a different date range or resource type to see the dashboard.</small></section>
@endif

<section class="rph-card rph-recent-card">
    <div class="rph-card-heading">
        <div><h2>Recent payments</h2><p>The five latest paid invoices.</p></div>
        <a href="{{ route('payments.history', array_merge(collect($filters)->except(['tab', 'section', 'page'])->all(), ['tab' => 'payments', 'status' => 'paid'])) }}" data-rph-paid>View all paid</a>
    </div>
    @if($recentPaid->isNotEmpty())
        <div class="rph-recent-list">
            @foreach($recentPaid as $invoice)
                @php $reservation = $invoice->reservation; @endphp
                <a class="rph-recent-row" href="{{ $reservation ? route('payments.resources.print', ['reference' => $reservation->reference]) : '#' }}">
                    <span class="rph-recent-icon"><i class="ti ti-receipt"></i></span>
                    <span class="rph-recent-copy"><strong>{{ $invoice->invoice_number ?: 'Draft invoice' }}</strong><small>{{ $reservation?->requester?->name ?? $reservation?->createdBy?->name ?? 'Reservation user' }}</small></span>
                    <strong class="rph-recent-amount">{{ $formatMoney(data_get($invoice->fee_breakdown, 'total', 0)) }}</strong>
                    <time datetime="{{ $invoice->paid_at?->toIso8601String() }}">{{ $invoice->paid_at?->format('d M Y') ?? '—' }}</time>
                </a>
            @endforeach
        </div>
    @else
        <div class="rph-recent-empty">No paid invoices in the selected range yet.</div>
    @endif
</section>
