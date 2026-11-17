@extends('layouts.app')

@section('title', 'Resource Payments & Invoicing')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/create-user.css') }}?v=15">
<link rel="stylesheet" href="{{ asset('css/reservation-index.css') }}?v=18">
<link rel="stylesheet" href="{{ asset('css/resource-payments.css') }}?v=13">
<link rel="stylesheet" href="{{ asset('css/resource-payment-record.css') }}?v=5">
<link rel="stylesheet" href="{{ asset('css/dashboard-role.css') }}?v={{ @filemtime(public_path('css/dashboard-role.css')) ?: time() }}">
@endpush

@section('content')
<div class="container-fluid mt-4 rsv-page rp-page">
    <header class="rd-hero rp-dashboard-hero">
        <div class="rd-hero__copy">
            <h1>Resource Payment &amp; Invoicing</h1>
            <p class="rd-hero__sub">Calculate usage charges, review invoices, and track reservation payment status.</p>
        </div>
        <nav class="rd-hero__actions" aria-label="Payment shortcuts">
            @if(auth()->user()?->hasPermission('payments.rates.manage'))
                <a href="{{ route('payments.rates.index') }}" class="rd-btn rd-btn--solid"><i class="ti ti-tag"></i> Manage resource rates</a>
            @endif
            @if(auth()->user()?->hasPermission('reservations.index'))
                <a href="{{ route('reservations.index') }}" class="rd-btn rd-btn--outline"><i class="ti ti-history"></i> Reservation history</a>
            @endif
            <a href="{{ route('payments.history') }}" class="rd-btn rd-btn--outline"><i class="ti ti-table"></i> View payments</a>
        </nav>
    </header>

    <div id="rp-flash" class="alert rsv-auto-alert d-none" role="status"></div>

    <ol class="rp-steps" aria-label="Payment steps">
        <li class="is-current" id="rp-step-find"><span>1</span> Find reservation</li>
        <li id="rp-step-pay"><span>2</span> Collect payment</li>
    </ol>

    <div class="rp-grid">
        <div class="rp-main-column">
            <section class="rp-card">
                <div class="rp-card-title"><span class="rp-title-icon"><i class="ti ti-search"></i></span><div><h2>Search Reservation</h2><p>Find reservation details by reference number.</p></div></div>
                <form id="rp-search-form" class="rp-search-form" action="{{ route('payments.resources.lookup') }}" data-print-template="{{ route('payments.resources.print', ['reference' => '__REFERENCE__']) }}" novalidate>
                    <label for="rp-search">Reservation Reference</label>
                    <div class="rp-search-row">
                        <div class="input-group rsv-search-group rp-search-group">
                            <span class="input-group-text"><i class="ti ti-search"></i></span>
                            <input id="rp-search" name="reference" type="text" class="form-control" placeholder="e.g. RRS-2026-000123" autocomplete="off" spellcheck="false" inputmode="text" aria-label="Reservation reference, for example RRS-2026-000123" aria-describedby="rp-search-message">
                        </div>
                        <button type="submit" class="btn btn-primary rp-search-btn"><i class="ti ti-search me-1"></i> Find</button>
                    </div>
                    <small class="rp-search-hint">Use RRS-YYYY-000000 or HST-YYYY-000000</small>
                    <div id="rp-search-message" class="rp-message" aria-live="polite"></div>
                </form>
                <div id="rp-reservation" class="rp-reservation rp-empty-reservation"><div class="rp-reservation-summary"><span class="rp-resource-icon"><i class="ti ti-building-community"></i></span><div class="rp-resource-copy"><strong>Search to load a reservation</strong><span>Reservation details and usage will appear here.</span></div><span class="rp-date"><small>STATUS</small><b>Awaiting lookup</b></span></div></div>
            </section>

            <section class="rp-card rp-guide-card">
                <div class="rp-card-title"><span class="rp-title-icon"><i class="ti ti-checklist"></i></span><div><h2>Payment collection</h2><p>Charges follow the booked reservation time and the resource rate card.</p></div></div>
                <ol class="rp-guide-list">
                    <li><span>1</span><div><strong>Find the booking</strong><p>Search with an RRS or HST reference to load the reservation.</p></div></li>
                    <li><span>2</span><div><strong>Review the invoice</strong><p>Confirm the rate, discount, and tax on the preview before collecting money.</p></div></li>
                    <li><span>3</span><div><strong>Record the payment</strong><p>Mark cash or bank transfer as paid, then print the invoice for the requester.</p></div></li>
                </ol>
                <div class="rp-billing-snapshot">
                    <div><span>Reserved time</span><div id="rp-time-range" class="rp-time-range"><span>Start <b>—</b></span><span>End <b>—</b></span></div></div>
                    <div><span>Booked duration</span><strong id="rp-reserved-duration">—</strong></div>
                </div>
                <input id="rp-extra-hours" type="hidden" value="0">
                <button type="button" id="rp-hours-up" hidden tabindex="-1"></button>
                <button type="button" id="rp-hours-down" hidden tabindex="-1"></button>
                <button type="button" id="rp-confirm-usage" hidden tabindex="-1"></button>
                <span id="rp-usage-message" hidden></span>
                <span id="rp-actual-duration" hidden>—</span>
            </section>

        </div>

        <aside class="rp-invoice" id="rp-invoice">
            <div class="rp-invoice-header"><div><span class="rp-eyebrow">PAYMENT SUMMARY</span><h2>Invoice Preview</h2><span id="rp-invoice-number">Draft invoice</span></div><span class="rp-status" id="rp-status">UNPAID</span></div>
            <div class="rp-invoice-body">
                <div class="rp-invoice-meta"><div><span>Invoice number</span><strong id="rp-invoice-id">DRAFT</strong></div><div><span>Issue date</span><strong id="rp-issue-date">{{ now()->format('d M Y') }}</strong></div></div>
                <div class="rp-invoice-recipient"><span>Bill to</span><strong id="rp-requester">Reservation user</strong><small id="rp-requester-email"></small><small id="rp-invoice-ref">Search a reservation to begin</small><small id="rp-invoice-resource"></small></div>
                <div class="rp-line-items"><div class="rp-line"><span>Base Reservation Fee <small id="rp-base-detail">Includes first hours from the applicable rate</small></span><strong id="rp-base-fee">LKR 0.00</strong></div><div class="rp-line"><span>Extra Hours Charge <small><span id="rp-extra-detail">0.0 hrs × applicable rate</span></small></span><strong id="rp-extra-fee">LKR 0.00</strong></div><div class="rp-line"><span>Discount <small id="rp-discount-detail">Customer category</small></span><strong id="rp-discount">− LKR 0.00</strong></div><div class="rp-line"><span>Tax <small id="rp-tax-detail">Applicable rate</small></span><strong id="rp-tax-fee">LKR 0.00</strong></div></div>
                <div class="rp-total"><div><span>TOTAL AMOUNT DUE</span><small>Draft total in Sri Lankan rupees</small></div><strong id="rp-total" aria-live="polite" aria-atomic="true">LKR 0.00</strong></div>
                <div id="rp-rate-warning" class="rp-rate-warning is-hidden" role="status"><i class="ti ti-alert-circle"></i><span>Rate details are not available. Configure an applicable rate before collecting payment.</span></div>
                <div class="rp-paid-confirmation" id="rp-payment-note" hidden><i class="ti ti-circle-check"></i><span><strong id="rp-payment-status-text">Paid</strong><span id="rp-payment-note-detail"></span></span></div>
            </div>
            <div class="rp-invoice-actions">@if(auth()->user()?->hasPermission('payments.mark-paid'))<button type="button" class="rp-mark-paid-button" id="rp-mark-paid" data-url-template="{{ route('payments.resources.mark-paid', ['reference' => '__REFERENCE__']) }}" hidden disabled><i class="ti ti-cash"></i> Mark as Paid</button>@endif<button type="button" class="rp-print-button" id="rp-print" disabled><i class="ti ti-printer"></i> Print Invoice</button></div>
            <div class="rp-invoice-foot"><i class="ti ti-file-invoice"></i> <span id="rp-invoice-footnote">Draft only. Not a tax invoice until issued by Finance.</span></div>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.RPIndex = {
    confirmUrl: @json(route('payments.resources.usage.confirm')),
    canMarkPaid: @json(auth()->user()?->hasPermission('payments.mark-paid') ?? false)
};
</script>
<script src="{{ asset('js/resource-payment-record.js') }}?v=5"></script>
<script src="{{ asset('js/resource-select.js') }}?v=3"></script>
<script src="{{ asset('js/resource-payments.js') }}?v=7"></script>
@endpush
