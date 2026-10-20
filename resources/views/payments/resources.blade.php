@extends('layouts.app')

@section('title', 'Resource Payments & Invoicing')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/create-user.css') }}?v=15">
<link rel="stylesheet" href="{{ asset('css/reservation-index.css') }}?v=18">
<link rel="stylesheet" href="{{ asset('css/resource-payments.css') }}?v=4">
@endpush

@section('content')
<div class="container-fluid mt-4 rsv-page rp-page">
    <div class="rsv-header">
        <div>
            <h1 class="rsv-title">Resource Payment &amp; Invoicing</h1>
            <p class="rsv-subtitle mb-0">Calculate usage charges, review invoices, and track reservation payment status.</p>
        </div>
        <div class="rsv-header-actions">
            @if(auth()->user()?->hasPermission('payments.rates.manage'))
                <a href="{{ route('payments.rates.index') }}" class="btn rsv-header-btn rsv-create-btn"><i class="ti ti-tag me-1"></i> Manage resource rates</a>
            @endif
            @if(auth()->user()?->hasPermission('reservations.index'))
                <a href="{{ route('reservations.index') }}" class="btn rsv-header-btn rsv-calendar-btn"><i class="ti ti-history me-1"></i> Reservation history</a>
            @endif
        </div>
    </div>

    <ol class="rp-steps" aria-label="Payment steps">
        <li class="is-current" id="rp-step-find"><span>1</span> Find reservation</li>
        <li id="rp-step-usage"><span>2</span> Confirm usage</li>
        <li id="rp-step-pay"><span>3</span> Pay</li>
    </ol>

    <div class="rp-grid">
        <div class="rp-main-column">
            <section class="rp-card">
                <div class="rp-card-title"><span class="rp-title-icon"><i class="ti ti-search"></i></span><div><h2>Search Reservation</h2><p>Find reservation details by reference number.</p></div></div>
                <form id="rp-search-form" class="rp-search-form" action="{{ route('payments.resources.lookup') }}" data-print-template="{{ route('payments.resources.print', ['reference' => '__REFERENCE__']) }}">
                    <label for="rp-search">Reservation Reference</label>
                    <div class="rp-search-row">
                        <input id="rp-search" name="reference" type="search" placeholder="e.g. RRS-2026-000123" autocomplete="off" spellcheck="false" inputmode="text" pattern="([Rr][Rr][Ss]|[Hh][Ss][Tt])-[0-9]{4}-[0-9]{6}" title="Use the format RRS-YYYY-000000 or HST-YYYY-000000" aria-label="Reservation reference, for example RRS-2026-000123" required>
                        <button type="submit"><i class="ti ti-search"></i> Find</button>
                    </div>
                    <div id="rp-search-message" class="rp-message" aria-live="polite"></div>
                </form>
                <div id="rp-reservation" class="rp-reservation rp-empty-reservation"><div class="rp-reservation-summary"><span class="rp-resource-icon"><i class="ti ti-building-community"></i></span><div class="rp-resource-copy"><strong>Search to load a reservation</strong><span>Reservation details and usage will appear here.</span></div><span class="rp-date"><small>STATUS</small><b>Awaiting lookup</b></span></div></div>
            </section>

            <section class="rp-card rp-usage-card" id="rp-usage-card">
                <div class="rp-card-title"><span class="rp-title-icon"><i class="ti ti-clock-hour-4"></i></span><div><h2>Usage Details</h2><p>Confirm the actual time used before invoicing.</p></div></div>
                <div class="rp-usage-grid">
                    <div><label class="rp-label">Reserved Time</label><div id="rp-time-range" class="rp-time-range"><span>Start <b>—</b></span><span>End <b>—</b></span></div></div>
                    <div><label for="rp-extra-hours" class="rp-label">Extra Hours Used</label><div class="rp-hours-control"><button type="button" id="rp-hours-down" aria-label="Decrease extra hours" disabled><i class="ti ti-chevron-down"></i></button><input id="rp-extra-hours" type="number" min="0" max="24" step="0.5" value="0" aria-label="Extra hours" aria-describedby="rp-overtime-note rp-usage-message" disabled><button type="button" id="rp-hours-up" aria-label="Increase extra hours" disabled><i class="ti ti-chevron-up"></i></button><span>Hours</span></div><small id="rp-overtime-note">Enter any additional time used, in 0.5 hour increments.</small><small class="rp-validation-message" id="rp-usage-message" aria-live="polite"></small></div>
                </div>
                <div class="rp-actual-summary"><span>Reserved duration <strong id="rp-reserved-duration">—</strong></span><span>Actual duration <strong id="rp-actual-duration">—</strong></span></div>
                <div class="rp-usage-foot"><i class="ti ti-info-circle"></i><span>Extra usage is priced from the stored reservation rate. Confirm usage to record an audit entry.</span><button type="button" id="rp-confirm-usage" class="rp-confirm-usage" disabled>Confirm usage</button></div>
            </section>

            <div class="rp-gateway-banner"><i class="ti ti-info-circle"></i><span>Online payments are pending gateway integration. You can review and print a draft invoice.</span></div>
        </div>

        <aside class="rp-invoice" id="rp-invoice">
            <div class="rp-invoice-header"><div><span class="rp-eyebrow">PAYMENT SUMMARY</span><h2>Invoice Preview</h2><span id="rp-invoice-number">Draft invoice</span></div><span class="rp-status" id="rp-status">PENDING</span></div>
            <div class="rp-invoice-body">
                <div class="rp-invoice-meta"><div><span>Invoice number</span><strong id="rp-invoice-id">DRAFT</strong></div><div><span>Issue date</span><strong id="rp-issue-date">{{ now()->format('d M Y') }}</strong></div></div>
                <div class="rp-invoice-recipient"><span>Bill to</span><strong id="rp-requester">Reservation user</strong><small id="rp-requester-email"></small><small id="rp-invoice-ref">Search a reservation to begin</small><small id="rp-invoice-resource"></small></div>
                <div class="rp-line-items"><div class="rp-line"><span>Base Reservation Fee <small id="rp-base-detail">Includes first hours from the applicable rate</small></span><strong id="rp-base-fee">LKR 0.00</strong></div><div class="rp-line"><span>Extra Hours Charge <small><span id="rp-extra-detail">0.0 hrs × applicable rate</span></small></span><strong id="rp-extra-fee">LKR 0.00</strong></div><div class="rp-line"><span>Discount <small id="rp-discount-detail">Customer category</small></span><strong id="rp-discount">− LKR 0.00</strong></div><div class="rp-line"><span>Tax <small id="rp-tax-detail">Applicable rate</small></span><strong id="rp-tax-fee">LKR 0.00</strong></div></div>
                <div class="rp-total"><div><span>TOTAL AMOUNT DUE</span><small>Draft total in Sri Lankan rupees</small></div><strong id="rp-total" aria-live="polite" aria-atomic="true">LKR 0.00</strong></div>
                <div id="rp-rate-warning" class="rp-rate-warning is-hidden" role="status"><i class="ti ti-alert-circle"></i><span>Rate details are not available. Configure an applicable rate before collecting payment.</span></div>
                <div class="rp-secure-note"><i class="ti ti-clock-hour-4"></i><span>Online payment status: <strong>Pending</strong>.</span></div>
            </div>
            <div class="rp-invoice-actions"><button type="button" class="rp-pay-button" id="rp-pay" disabled title="Online payments are pending gateway integration"><i class="ti ti-credit-card"></i> Pay now</button><button type="button" class="rp-print-button" id="rp-print" disabled><i class="ti ti-printer"></i> Print Invoice</button></div>
            <div class="rp-invoice-foot"><i class="ti ti-file-invoice"></i> Draft only. Not a tax invoice until issued by Finance.</div>
        </aside>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.RPIndex = {
    confirmUrl: @json(route('payments.resources.usage.confirm'))
};
</script>
<script src="{{ asset('js/resource-payments.js') }}?v=2"></script>
@endpush
