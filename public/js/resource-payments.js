(function () {
    const form = document.getElementById('rp-search-form');
    if (!form) return;

    const config = window.RPIndex || {};
    const message = document.getElementById('rp-search-message');
    const searchInput = document.getElementById('rp-search');
    const hoursInput = document.getElementById('rp-extra-hours');
    const printButton = document.getElementById('rp-print');
    const confirmUsageButton = document.getElementById('rp-confirm-usage');
    const markPaidButton = document.getElementById('rp-mark-paid');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const money = value => 'LKR ' + Number(value || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));
    const referencePattern = /^(RRS|HST)-\d{4}-\d{6}$/i;
    let reservation = null;
    let feeRequestSequence = 0;
    let usageConfirmed = false;
    let savedConfirmedHours = 0;

    function dismissFlash(alert, delay = 5000) {
        if (!alert || alert.classList.contains('d-none')) return;
        clearTimeout(alert._rpTimer);
        alert._rpTimer = setTimeout(() => {
            alert.style.transition = 'opacity .3s ease';
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.classList.add('d-none');
                alert.style.opacity = '';
                alert.textContent = '';
            }, 300);
        }, delay);
    }

    function showFlash(text, type = 'danger') {
        const flash = document.getElementById('rp-flash');
        if (!flash || !text) return;
        flash.className = `alert alert-${type} rsv-auto-alert`;
        flash.textContent = text;
        flash.style.opacity = '1';
        flash.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        dismissFlash(flash);
    }

    function jsonHeaders(extra) {
        return Object.assign({
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf,
        }, extra || {});
    }

    function fetchJson(url, options) {
        return fetch(url, Object.assign({ credentials: 'same-origin' }, options || {}));
    }

    function setMessage(text, kind = '') {
        message.textContent = text || '';
        message.className = 'rp-message' + (kind ? ` is-${kind}` : '');
    }

    function normalizeReference() {
        searchInput.value = String(searchInput.value || '').trim().toUpperCase();
        return searchInput.value;
    }

    function updateStepState() {
        const loaded = Boolean(reservation);
        const paid = reservation?.payment_status === 'paid';
        const hasExtra = Number(hoursInput.value || 0) > 0;
        const findStep = document.getElementById('rp-step-find');
        const usageStep = document.getElementById('rp-step-usage');
        const payStep = document.getElementById('rp-step-pay');
        const steps = findStep.parentElement;
        const payStepNumber = document.getElementById('rp-step-pay-number');
        findStep.classList.toggle('is-complete', loaded);
        findStep.classList.toggle('is-current', !loaded);
        usageStep.classList.toggle('is-skipped', !hasExtra);
        steps.classList.toggle('has-skipped-step', !hasExtra);
        payStepNumber.textContent = hasExtra ? '3' : '2';
        usageStep.classList.toggle('is-current', loaded && hasExtra && !usageConfirmed && !paid);
        usageStep.classList.toggle('is-complete', loaded && hasExtra && (usageConfirmed || paid));
        payStep.classList.toggle('is-current', loaded && (!hasExtra || usageConfirmed || paid));
        payStep.classList.toggle('is-complete', paid);
    }

    function renderInvoice() {
        const loaded = Boolean(reservation);
        const paid = reservation?.payment_status === 'paid';
        const lines = reservation?.lines || [];
        const hasLoadedRate = loaded && Boolean(reservation.has_rate);
        document.getElementById('rp-base-fee').textContent = hasLoadedRate && Number(reservation?.base_fee) === 0 ? 'Free' : money(reservation?.base_fee);
        document.getElementById('rp-extra-fee').textContent = hasLoadedRate && Number(reservation?.extra_hour_fee) === 0 ? 'Free' : money(reservation?.extra_hour_fee);
        document.getElementById('rp-tax-fee').textContent = money(reservation?.tax_fee);
        document.getElementById('rp-discount').textContent = '− ' + money(reservation?.discount);
        document.getElementById('rp-total').textContent = money(reservation?.total);
        document.getElementById('rp-extra-detail').textContent = lines.length
            ? lines.map(line => `${line.resource}: ${Number(line.extra_hours).toFixed(1)} extra hrs × ${Number(line.extra_hourly_unit_rate) === 0 ? 'Free' : money(line.extra_hourly_unit_rate)}/hr`).join(' · ')
            : (loaded ? 'Rate not available' : '0.0 hrs × applicable rate');
        document.getElementById('rp-base-detail').textContent = lines.length
            ? lines.map(line => `${line.resource}: ${line.booking_type} (${line.booking_time}) · ${Number(line.base_fee) === 0 ? 'Free' : money(line.base_fee)}`).join(' · ')
            : (loaded ? 'Rate not available' : 'Includes first hours from the applicable rate');
        document.getElementById('rp-tax-detail').textContent = reservation?.tax_percent === null
            ? 'Per resource rate after discount'
            : `${Number(reservation?.tax_percent || 0).toFixed(3).replace(/0+$/, '').replace(/\.$/, '')}% after discount`;
        document.getElementById('rp-discount-detail').textContent = `${Number(reservation?.discount_percent || 0).toFixed(2).replace(/\.00$/, '')}% customer category discount`;

        const status = document.getElementById('rp-status');
        status.textContent = paid ? 'PAID' : 'UNPAID';
        status.classList.toggle('is-paid', paid);
        status.classList.toggle('is-unpaid', !paid);
        document.getElementById('rp-payment-status-text').textContent = paid ? 'Paid' : '';
        const method = reservation?.payment_method ? reservation.payment_method.replaceAll('_', ' ') : '';
        const paymentNoteDetail = document.getElementById('rp-payment-note-detail');
        if (paymentNoteDetail) paymentNoteDetail.textContent = paid
            ? ` on ${reservation.payment_date || reservation.paid_at || ''}${method ? ` via ${method}` : ''}${reservation.payment_reference ? ` · Ref ${reservation.payment_reference}` : ''}`
            : '';
        document.getElementById('rp-payment-note').hidden = !paid;
        document.getElementById('rp-invoice-footnote').textContent = paid
            ? 'Payment recorded. Not a tax invoice until issued by Finance.'
            : 'Draft only. Not a tax invoice until issued by Finance.';
        const paymentDetail = document.querySelector('#rp-reservation-details div:last-child strong');
        if (paymentDetail && loaded) paymentDetail.textContent = paid ? 'Paid' : 'Unpaid';
        document.getElementById('rp-invoice-id').textContent = reservation?.invoice_number || 'DRAFT';
        document.getElementById('rp-invoice-number').textContent = loaded
            ? `${reservation.invoice_number} · ${paid ? 'Paid' : 'Unpaid'}`
            : 'Draft invoice';
        if (loaded) document.getElementById('rp-issue-date').textContent = reservation.issue_date || '—';

        const rateWarning = document.getElementById('rp-rate-warning');
        rateWarning.classList.toggle('is-hidden', !loaded || Boolean(reservation.has_rate));
        printButton.disabled = !loaded;
        hoursInput.disabled = !loaded || paid;
        document.getElementById('rp-hours-up').disabled = !loaded || paid;
        document.getElementById('rp-hours-down').disabled = !loaded || paid;
        const extraHours = Number(hoursInput.value || 0);
        confirmUsageButton.hidden = !loaded || paid || extraHours <= 0;
        confirmUsageButton.disabled = !loaded || paid || !reservation?.has_rate || extraHours <= 0 || usageConfirmed;
        if (markPaidButton) {
            markPaidButton.hidden = !loaded || paid;
            markPaidButton.disabled = !loaded || paid || !reservation?.has_rate || (extraHours > 0 && !usageConfirmed);
            markPaidButton.title = !reservation?.has_rate
                ? 'An applicable resource rate is required before recording payment'
                : (extraHours > 0 && !usageConfirmed ? 'Confirm the extra usage before recording payment' : '');
        }
        updateStepState();
    }

    function renderReservation(payload) {
        const card = document.getElementById('rp-reservation');
        card.classList.remove('rp-empty-reservation', 'is-expanded');
        card.innerHTML = `
            <div class="rp-reservation-summary">
                <span class="rp-resource-icon"><i class="ti ti-building-community"></i></span>
                <div class="rp-resource-copy"><strong>${escapeHtml(payload.resource)}</strong><span>${escapeHtml(payload.reference)} · ID ${escapeHtml(payload.reservation_id)} · ${escapeHtml(payload.date || '—')}</span></div>
                <span class="rp-date"><small>STATUS</small><b>Reservation loaded</b></span>
                <button type="button" class="rp-expand-button" aria-expanded="false" aria-controls="rp-reservation-details" aria-label="Show reservation details"><i class="ti ti-chevron-down"></i></button>
            </div>
            <div class="rp-reservation-details" id="rp-reservation-details" hidden>
                <div><span>Reservation ID</span><strong>${escapeHtml(payload.reservation_id)}</strong></div>
                <div><span>Resource</span><strong>${escapeHtml(payload.resource)}</strong></div>
                <div><span>Location</span><strong>${escapeHtml(payload.location)}</strong></div>
                <div><span>Requester</span><strong>${escapeHtml(payload.requester)}</strong></div>
                <div><span>Date</span><strong>${escapeHtml(payload.date || '—')}</strong></div>
                <div><span>Start time</span><strong>${escapeHtml(payload.start)}</strong></div>
                <div><span>End time</span><strong>${escapeHtml(payload.end)}</strong></div>
                <div><span>Duration</span><strong>${Number(payload.hours).toFixed(1)} hours</strong></div>
                <div><span>Booking type</span><strong>${escapeHtml(payload.booking_type || '—')}</strong></div>
                <div><span>Reservation status</span><strong>${escapeHtml(payload.reservation_status)}</strong></div>
                <div><span>Payment status</span><strong>${payload.payment_status === 'paid' ? 'Paid' : 'Unpaid'}</strong></div>
            </div>`;
        card.querySelector('.rp-expand-button').addEventListener('click', event => {
            const button = event.currentTarget;
            const expanded = button.getAttribute('aria-expanded') === 'true';
            button.setAttribute('aria-expanded', String(!expanded));
            button.setAttribute('aria-label', expanded ? 'Show reservation details' : 'Hide reservation details');
            card.classList.toggle('is-expanded', !expanded);
            document.getElementById('rp-reservation-details').hidden = expanded;
        });
        document.getElementById('rp-time-range').innerHTML = `<span>Start <b>${escapeHtml(payload.start)}</b></span><span>End <b>${escapeHtml(payload.end)}</b></span>`;
        document.getElementById('rp-requester').textContent = payload.requester;
        document.getElementById('rp-requester-email').textContent = payload.bill_to_email || 'Email not available';
        document.getElementById('rp-invoice-ref').textContent = `${payload.reference} · ${payload.date || ''}`;
        document.getElementById('rp-invoice-resource').textContent = `${payload.resource} · ${payload.location}`;
        document.getElementById('rp-reserved-duration').textContent = `${Number(payload.hours).toFixed(1)} hours`;
        document.getElementById('rp-actual-duration').textContent = `${(Number(payload.hours) + Number(payload.extra_hours || 0)).toFixed(1)} hours`;
    }

    function clearReservationCard(title = 'Search to load a reservation', detail = 'Reservation details and usage will appear here.') {
        const card = document.getElementById('rp-reservation');
        card.classList.add('rp-empty-reservation');
        card.classList.remove('is-expanded');
        card.innerHTML = `<div class="rp-reservation-summary"><span class="rp-resource-icon"><i class="ti ti-building-community"></i></span><div class="rp-resource-copy"><strong>${escapeHtml(title)}</strong><span>${escapeHtml(detail)}</span></div><span class="rp-date"><small>STATUS</small><b>${reservation ? 'Reservation loaded' : 'Awaiting lookup'}</b></span></div>`;
    }

    function resetLoadedReservation() {
        reservation = null;
        usageConfirmed = false;
        savedConfirmedHours = 0;
        hoursInput.value = '0';
        document.getElementById('rp-time-range').innerHTML = '<span>Start <b>—</b></span><span>End <b>—</b></span>';
        document.getElementById('rp-requester').textContent = 'Reservation user';
        document.getElementById('rp-requester-email').textContent = '';
        document.getElementById('rp-invoice-ref').textContent = 'Search a reservation to begin';
        document.getElementById('rp-invoice-resource').textContent = '';
        document.getElementById('rp-invoice-id').textContent = 'DRAFT';
        document.getElementById('rp-issue-date').textContent = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        document.getElementById('rp-reserved-duration').textContent = '—';
        document.getElementById('rp-actual-duration').textContent = '—';
        clearReservationCard();
        renderInvoice();
    }

    async function refreshBreakdown() {
        if (!reservation || reservation.payment_status === 'paid') return;
        const requestSequence = ++feeRequestSequence;
        const params = new URLSearchParams({ reference: reservation.reference, extra_hours: hoursInput.value || '0' });
        try {
            const response = await fetchJson(form.action + '?' + params.toString(), { headers: jsonHeaders() });
            const payload = await response.json();
            if (!response.ok) throw new Error((payload.errors && Object.values(payload.errors).flat()[0]) || payload.message || 'Fee calculation failed.');
            if (requestSequence !== feeRequestSequence) return;
            reservation = { ...reservation, ...payload };
            usageConfirmed = Boolean(payload.is_usage_confirmed);
            savedConfirmedHours = Number(payload.confirmed_extra_hours ?? payload.extra_hours ?? 0);
            setMessage(payload.rate_note || 'Invoice breakdown updated.', payload.has_rate ? 'success' : 'warning');
        } catch (error) {
            if (requestSequence !== feeRequestSequence) return;
            setMessage(error.message, 'error');
            showFlash(error.message);
        }
        renderInvoice();
    }

    searchInput.addEventListener('input', () => {
        searchInput.classList.remove('is-invalid');
        if (message.classList.contains('is-error')) setMessage('');
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        const reference = normalizeReference();
        if (!referencePattern.test(reference)) {
            resetLoadedReservation();
            searchInput.classList.add('is-invalid');
            const invalid = 'Enter a reservation reference in the format RRS-YYYY-000000 or HST-YYYY-000000.';
            setMessage(invalid, 'error');
            searchInput.focus();
            showFlash(invalid);
            return;
        }

        feeRequestSequence++;
        resetLoadedReservation();
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        button.innerHTML = '<span class="rp-spinner" aria-hidden="true"></span> Finding…';
        form.setAttribute('aria-busy', 'true');
        document.getElementById('rp-reservation').setAttribute('aria-busy', 'true');
        setMessage('Searching reservations…');
        try {
            const response = await fetchJson(form.action + '?reference=' + encodeURIComponent(reference), { headers: jsonHeaders() });
            const payload = await response.json();
            if (!response.ok) {
                const error = new Error((payload.errors && Object.values(payload.errors).flat()[0]) || payload.message || 'Could not find a reservation.');
                error.status = response.status;
                throw error;
            }
            reservation = payload;
            usageConfirmed = Boolean(payload.is_usage_confirmed);
            savedConfirmedHours = Number(payload.confirmed_extra_hours ?? payload.extra_hours ?? 0);
            hoursInput.value = Number(payload.extra_hours || 0).toString();
            renderReservation(payload);
            renderInvoice();
            const usageDisclosure = document.getElementById('rp-usage-disclosure');
            if (Number(payload.extra_hours || 0) > 0) usageDisclosure.open = true;
            setMessage(payload.rate_note || `Reservation loaded. Invoice ${payload.invoice_number} is ${payload.payment_status}.`, payload.has_rate ? 'success' : 'warning');
            showFlash(payload.has_rate ? 'Reservation loaded.' : (payload.rate_note || 'Reservation loaded.'), payload.has_rate ? 'success' : 'warning');
        } catch (error) {
            resetLoadedReservation();
            const notFound = error.status === 404;
            const title = notFound ? 'Reservation not found' : (error.status === 409 ? 'Invoice unavailable' : 'Could not load reservation');
            const detail = error instanceof TypeError ? 'Network error. Check your connection and try again.' : error.message;
            clearReservationCard(title, detail);
            setMessage(detail, 'error');
            showFlash(detail);
        } finally {
            button.disabled = false;
            button.innerHTML = '<i class="ti ti-search"></i> Find';
            form.removeAttribute('aria-busy');
            document.getElementById('rp-reservation').removeAttribute('aria-busy');
        }
    });

    hoursInput.addEventListener('input', () => {
        const extraHours = Number(hoursInput.value);
        usageConfirmed = extraHours <= 0 || extraHours === savedConfirmedHours;
        updateStepState();
        if (!hoursInput.validity.valid) {
            document.getElementById('rp-usage-message').textContent = 'Enter between 0 and 24 hours in 0.5 hour increments.';
            document.getElementById('rp-usage-message').classList.add('is-error');
            renderInvoice();
            return;
        }
        document.getElementById('rp-usage-message').textContent = '';
        document.getElementById('rp-usage-message').classList.remove('is-error');
        document.getElementById('rp-actual-duration').textContent = `${(Number(reservation?.hours || 0) + extraHours).toFixed(1)} hours`;
        renderInvoice();
        refreshBreakdown();
    });

    document.getElementById('rp-hours-up').addEventListener('click', () => {
        hoursInput.value = Math.min(24, (Number(hoursInput.value) || 0) + 0.5);
        hoursInput.dispatchEvent(new Event('input', { bubbles: true }));
    });
    document.getElementById('rp-hours-down').addEventListener('click', () => {
        hoursInput.value = Math.max(0, (Number(hoursInput.value) || 0) - 0.5);
        hoursInput.dispatchEvent(new Event('input', { bubbles: true }));
    });

    confirmUsageButton.addEventListener('click', async () => {
        if (!reservation || Number(hoursInput.value) <= 0 || !hoursInput.reportValidity()) return;
        feeRequestSequence++;
        confirmUsageButton.disabled = true;
        confirmUsageButton.textContent = 'Recording…';
        try {
            const response = await fetchJson(config.confirmUrl, {
                method: 'POST',
                headers: jsonHeaders({ 'Content-Type': 'application/json' }),
                body: JSON.stringify({ reference: reservation.reference, extra_hours: hoursInput.value }),
            });
            const payload = await response.json();
            if (!response.ok) throw new Error((payload.errors && Object.values(payload.errors).flat()[0]) || payload.message || 'Could not confirm usage.');
            reservation = { ...reservation, ...payload, payment_status: payload.payment_status || reservation.payment_status };
            usageConfirmed = true;
            savedConfirmedHours = Number(payload.confirmed_extra_hours ?? payload.extra_hours ?? 0);
            renderInvoice();
            setMessage(payload.message, 'success');
            showFlash(payload.message || 'Usage confirmed.', 'success');
        } catch (error) {
            document.getElementById('rp-usage-message').textContent = error.message;
            document.getElementById('rp-usage-message').classList.add('is-error');
            showFlash(error.message);
        } finally {
            confirmUsageButton.textContent = 'Confirm usage';
            renderInvoice();
        }
    });

    markPaidButton?.addEventListener('click', async () => {
        if (!reservation || reservation.payment_status === 'paid') return;
        if (!window.ResourcePaymentRecord) return;
        feeRequestSequence++;
        markPaidButton.disabled = true;
        try {
            const url = markPaidButton.dataset.urlTemplate.replace('__REFERENCE__', encodeURIComponent(reservation.reference));
            const payload = await window.ResourcePaymentRecord.open({
                invoiceNumber: reservation.invoice_number,
                total: reservation.total,
                url,
                extraHours: Number(hoursInput.value || 0),
            });
            if (!payload) { markPaidButton.disabled = false; return; }
            reservation = { ...reservation, ...payload, payment_status: 'paid', has_rate: true };
            usageConfirmed = true;
            renderInvoice();
            setMessage(payload.message, 'success');
        } catch (error) {
            markPaidButton.disabled = false;
            renderInvoice();
            setMessage(error.message, 'error');
            showFlash(error.message);
        }
    });

    printButton.addEventListener('click', () => {
        if (!reservation) return;
        const url = form.dataset.printTemplate.replace('__REFERENCE__', encodeURIComponent(reservation.reference)) + '?extra_hours=' + encodeURIComponent(hoursInput.value || '0');
        window.open(url, '_blank', 'noopener');
    });

    renderInvoice();
})();
