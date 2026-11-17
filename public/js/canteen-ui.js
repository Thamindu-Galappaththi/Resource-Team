function dismissCanteenAlert(alert, delay = 5000) {
    if (!alert || alert.classList.contains('d-none')) return;

    clearTimeout(alert._cpTimer);
    alert._cpTimer = setTimeout(() => {
        alert.style.transition = 'opacity .3s ease';
        alert.style.opacity = '0';
        setTimeout(() => {
            if (alert.id === 'cp-flash' || alert.id === 'cd-flash') {
                alert.classList.add('d-none');
                alert.style.opacity = '';
                alert.textContent = '';
            } else {
                alert.remove();
            }
        }, 300);
    }, delay);
}

function showCanteenFlash(message, type = 'danger') {
    const flash = document.getElementById('cd-flash') || document.getElementById('cp-flash');
    if (!flash || !message) return;

    flash.className = `alert alert-${type} cp-auto-alert`;
    flash.textContent = message;
    flash.style.opacity = '1';
    dismissCanteenAlert(flash);
}

let pendingRequest = null;
const dashboardLiveIds = ['cd-kpis', 'cd-pending-count', 'cd-pending-results', 'cd-glance', 'cd-meals', 'cd-forecast'];

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('#cd-review-form [name="_token"]')?.value
        || '';
}

function swapLiveNode(id, page) {
    const current = document.getElementById(id);
    const next = page.getElementById(id);
    if (current && next) current.replaceWith(next);
}

async function refreshDashboard(url, { stats = false } = {}) {
    const current = document.getElementById('cd-pending-results');
    if (!current) return;

    pendingRequest?.abort();
    pendingRequest = new AbortController();
    current.classList.add('is-loading');

    try {
        const response = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
            signal: pendingRequest.signal,
            credentials: 'same-origin',
        });
        if (!response.ok) throw new Error('Unable to load pending requests.');

        const page = new DOMParser().parseFromString(await response.text(), 'text/html');
        const next = page.querySelector('#cd-pending-results');
        if (!next) throw new Error('Unable to load pending requests.');

        if (stats) {
            dashboardLiveIds.forEach((id) => swapLiveNode(id, page));
        } else {
            current.replaceWith(next);
        }

        history.replaceState(null, '', url);

        const results = document.getElementById('cd-pending-results');
        const pageNo = Number(new URL(url, window.location.origin).searchParams.get('pending_page') || 1);
        if (stats && results && !results.querySelector('.cd-request') && pageNo > 1) {
            const previous = new URL(url, window.location.origin);
            previous.searchParams.set('pending_page', String(pageNo - 1));
            await refreshDashboard(`${previous.pathname}?${previous.searchParams}#pending-requests`, { stats: true });
        }
    } catch (error) {
        if (error.name === 'AbortError') return;
        document.getElementById('cd-pending-results')?.classList.remove('is-loading');
        showCanteenFlash(error.message || 'Unable to load pending requests.');
    }
}

function refreshPendingQueue(url) {
    return refreshDashboard(url);
}

document.addEventListener('pointerdown', (event) => {
    const button = event.target.closest('.cp-page .btn');

    if (!button || button.disabled || button.classList.contains('cu-dropdown-toggle') || button.closest('[data-cp-confirm], .cp-modal, [data-cu-select]')) return;

    const bounds = button.getBoundingClientRect();
    const diameter = Math.max(bounds.width, bounds.height) * 1.8;
    const ripple = document.createElement('span');

    ripple.className = 'cp-ripple';
    ripple.style.width = `${diameter}px`;
    ripple.style.height = `${diameter}px`;
    ripple.style.left = `${event.clientX - bounds.left - diameter / 2}px`;
    ripple.style.top = `${event.clientY - bounds.top - diameter / 2}px`;
    button.append(ripple);
    ripple.addEventListener('animationend', () => ripple.remove(), { once: true });
});

document.addEventListener('change', (event) => {
    const field = event.target.closest('.cp-page .form-control, .cp-page .form-select');

    if (!field) return;

    field.classList.remove('cp-selected');
    requestAnimationFrame(() => field.classList.add('cp-selected'));
    window.setTimeout(() => field.classList.remove('cp-selected'), 500);
});

