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

async function refreshPendingQueue(url) {
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

        current.replaceWith(next);
        history.replaceState(null, '', url);
    } catch (error) {
        if (error.name === 'AbortError') return;
        document.getElementById('cd-pending-results')?.classList.remove('is-loading');
        showCanteenFlash(error.message || 'Unable to load pending requests.');
    }
}

document.addEventListener('pointerdown', (event) => {
    const button = event.target.closest('.cp-page .btn');

    if (!button || button.disabled || button.closest('[data-cp-confirm], .cp-modal')) return;

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

document.querySelectorAll('.cp-auto-alert').forEach((alert) => dismissCanteenAlert(alert));