@extends('layouts.app')

@section('title', 'Resource Payments & Invoicing')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/resource-payments.css') }}?v={{ @filemtime(public_path('css/resource-payments.css')) ?: time() }}">
@endpush

@section('content')
<main class="rp-page">
    <div class="rp-heading">
        <div><span class="rp-eyebrow">PAYMENTS &amp; INVOICING</span><h1>Resource Payment &amp; Invoicing</h1><p>Calculate usage charges, review invoices, and track reservation payment status.</p></div>
        <a href="{{ route('reservations.index') }}" class="rp-history"><i class="ti ti-history"></i> Reservation history</a>
    </div>

    <div class="rp-grid">
        <div class="rp-main-column">
            <section class="rp-card">
                <div class="rp-card-title"><span class="rp-title-icon"><i class="ti ti-search"></i></span><div><h2>Search Reservation</h2><p>Find a reservation by reference number or resource name.</p></div></div>
                <form id="rp-search-form" class="rp-search-form" action="{{ route('payments.resources.lookup') }}">
                    <label for="rp-search">Reservation ID or Resource Name</label>
                    <div class="rp-search-row"><input id="rp-search" name="query" type="search" placeholder="e.g. RRS-2026-000123" autocomplete="off" required><button type="submit"><i class="ti ti-search"></i> Find</button></div>
                    <div id="rp-search-message" class="rp-message" aria-live="polite"></div>
                </form>
                <div id="rp-reservation" class="rp-reservation rp-empty-reservation"><div class="rp-reservation-summary"><span class="rp-resource-icon"><i class="ti ti-building-community"></i></span><div class="rp-resource-copy"><strong>Search to load a reservation</strong><span>Reservation details and usage will appear here.</span></div><span class="rp-date"><small>STATUS</small><b>Awaiting lookup</b></span></div></div>
            </section>

            <section class="rp-card rp-usage-card">
                <div class="rp-card-title"><span class="rp-title-icon"><i class="ti ti-clock-hour-4"></i></span><div><h2>Usage Details</h2><p>Confirm the actual time used before invoicing.</p></div></div>
                <div class="rp-usage-grid">
                    <div><label class="rp-label">Reserved Time</label><div id="rp-time-range" class="rp-time-range"><span>Start <b>—</b></span><span>End <b>—</b></span></div></div>
                    <div><label for="rp-extra-hours" class="rp-label">Extra Hours Used</label><div class="rp-hours-control"><button type="button" id="rp-hours-down" aria-label="Decrease extra hours"><i class="ti ti-chevron-down"></i></button><input id="rp-extra-hours" type="number" min="0" max="24" step="0.5" value="0" aria-label="Extra hours"><button type="button" id="rp-hours-up" aria-label="Increase extra hours"><i class="ti ti-chevron-up"></i></button><span>Hours</span></div><small id="rp-overtime-note">Additional usage is calculated from the applicable hourly rate.</small></div>
                </div>
                <div class="rp-usage-foot"><i class="ti ti-info-circle"></i><span>Only confirm the actual extra time used. The reservation’s original duration is included in the base fee.</span></div>
            </section>

            <div class="rp-payment-note"><i class="ti ti-shield-lock"></i><span>Invoice preview is available now. Online payment processing will be enabled when a finance gateway is connected.</span></div>
        </div>

        <aside class="rp-invoice" id="rp-invoice">
            <div class="rp-invoice-header"><div><span class="rp-eyebrow">PAYMENT SUMMARY</span><h2>Invoice Preview</h2><span id="rp-invoice-number">Draft invoice</span></div><span class="rp-status" id="rp-status">DRAFT</span></div>
            <div class="rp-invoice-body">
                <div class="rp-invoice-recipient"><span>Bill to</span><strong id="rp-requester">Reservation user</strong><small id="rp-invoice-ref">Search a reservation to begin</small></div>
                <div class="rp-line-items"><div class="rp-line"><span>Base Reservation Fee <small id="rp-base-detail">Reservation duration</small></span><strong id="rp-base-fee">LKR 0.00</strong></div><div class="rp-line"><span>Extra Hours Charge <small><span id="rp-extra-detail">0.0 hrs × applicable rate</span></small></span><strong id="rp-extra-fee">LKR 0.00</strong></div><div class="rp-line"><span>Service Fees <small>Administration &amp; processing</small></span><strong id="rp-service-fee">LKR 0.00</strong></div></div>
                <div class="rp-total"><div><span>TOTAL AMOUNT DUE</span><small>Taxes and processing fees included where applicable</small></div><strong id="rp-total">LKR 0.00</strong></div>
                <div id="rp-rate-warning" class="rp-rate-warning"><i class="ti ti-alert-circle"></i><span>Rate details are not available. Configure an applicable rate before collecting payment.</span></div>
                <div class="rp-secure-note"><i class="ti ti-lock-check"></i><span>Payment status is recorded against the reservation. A receipt should be issued after a successful transaction.</span></div>
            </div>
            <div class="rp-invoice-actions"><button type="button" class="rp-pay-button" id="rp-pay" disabled><i class="ti ti-credit-card"></i> Confirm Payment</button><button type="button" class="rp-print-button" id="rp-print"><i class="ti ti-printer"></i> Generate &amp; Print Invoice</button></div>
            <div class="rp-invoice-foot"><i class="ti ti-file-invoice"></i> This preview is not a tax invoice until issued by Finance.</div>
        </aside>
    </div>
</main>
@endsection

