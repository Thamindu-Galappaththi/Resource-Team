@extends('layouts.app')

@section('title', 'View Payments')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/create-user.css') }}?v=15">
<link rel="stylesheet" href="{{ asset('css/reservation-index.css') }}?v=18">
<link rel="stylesheet" href="{{ asset('css/resource-calendar.css') }}?v=38">
<link rel="stylesheet" href="{{ asset('css/resource-payments.css') }}?v=43">
<link rel="stylesheet" href="{{ asset('css/dashboard-role.css') }}?v={{ @filemtime(public_path('css/dashboard-role.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('css/resource-payment-history.css') }}?v=2">
@endpush

@section('content')
@php
    $queryFilters = collect($filters)->except('tab')->all();
    $formatMoney = fn ($amount) => 'LKR '.number_format((float) $amount, 2);
    $paidShare = $summary['total_amount'] > 0 ? round($summary['paid_amount'] / $summary['total_amount'] * 100, 1) : 0;
@endphp
<div class="container-fluid mt-4 rsv-page rp-page rr-admin rp-history-page">
    <header class="rd-hero rph-hero">
        <div class="rd-hero__copy">
            <h1>View payments</h1>
            <p class="rd-hero__sub">Review resource invoices and payment status.</p>
        </div>
        <nav class="rd-hero__actions" aria-label="Payment navigation">
            <a href="{{ route('payments.resources') }}" class="rd-btn rd-btn--outline"><i class="ti ti-arrow-left"></i> Back to invoicing</a>
        </nav>
    </header>

    <section class="rr-stats rc-cards rph-stats" aria-label="Payment summary">
        <article class="rc-card" style="--stat-color:#1769c2;--stat-icon-bg:#e8f0fa">
            <span class="rc-card-icon" aria-hidden="true"><i class="ti ti-file-invoice"></i></span><div class="rc-card-label">Total invoices</div><div class="rc-card-value">{{ number_format($summary['total_count']) }}</div><small class="rph-stat-detail">{{ $formatMoney($summary['total_amount']) }} invoice value</small>
        </article>
        <article class="rc-card" style="--stat-color:#16a77a;--stat-icon-bg:#e7f6f0">
            <span class="rc-card-icon" aria-hidden="true"><i class="ti ti-circle-check"></i></span><div class="rc-card-label">Paid · {{ number_format($summary['paid_count']) }}</div><div class="rc-card-value rph-stat-money">{{ $formatMoney($summary['paid_amount']) }}</div><small class="rph-stat-detail">Collected</small>
        </article>
        <article class="rc-card" style="--stat-color:#d97706;--stat-icon-bg:#fff4e5">
            <span class="rc-card-icon" aria-hidden="true"><i class="ti ti-clock"></i></span><div class="rc-card-label">Unpaid · {{ number_format($summary['unpaid_count']) }}</div><div class="rc-card-value rph-stat-money">{{ $formatMoney($summary['unpaid_amount']) }}</div><small class="rph-stat-detail">Outstanding</small>
        </article>
        <article class="rc-card" style="--stat-color:#7355c7;--stat-icon-bg:#f0ebff">
            <span class="rc-card-icon" aria-hidden="true"><i class="ti ti-chart-pie"></i></span><div class="rc-card-label">Collection rate</div><div class="rc-card-value">{{ number_format($summary['collection_rate'], 1) }}%</div><small class="rph-stat-detail">Paid value / total value</small>
        </article>
    </section>

    <nav class="rr-tabs rph-tabs" aria-label="Payment history sections">
        <a href="{{ route('payments.history', array_merge($queryFilters, ['tab' => 'payments'])) }}" @class(['is-active' => $activeTab === 'payments']) @if($activeTab === 'payments') aria-current="page" @endif><i class="ti ti-list-details"></i> Payments</a>
        <a href="{{ route('payments.history', array_merge($queryFilters, ['tab' => 'dashboard'])) }}" @class(['is-active' => $activeTab === 'dashboard']) @if($activeTab === 'dashboard') aria-current="page" @endif><i class="ti ti-chart-donut"></i> Dashboard</a>
    </nav>

    @if($activeTab === 'dashboard')
        <section class="rph-card rph-filter-card" aria-label="Dashboard filters">
            <form method="GET" action="{{ route('payments.history') }}" class="rph-filter-form">
                <input type="hidden" name="tab" value="dashboard">
                <div><label for="rph-dash-from">Issue date from</label><input id="rph-dash-from" class="form-control" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></div>
                <div><label for="rph-dash-to">Issue date to</label><input id="rph-dash-to" class="form-control" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></div>
                <div><label for="rph-dash-type">Resource type</label><select id="rph-dash-type" class="form-select" name="resource_type"><option value="">All resource types</option>@foreach($resourceTypes as $type)<option value="{{ $type->id }}" @selected((string) ($filters['resource_type'] ?? '') === (string) $type->id)>{{ $type->name }}</option>@endforeach</select></div>
                <div class="rph-filter-actions"><button class="btn btn-primary" type="submit"><i class="ti ti-filter"></i> Apply filters</button><a class="rph-clear-link" href="{{ route('payments.history', ['tab' => 'dashboard']) }}">Clear</a></div>
            </form>
        </section>

        @if($summary['total_count'] > 0)
            <section class="rph-dashboard-grid" aria-label="Payment charts">
                <article class="rph-card rph-chart-card">
                    <div class="rph-card-heading"><div><h2>Paid vs unpaid</h2><p>Invoice amount by payment status.</p></div></div>
                    <div class="rph-donut-layout">
                        <div class="rph-donut" role="img" aria-label="{{ number_format($paidShare, 1) }} percent of invoice value collected" style="--paid-share: {{ $paidShare }}%"><div><strong>{{ number_format($paidShare, 1) }}%</strong><span>Collected</span></div></div>
                        <div class="rph-legend"><div><span class="rph-legend-dot paid"></span><div><strong>Paid</strong><small>{{ number_format($summary['paid_count']) }} invoices · {{ $formatMoney($summary['paid_amount']) }}</small></div></div><div><span class="rph-legend-dot unpaid"></span><div><strong>Unpaid</strong><small>{{ number_format($summary['unpaid_count']) }} invoices · {{ $formatMoney($summary['unpaid_amount']) }}</small></div></div></div>
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
            <div class="rph-card-heading"><div><h2>Recent payments</h2><p>The five latest paid invoices.</p></div><a href="{{ route('payments.history', array_merge($queryFilters, ['tab' => 'payments', 'status' => 'paid'])) }}">View all paid</a></div>
            @if($recentPaid->isNotEmpty())
                <div class="rph-recent-list">
                    @foreach($recentPaid as $invoice)
                        @php
                            $reservation = $invoice->reservation;
                        @endphp
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
    @else
        <section class="rph-card rph-table-card">
            <div class="rph-card-heading rph-table-heading"><div><h2>Resource invoices</h2><p>View-only invoice and payment records.</p></div><span class="rph-record-count">{{ number_format($payments->total()) }} records</span></div>
            <form method="GET" action="{{ route('payments.history') }}" class="rph-table-toolbar">
                <input type="hidden" name="tab" value="payments">
                <div class="rph-search"><label class="visually-hidden" for="rph-search">Search payments</label><i class="ti ti-search" aria-hidden="true"></i><input id="rph-search" type="search" name="search" class="form-control" placeholder="Search invoice, reservation, requester, resource" value="{{ $filters['search'] ?? '' }}"></div>
                <label class="visually-hidden" for="rph-status">Payment status</label><select id="rph-status" name="status" class="form-select rph-status-filter"><option value="">All statuses</option><option value="paid" @selected(($filters['status'] ?? '') === 'paid')>Paid</option><option value="unpaid" @selected(($filters['status'] ?? '') === 'unpaid')>Unpaid</option></select>
                <label class="visually-hidden" for="rph-type">Resource type</label><select id="rph-type" name="resource_type" class="form-select rph-type-filter"><option value="">All types</option>@foreach($resourceTypes as $type)<option value="{{ $type->id }}" @selected((string) ($filters['resource_type'] ?? '') === (string) $type->id)>{{ $type->name }}</option>@endforeach</select>
                <label class="visually-hidden" for="rph-from">Issue date from</label><input id="rph-from" type="date" name="date_from" class="form-control rph-date-filter" value="{{ $filters['date_from'] ?? '' }}" aria-label="Issue date from">
                <label class="visually-hidden" for="rph-to">Issue date to</label><input id="rph-to" type="date" name="date_to" class="form-control rph-date-filter" value="{{ $filters['date_to'] ?? '' }}" aria-label="Issue date to">
                <label class="visually-hidden" for="rph-per-page">Rows per page</label><select id="rph-per-page" name="per_page" class="form-select rph-per-page"><option value="10" @selected((int) ($filters['per_page'] ?? 10) === 10)>10 rows</option><option value="25" @selected((int) ($filters['per_page'] ?? 10) === 25)>25 rows</option><option value="50" @selected((int) ($filters['per_page'] ?? 10) === 50)>50 rows</option></select>
                <button class="rph-search-button" type="submit" aria-label="Search and filter payments" title="Search and filter"><i class="ti ti-search"></i></button>
                <a class="rph-export-btn" href="{{ route('payments.history.export', request()->except(['page', 'tab'])) }}"><i class="ti ti-download"></i> Export CSV</a>
            </form>
            <div class="table-responsive rph-table-wrap">
                <table class="table align-middle mb-0 rph-table">
                    <thead><tr><th>Invoice number</th><th>Reservation</th><th>Requester</th><th>Resource</th><th class="text-end">Amount (LKR)</th><th>Status</th><th>Issue date</th><th>Paid date</th><th class="text-center">View</th></tr></thead>
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
                            <td data-label="View" class="rph-view-cell">@if($reservation)<a class="rph-view-button" href="{{ route('payments.resources.print', ['reference' => $reservation->reference]) }}" title="View invoice {{ $invoice->invoice_number }}" aria-label="View invoice {{ $invoice->invoice_number }}"><i class="ti ti-eye"></i></a>@else<span class="rph-view-disabled" aria-label="Invoice preview unavailable"><i class="ti ti-eye-off"></i></span>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="rph-empty"><span><i class="ti ti-receipt"></i></span><strong>No invoices found</strong><small>Try changing the search or filters.</small></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="rph-pagination"><span>Showing {{ $payments->firstItem() ?? 0 }}–{{ $payments->lastItem() ?? 0 }} of {{ number_format($payments->total()) }}</span>{{ $payments->links() }}</div>
        </section>
    @endif
</div>
@endsection
