(function () {
    const form = document.getElementById('hsr-filters');
    const search = document.getElementById('search');
    const placeholders = { status: 'All statuses' };
    let request = null;
    let searchTimer = null;

    function dismissAlert(alert, delay = 6000) {
        if (!alert || alert.classList.contains('d-none')) return;
        clearTimeout(alert._hsrTimer);
        alert._hsrTimer = setTimeout(() => {
            alert.style.transition = 'opacity .3s ease';
            alert.style.opacity = '0';
            setTimeout(() => {
                if (alert.id === 'hsr-flash') {
                    alert.classList.add('d-none');
                    alert.style.opacity = '';
                    alert.textContent = '';
                } else {
                    alert.remove();
                }
            }, 300);
        }, delay);
    }

    function showFlash(message, type = 'danger') {
        const flash = document.getElementById('hsr-flash');
        if (!flash || !message) return;
        flash.className = `alert alert-${type} hr-auto-alert`;
        flash.textContent = message;
        flash.style.opacity = '1';
        dismissAlert(flash);
    }

    function currentParams() {
        const params = {};
        ['search', 'status'].forEach((id) => {
            const value = (document.getElementById(id)?.value || '').trim();
            if (value) params[id] = value;
        });
        return params;
    }

    function toggleClear() {
        const clear = document.getElementById('hsr-clear');
        if (clear) clear.disabled = Object.keys(currentParams()).length === 0;
        const searchClear = document.getElementById('hsr-search-clear');
        if (searchClear) searchClear.hidden = !(search?.value || '').length;
    }

    function setLoading(isLoading) {
        document.getElementById('hsr-results')?.classList.toggle('is-loading', isLoading);
        const overlay = document.getElementById('hsr-loading');
        if (overlay) overlay.hidden = !isLoading;
    }

    function filterUrl() {
        const url = new URL(form.action, window.location.origin);
        url.search = new URLSearchParams(currentParams()).toString();
        return url.toString();
    }

    async function refresh(url) {
        request?.abort();
        request = new AbortController();
        setLoading(true);
        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'text/html' },
                signal: request.signal,
                credentials: 'same-origin',
            });
            if (!response.ok) throw new Error('Unable to load student requests.');
            const page = new DOMParser().parseFromString(await response.text(), 'text/html');
            ['hsr-results', 'hsr-modals'].forEach((id) => {
                const next = page.getElementById(id);
                const current = document.getElementById(id);
                if (!next || !current) throw new Error('The student requests could not be updated.');
                current.replaceWith(next);
            });
            history.replaceState(null, '', url);
            toggleClear();
        } catch (error) {
            if (error.name === 'AbortError') return;
            showFlash(error.message || 'Unable to load student requests.');
        } finally {
            setLoading(false);
        }
    }

    form?.addEventListener('submit', (event) => {
        event.preventDefault();
        refresh(filterUrl());
    });

    search?.addEventListener('input', () => {
        toggleClear();
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => refresh(filterUrl()), 350);
    });

    document.getElementById('hsr-search-clear')?.addEventListener('click', () => {
        if (!search) return;
        search.value = '';
        clearTimeout(searchTimer);
        toggleClear();
        search.focus();
        refresh(filterUrl());
    });

    search?.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && search.value) {
            event.preventDefault();
            document.getElementById('hsr-search-clear')?.click();
        }
    });

    document.getElementById('status')?.addEventListener('change', () => {
        toggleClear();
        refresh(filterUrl());
    });

    document.getElementById('hsr-clear')?.addEventListener('click', () => {
        if (search) search.value = '';
        Object.entries(placeholders).forEach(([id, label]) => window.RMSelect?.setValue(id, '', label));
        toggleClear();
        refresh(form.action);
    });

    document.addEventListener('click', (event) => {
        const pageLink = event.target.closest?.('#hsr-results .pagination a');
        if (!pageLink) return;
        event.preventDefault();
        refresh(pageLink.href);
        document.getElementById('hsr-results')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    function copied(text) {
        if (window.RMSwal && window.Swal) {
            window.RMSwal.fire({
                icon: 'success',
                title: 'Link copied',
                html: 'Paste it in WhatsApp or any message to share it with students.',
                timer: 2200,
                timerProgressBar: true,
                showConfirmButton: false,
                customClass: { ...window.RMSwal.base.customClass, container: 'rm-swal-backdrop' },
            });
            return;
        }
        showFlash(`Link copied: ${text}`, 'success');
    }

    async function copyText(text) {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
            return;
        }
        const helper = document.createElement('textarea');
        helper.value = text;
        helper.setAttribute('readonly', '');
        helper.style.position = 'fixed';
        helper.style.opacity = '0';
        document.body.appendChild(helper);
        helper.select();
        const ok = document.execCommand('copy');
        helper.remove();
        if (!ok) throw new Error('copy failed');
    }

    document.getElementById('hsr-copy')?.addEventListener('click', async (event) => {
        const input = document.getElementById(event.currentTarget.dataset.copyTarget);
        if (!input) return;
        try {
            await copyText(input.value);
            copied(input.value);
        } catch {
            input.select();
            window.RMSwal?.fire({ icon: 'info', title: 'Copy the link', html: 'Press Ctrl+C (or long-press on mobile) to copy the selected link.' });
        }
    });

    const modal = document.getElementById('hsrDecision');
    modal?.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;
        if (!trigger) return;
        const decisionForm = modal.querySelector('form');
        const note = modal.querySelector('textarea');
        const status = trigger.dataset.hsrStatus;
        const needsNote = status !== 'approved';

        decisionForm.action = trigger.dataset.hsrAction;
        decisionForm.querySelector('input[name="status"]').value = status;
        modal.querySelector('.modal-title').textContent = `${trigger.dataset.hsrLabel} request?`;
        modal.querySelector('[data-hsr-sub]').textContent = `${trigger.dataset.hsrRef} · ${trigger.dataset.hsrStudent}`;
        modal.querySelector('[data-hsr-note-label]').textContent = needsNote ? 'Reason for the student (required)' : 'Note for the student (optional)';
        modal.querySelector('[data-hsr-sms]').textContent = `An SMS with this decision will be sent to ${trigger.dataset.hsrPhone}.`;
        note.value = '';
        note.required = needsNote;

        const submit = modal.querySelector('[data-hsr-submit]');
        submit.textContent = { approved: 'Confirm approval', rejected: 'Confirm rejection', cancelled: 'Confirm cancellation' }[status] || 'Confirm';
        submit.className = `btn ${status === 'approved' ? 'btn-success' : status === 'rejected' ? 'btn-danger' : 'btn-secondary'}`;
    });
    modal?.addEventListener('shown.bs.modal', () => modal.querySelector('textarea')?.focus());

    document.querySelectorAll('.hr-auto-alert').forEach((alert) => dismissAlert(alert));
    toggleClear();
})();