@push('scripts')
<script>
(() => {
    const form = document.getElementById('rp-search-form');
    const message = document.getElementById('rp-search-message');
    const hoursInput = document.getElementById('rp-extra-hours');
    const money = value => 'LKR ' + Number(value || 0).toLocaleString('en-LK', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    let reservation = null;

    function renderInvoice() {
        const extra = Math.max(0, Number(hoursInput.value) || 0);
        const rate = reservation?.extra_hourly_rate || 0;
        const extraFee = rate * extra;
        const base = reservation?.base_fee || 0;
        const service = 0;
        document.getElementById('rp-base-fee').textContent = money(base);
        document.getElementById('rp-extra-fee').textContent = money(extraFee);
        document.getElementById('rp-service-fee').textContent = money(service);
        document.getElementById('rp-total').textContent = money(base + extraFee + service);
        document.getElementById('rp-extra-detail').textContent = extra.toFixed(1) + ' hrs × ' + money(rate) + '/hr';
        document.getElementById('rp-status').textContent = reservation ? 'PAYMENT DUE' : 'DRAFT';
        document.getElementById('rp-rate-warning').classList.toggle('is-hidden', Boolean(reservation?.has_rate && (extra === 0 || reservation?.has_extra_hourly_rate)));
        document.getElementById('rp-pay').disabled = !reservation?.has_rate || (extra > 0 && !reservation?.has_extra_hourly_rate);
    }

    form.addEventListener('submit', async event => {
        event.preventDefault();
        const button = form.querySelector('button');
        button.disabled = true;
        message.textContent = 'Searching reservations…';
        message.className = 'rp-message';
        try {
            const response = await fetch(form.action + '?query=' + encodeURIComponent(form.query.value), {headers: {'Accept': 'application/json'}});
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'Could not find a reservation.');
            reservation = payload;
            const reservationCard = document.getElementById('rp-reservation');
            reservationCard.classList.remove('rp-empty-reservation', 'is-expanded');
            reservationCard.innerHTML = `
                <div class="rp-reservation-summary">
                    <span class="rp-resource-icon"><i class="ti ti-building-community"></i></span>
                    <div class="rp-resource-copy"><strong>${payload.resource}</strong><span>${payload.reference}</span></div>
                    <span class="rp-date"><small>RESERVED</small><b>${payload.date || '—'}</b></span>
                    <button type="button" class="rp-expand-button" aria-expanded="false" aria-controls="rp-reservation-details" aria-label="Show reservation details"><i class="ti ti-chevron-down"></i></button>
                </div>
                <div class="rp-reservation-details" id="rp-reservation-details" hidden>
                    <div><span>Requester</span><strong>${payload.requester}</strong></div>
                    <div><span>Reserved time</span><strong>${payload.start} – ${payload.end}</strong></div>
                    <div><span>Duration</span><strong>${Number(payload.hours).toFixed(1)} hours</strong></div>
                    <div><span>Payment status</span><strong>${payload.has_rate ? 'Payment due' : 'Rate required'}</strong></div>
                </div>`;
            reservationCard.querySelector('.rp-expand-button').addEventListener('click', event => {
                const toggle = event.currentTarget;
                const expanded = toggle.getAttribute('aria-expanded') === 'true';
                toggle.setAttribute('aria-expanded', String(!expanded));
                toggle.setAttribute('aria-label', expanded ? 'Show reservation details' : 'Hide reservation details');
                reservationCard.classList.toggle('is-expanded', !expanded);
                document.getElementById('rp-reservation-details').hidden = expanded;
            });
            document.getElementById('rp-time-range').innerHTML = `<span>Start <b>${payload.start}</b></span><span>End <b>${payload.end}</b></span>`;
            document.getElementById('rp-requester').textContent = payload.requester;
            document.getElementById('rp-invoice-ref').textContent = payload.reference + ' · ' + (payload.date || '');
            document.getElementById('rp-invoice-number').textContent = 'Draft · ' + payload.reference;
            document.getElementById('rp-base-detail').textContent = payload.hours.toFixed(1) + ' reserved hours';
            document.getElementById('rp-extra-hours').value = '0';
            message.textContent = payload.rate_note || 'Reservation loaded. Review actual usage and invoice details.';
            message.className = 'rp-message ' + (payload.has_rate ? 'is-success' : 'is-warning');
            renderInvoice();
        } catch (error) {
            reservation = null;
            renderInvoice();
            message.textContent = error.message;
            message.className = 'rp-message is-error';
        } finally { button.disabled = false; }
    });

    hoursInput.addEventListener('input', () => {
        if (Number(hoursInput.value) > 0 && !reservation?.has_extra_hourly_rate) {
            message.textContent = 'An hourly rate for extra usage is not stored for this reservation.';
            message.className = 'rp-message is-warning';
        } else if (reservation) {
            message.textContent = reservation.rate_note || 'Reservation loaded. Review actual usage and invoice details.';
            message.className = 'rp-message ' + (reservation.has_rate ? 'is-success' : 'is-warning');
        }
        renderInvoice();
    });
    document.getElementById('rp-hours-up').addEventListener('click', () => { hoursInput.value = Math.min(24, (Number(hoursInput.value) || 0) + 0.5); renderInvoice(); });
    document.getElementById('rp-hours-down').addEventListener('click', () => { hoursInput.value = Math.max(0, (Number(hoursInput.value) || 0) - 0.5); renderInvoice(); });
    document.getElementById('rp-print').addEventListener('click', () => window.print());
    document.getElementById('rp-pay').addEventListener('click', () => alert('Online payment processing is not configured yet. The invoice is ready to print.'));
    renderInvoice();
})();
</script>
@endpush