document.addEventListener('click', (event) => {
    const pendingPage = event.target.closest('#cd-pending-results .pagination a');

    if (pendingPage) {
        event.preventDefault();
        refreshPendingQueue(pendingPage.href);
        return;
    }

    const reviewTrigger = event.target.closest('[data-cd-review]');

    if (reviewTrigger) {
        event.preventDefault();
        openCanteenReviewModal(reviewTrigger);
        return;
    }

    const confirmTrigger = event.target.closest('[data-cp-confirm]');

    if (confirmTrigger) {
        event.preventDefault();
        const modalEl = document.getElementById('cp-confirm-modal');
        const form = modalEl?.querySelector('#cp-confirm-form');
        if (!modalEl || !form) return;

        form.action = confirmTrigger.dataset.cpAction || '';
        const title = modalEl.querySelector('#cp-confirm-title');
        const sub = modalEl.querySelector('#cp-confirm-sub');
        const copy = modalEl.querySelector('#cp-confirm-copy');
        const submit = modalEl.querySelector('#cp-confirm-submit');
        const icon = modalEl.querySelector('#cp-confirm-icon i');

        if (title) title.textContent = confirmTrigger.dataset.cpTitle || 'Please confirm';
        if (sub) {
            sub.textContent = confirmTrigger.dataset.cpSub || '';
            sub.hidden = !sub.textContent;
        }
        if (copy) copy.textContent = confirmTrigger.dataset.cpCopy || 'This cannot be undone.';
        if (submit) submit.textContent = confirmTrigger.dataset.cpSubmit || 'Confirm';
        if (icon) icon.className = confirmTrigger.dataset.cpIcon || 'ti ti-trash';

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
        return;
    }

    const toggle = event.target.closest('[data-reject-toggle]');

    if (toggle) {
        const form = document.querySelector(toggle.dataset.rejectToggle);

        if (!form) return;

        form.hidden = false;
        requestAnimationFrame(() => form.classList.add('is-open'));
        toggle.setAttribute('aria-expanded', 'true');
        form.querySelector('textarea')?.focus();

        return;
    }

    const close = event.target.closest('[data-reject-close]');

    if (!close) return;

    const form = close.closest('.cd-reject-form');
    const toggleButton = form && document.querySelector(`[data-reject-toggle="#${form.id}"]`);

    if (!form) return;

    form.classList.remove('is-open');
    form.hidden = true;
    toggleButton?.setAttribute('aria-expanded', 'false');
    toggleButton?.focus();
});

function openCanteenReviewModal(trigger) {
    const modalEl = document.getElementById('cd-review-modal');
    const form = document.getElementById('cd-review-form');
    if (!modalEl || !form) return;

    const status = trigger.dataset.cdStatus || 'approved';
    const rejecting = status === 'rejected';
    form.action = trigger.dataset.cdAction || '';
    form.dataset.saving = '';

    const statusInput = form.querySelector('#cd-review-status');
    const title = modalEl.querySelector('#cd-review-title');
    const sub = modalEl.querySelector('#cd-review-sub');
    const copy = modalEl.querySelector('#cd-review-copy');
    const submit = modalEl.querySelector('#cd-review-submit');
    const icon = modalEl.querySelector('#cd-review-icon i');
    const reasonWrap = modalEl.querySelector('#cd-review-reason-wrap');
    const reason = modalEl.querySelector('#cd-review-reason');
    const reasonError = modalEl.querySelector('#cd-review-reason-error');

    if (statusInput) statusInput.value = status;
    if (title) title.textContent = trigger.dataset.cdTitle || 'Review reservation?';
    if (sub) {
        sub.textContent = trigger.dataset.cdSub || '';
        sub.hidden = !sub.textContent;
    }
    if (copy) copy.textContent = trigger.dataset.cdCopy || 'Please confirm this decision.';
    if (submit) {
        submit.textContent = trigger.dataset.cdSubmit || 'Confirm';
        submit.className = rejecting ? 'btn btn-danger' : 'btn btn-success';
        submit.disabled = false;
    }
    if (icon) icon.className = trigger.dataset.cdIcon || (rejecting ? 'ti ti-circle-x' : 'ti ti-circle-check');
    if (reasonWrap) reasonWrap.hidden = !rejecting;
    if (reason) {
        reason.value = '';
        reason.required = rejecting;
        reason.classList.remove('is-invalid');
    }
    if (reasonError) reasonError.textContent = '';

    bootstrap.Modal.getOrCreateInstance(modalEl).show();
    if (rejecting) window.setTimeout(() => reason?.focus(), 200);
}

async function submitCanteenReview(event) {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || form.id !== 'cd-review-form') return;

    event.preventDefault();
    if (form.dataset.saving === '1') return;

    const status = form.querySelector('#cd-review-status')?.value;
    const reason = form.querySelector('#cd-review-reason');
    const reasonError = form.querySelector('#cd-review-reason-error');
    const submit = form.querySelector('#cd-review-submit');

    if (status === 'rejected' && !reason?.value.trim()) {
        reason?.classList.add('is-invalid');
        if (reasonError) reasonError.textContent = 'Add a reason before rejecting this request.';
        reason?.focus();
        return;
    }

    form.dataset.saving = '1';
    if (submit) submit.disabled = true;

    try {
        const body = new FormData(form);
        body.append('_method', 'PATCH');
        const response = await fetch(form.action, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
            },
            body,
            credentials: 'same-origin',
        });
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            const message = payload?.errors?.approval_comments?.[0]
                || payload?.message
                || 'Unable to update this reservation.';
            throw new Error(message);
        }

        bootstrap.Modal.getInstance(document.getElementById('cd-review-modal'))?.hide();
        if (document.getElementById('cd-pending-results')) {
            showCanteenFlash(payload.message || 'Reservation updated.', 'success');
            await refreshDashboard(window.location.href, { stats: true });
            return;
        }
        window.location.reload();
    } catch (error) {
        if (status === 'rejected' && reasonError) {
            reason?.classList.add('is-invalid');
            reasonError.textContent = error.message;
        } else {
            showCanteenFlash(error.message, 'danger');
        }
    } finally {
        form.dataset.saving = '';
        if (submit) submit.disabled = false;
    }
}

document.addEventListener('submit', submitCanteenReview);

document.querySelectorAll('.cp-auto-alert').forEach((alert) => dismissCanteenAlert(alert));