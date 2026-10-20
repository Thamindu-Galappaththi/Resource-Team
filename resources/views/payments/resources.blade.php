@extends('layouts.app')

@section('title', 'Resource Payments & Invoicing')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/resource-payments.css') }}?v={{ @filemtime(public_path('css/resource-payments.css')) ?: time() }}">
@endpush

@section('content')
<main class="rp-page">
    <div class="rp-heading">
        <div><span class="rp-eyebrow">PAYMENTS &amp; INVOICING</span><h1>Resource Payment &amp; Invoicing</h1><p>Calculate usage charges, review invoices, and track reservation payment status.</p></div>
        <div class="rp-header-actions">
            @if(auth()->user()?->hasPermission('payments.rates.manage'))<a href="{{ route('payments.rates.index') }}" class="rp-history">Manage resource rates</a>@endif
            <a href="{{ route('reservations.index') }}" class="rp-history"><i class="ti ti-history"></i> Reservation history</a>
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
                    <div class="rp-search-row"><input id="rp-search" name="reference" type="search" placeholder="e.g. RRS-2026-000123" autocomplete="off" pattern="(RRS|HST)-[0-9]{4}-[0-9]{6}" title="Use the format RRS-YYYY-000000 or HST-YYYY-000000" required><button type="submit"><i class="ti ti-search"></i> Find</button></div>
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
                <div class="rp-line-items"><div class="rp-line"><span>Base Reservation Fee <small id="rp-base-detail">Reservation duration</small></span><strong id="rp-base-fee">LKR 0.00</strong></div><div class="rp-line"><span>Extra Hours Charge <small><span id="rp-extra-detail">0.0 hrs × applicable rate</span></small></span><strong id="rp-extra-fee">LKR 0.00</strong></div><div class="rp-line"><span>Discount <small id="rp-discount-detail">Customer category</small></span><strong id="rp-discount">− LKR 0.00</strong></div><div class="rp-line"><span>Tax <small id="rp-tax-detail">Applicable rate</small></span><strong id="rp-tax-fee">LKR 0.00</strong></div></div>
                <div class="rp-total"><div><span>TOTAL AMOUNT DUE</span><small>Draft total in Sri Lankan rupees</small></div><strong id="rp-total" aria-live="polite" aria-atomic="true">LKR 0.00</strong></div>
                <div id="rp-rate-warning" class="rp-rate-warning is-hidden" role="status"><i class="ti ti-alert-circle"></i><span>Rate details are not available. Configure an applicable rate before collecting payment.</span></div>
                <div class="rp-secure-note"><i class="ti ti-clock-hour-4"></i><span>Online payment status: <strong>Pending</strong>.</span></div>
            </div>
            <div class="rp-invoice-actions"><button type="button" class="rp-pay-button" id="rp-pay" disabled title="Online payments are pending gateway integration"><i class="ti ti-credit-card"></i> Pay now</button><button type="button" class="rp-print-button" id="rp-print"><i class="ti ti-printer"></i> Print Invoice</button></div>
            <div class="rp-invoice-foot"><i class="ti ti-file-invoice"></i> Draft only. Not a tax invoice until issued by Finance.</div>
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
    const printButton = document.getElementById('rp-print');
    const confirmUsageButton = document.getElementById('rp-confirm-usage');
    const money = value => 'LKR ' + Number(value || 0).toLocaleString('en-LK', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char]));
    let reservation = null;
    let feeRequestSequence = 0;
    let usageConfirmed = false;

    function renderInvoice() {
        const base = reservation?.base_fee || 0;
        document.getElementById('rp-base-fee').textContent = money(base);
        document.getElementById('rp-extra-fee').textContent = money(reservation?.extra_hour_fee || 0);
        document.getElementById('rp-tax-fee').textContent = money(reservation?.tax_fee || 0);
        document.getElementById('rp-discount').textContent = '− ' + money(reservation?.discount || 0);
        document.getElementById('rp-total').textContent = money(reservation?.total || 0);
        const lines = reservation?.lines || [];
        document.getElementById('rp-extra-detail').textContent = lines.length
            ? lines.map(line => line.resource + ': ' + Number(line.extra_hours).toFixed(2) + ' hrs × ' + money(line.extra_hourly_rate) + '/hr').join(' · ')
            : 'Rate not available';
        document.getElementById('rp-base-detail').textContent = lines.length
            ? lines.map(line => line.resource + ': includes first ' + Number(line.base_duration_hours).toFixed(2).replace(/\.00$/, '') + ' hrs').join(' · ')
            : 'Rate not available';
        document.getElementById('rp-tax-detail').textContent = reservation?.tax_percent === null ? 'Per resource rate after discount' : Number(reservation?.tax_percent || 0).toFixed(3).replace(/0+$/, '').replace(/\.$/, '') + '% after discount';
        document.getElementById('rp-discount-detail').textContent = Number(reservation?.discount_percent || 0).toFixed(2).replace(/\.00$/, '') + '% customer category discount';
        document.getElementById('rp-status').textContent = 'PENDING';
        const rateWarningCanBeHidden = Boolean(reservation && reservation.has_rate);
        document.getElementById('rp-rate-warning').classList.toggle('is-hidden', !reservation || rateWarningCanBeHidden);
        document.getElementById('rp-pay').disabled = true;
        printButton.disabled = !reservation;
        hoursInput.disabled = !reservation;
        document.getElementById('rp-hours-up').disabled = !reservation;
        document.getElementById('rp-hours-down').disabled = !reservation;
        confirmUsageButton.disabled = !reservation || !reservation.has_rate;
        updateStepState();
    }

    function updateStepState() {
        document.getElementById('rp-step-find').classList.toggle('is-complete', Boolean(reservation));
        document.getElementById('rp-step-find').classList.toggle('is-current', !reservation);
        document.getElementById('rp-step-usage').classList.toggle('is-current', Boolean(reservation) && !usageConfirmed);
        document.getElementById('rp-step-usage').classList.toggle('is-complete', usageConfirmed);
        document.getElementById('rp-step-pay').classList.toggle('is-current', usageConfirmed);
    }

    async function refreshBreakdown() {
        if (!reservation) {
            renderInvoice();
            return;
        }

        const requestSequence = ++feeRequestSequence;
        const params = new URLSearchParams({reference: reservation.reference, extra_hours: hoursInput.value || '0'});
        try {
            const response = await fetch(form.action + '?' + params.toString(), {headers: {'Accept': 'application/json'}});
            const payload = await response.json();
            if (!response.ok) throw new Error((payload.errors && Object.values(payload.errors).flat()[0]) || payload.message || 'Fee calculation failed.');
            if (requestSequence !== feeRequestSequence) return;
            reservation = payload;
            usageConfirmed = false;
            document.getElementById('rp-rate-warning').classList.toggle('is-hidden', Boolean(payload.has_rate));
            message.textContent = payload.rate_note || 'Fee breakdown updated.';
            message.className = 'rp-message ' + (payload.has_rate ? 'is-success' : 'is-warning');
        } catch (error) {
            if (requestSequence !== feeRequestSequence) return;
            message.textContent = error.message;
            message.className = 'rp-message is-error';
        }
        renderInvoice();
    }

    form.addEventListener('submit', async event => {
        event.preventDefault();
        const button = form.querySelector('button');
        feeRequestSequence++;
        button.disabled = true;
        button.innerHTML = '<span class="rp-spinner" aria-hidden="true"></span> Finding…';
        form.setAttribute('aria-busy', 'true');
        document.getElementById('rp-reservation').setAttribute('aria-busy', 'true');
        message.textContent = 'Searching reservations…';
        message.className = 'rp-message';
        try {
            const response = await fetch(form.action + '?reference=' + encodeURIComponent(form.reference.value.trim()), {headers: {'Accept': 'application/json'}});
            const payload = await response.json();
            if (!response.ok) {
                const error = new Error((payload.errors && Object.values(payload.errors).flat()[0]) || payload.message || 'Could not find a reservation.');
                error.status = response.status;
                throw error;
            }
            reservation = payload;
            const reservationCard = document.getElementById('rp-reservation');
            reservationCard.classList.remove('rp-empty-reservation', 'is-expanded');
            reservationCard.innerHTML = `
                <div class="rp-reservation-summary">
                    <span class="rp-resource-icon"><i class="ti ti-building-community"></i></span>
                    <div class="rp-resource-copy"><strong>${escapeHtml(payload.resource)}</strong><span>${escapeHtml(payload.reference)}</span></div>
                    <span class="rp-date"><small>RESERVED</small><b>${escapeHtml(payload.date || '—')}</b></span>
                    <button type="button" class="rp-expand-button" aria-expanded="false" aria-controls="rp-reservation-details" aria-label="Show reservation details"><i class="ti ti-chevron-down"></i></button>
                </div>
                <div class="rp-reservation-details" id="rp-reservation-details" hidden>
                    <div><span>Requester</span><strong>${escapeHtml(payload.requester)}</strong></div>
                    <div><span>Reservation status</span><strong>${escapeHtml(payload.reservation_status)}</strong></div>
                    <div><span>Reserved time</span><strong>${escapeHtml(payload.start)} – ${escapeHtml(payload.end)}</strong></div>
                    <div><span>Duration</span><strong>${Number(payload.hours).toFixed(1)} hours</strong></div>
                    <div><span>Online payment</span><strong>Pending</strong></div>
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
            document.getElementById('rp-requester-email').textContent = payload.bill_to_email || 'Email not available';
            document.getElementById('rp-invoice-ref').textContent = payload.reference + ' · ' + (payload.date || '');
            document.getElementById('rp-invoice-resource').textContent = 'Resource: ' + payload.resource;
            document.getElementById('rp-invoice-id').textContent = 'DRAFT-' + payload.reference;
            document.getElementById('rp-issue-date').textContent = payload.issue_date;
            document.getElementById('rp-base-detail').textContent = payload.base_duration_hours ? 'Includes first ' + Number(payload.base_duration_hours).toFixed(2).replace(/\.00$/, '') + ' hrs' : 'Rate not available';
            document.getElementById('rp-extra-hours').value = '0';
            document.getElementById('rp-reserved-duration').textContent = payload.hours.toFixed(1) + ' hours';
            document.getElementById('rp-actual-duration').textContent = payload.hours.toFixed(1) + ' hours';
            document.getElementById('rp-usage-card').dataset.reservationStatus = payload.reservation_status;
            message.textContent = payload.rate_note || 'Reservation loaded. Review the fee breakdown below.';
            message.className = 'rp-message ' + (payload.has_rate ? 'is-success' : 'is-warning');
            document.getElementById('rp-invoice-number').textContent = 'Draft · online payment pending';
            await refreshBreakdown();
        } catch (error) {
            reservation = null;
            usageConfirmed = false;
            if (error instanceof TypeError) error.message = 'Network error. Check your connection and try again.';
            const card = document.getElementById('rp-reservation');
            card.classList.add('rp-empty-reservation');
            const notFound = error.status === 404;
            card.innerHTML = `<div class="rp-reservation-summary"><span class="rp-resource-icon"><i class="ti ${notFound ? 'ti-search-off' : 'ti-alert-circle'}"></i></span><div class="rp-resource-copy"><strong>${notFound ? 'Reservation not found' : 'Could not load reservation'}</strong><span>${escapeHtml(error.message)}</span></div></div>`;
            document.getElementById('rp-time-range').innerHTML = '<span>Start <b>—</b></span><span>End <b>—</b></span>';
            document.getElementById('rp-requester').textContent = 'Reservation user';
            document.getElementById('rp-invoice-ref').textContent = 'Search a reservation to begin';
            document.getElementById('rp-invoice-id').textContent = 'DRAFT';
            document.getElementById('rp-invoice-number').textContent = 'Draft invoice';
            document.getElementById('rp-requester-email').textContent = '';
            document.getElementById('rp-invoice-resource').textContent = '';
            document.getElementById('rp-reserved-duration').textContent = '—';
            document.getElementById('rp-actual-duration').textContent = '—';
            renderInvoice();
            message.textContent = error.message;
            message.className = 'rp-message is-error';
        } finally {
            button.disabled = false;
            button.innerHTML = '<i class="ti ti-search"></i> Find';
            form.removeAttribute('aria-busy');
            document.getElementById('rp-reservation').removeAttribute('aria-busy');
        }
    });

    hoursInput.addEventListener('input', () => {
        usageConfirmed = false;
        updateStepState();
        const extraHours = Number(hoursInput.value);
        if (!hoursInput.validity.valid) {
            document.getElementById('rp-usage-message').textContent = 'Enter between 0 and 24 hours in 0.5 hour increments.';
            document.getElementById('rp-usage-message').classList.add('is-error');
            confirmUsageButton.disabled = true;
            return;
        }
        document.getElementById('rp-usage-message').textContent = '';
        document.getElementById('rp-usage-message').classList.remove('is-error');
        document.getElementById('rp-actual-duration').textContent = ((reservation?.hours || 0) + extraHours).toFixed(1) + ' hours';
        if (reservation) {
            message.textContent = reservation.rate_note || 'Fee breakdown updated.';
            message.className = 'rp-message ' + (reservation.has_rate ? 'is-success' : 'is-warning');
        }
        refreshBreakdown();
    });
    document.getElementById('rp-hours-up').addEventListener('click', () => { hoursInput.value = Math.min(24, (Number(hoursInput.value) || 0) + 0.5); hoursInput.dispatchEvent(new Event('input', {bubbles: true})); });
    document.getElementById('rp-hours-down').addEventListener('click', () => { hoursInput.value = Math.max(0, (Number(hoursInput.value) || 0) - 0.5); hoursInput.dispatchEvent(new Event('input', {bubbles: true})); });
    confirmUsageButton.addEventListener('click', async () => {
        if (!reservation || !hoursInput.reportValidity()) return;
        confirmUsageButton.disabled = true;
        confirmUsageButton.textContent = 'Recording…';
        try {
            const response = await fetch(@json(route('payments.resources.usage.confirm')), {
                method: 'POST',
                headers: {'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
                body: JSON.stringify({reference: reservation.reference, extra_hours: hoursInput.value})
            });
            const payload = await response.json();
            if (!response.ok) throw new Error((payload.errors && Object.values(payload.errors).flat()[0]) || payload.message || 'Could not confirm usage.');
            reservation = {...reservation, ...payload};
            usageConfirmed = true;
            message.textContent = payload.message;
            message.className = 'rp-message is-success';
            renderInvoice();
        } catch (error) {
            document.getElementById('rp-usage-message').textContent = error.message;
            document.getElementById('rp-usage-message').classList.add('is-error');
        } finally {
            confirmUsageButton.textContent = 'Confirm usage';
            confirmUsageButton.disabled = !reservation || !reservation.has_rate;
        }
    });
    printButton.addEventListener('click', () => {
        if (!reservation) return;
        const url = form.dataset.printTemplate.replace('__REFERENCE__', encodeURIComponent(reservation.reference)) + '?extra_hours=' + encodeURIComponent(hoursInput.value || '0');
        window.open(url, '_blank', 'noopener');
    });
    renderInvoice();
})();
</script>
@endpush
