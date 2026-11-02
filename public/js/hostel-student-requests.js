(function () {
    const filters = document.getElementById('hsr-filters');
    const search = document.getElementById('search');
    let searchTimer = null;

    search?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => filters.requestSubmit(), 500);
    });
    document.getElementById('status')?.addEventListener('change', () => filters.requestSubmit());

    document.getElementById('hsr-copy')?.addEventListener('click', async (event) => {
        const button = event.currentTarget;
        const input = document.getElementById(button.dataset.copyTarget);
        const label = button.querySelector('span');
        if (!input) return;
        try {
            await navigator.clipboard.writeText(input.value);
        } catch {
            input.select();
            document.execCommand('copy');
        }
        if (label) {
            label.textContent = 'Copied';
            setTimeout(() => { label.textContent = 'Copy link'; }, 2000);
        }
    });

    const modal = document.getElementById('hsrDecision');
    modal?.addEventListener('show.bs.modal', (event) => {
        const trigger = event.relatedTarget;
        if (!trigger) return;
        const form = modal.querySelector('form');
        const note = modal.querySelector('textarea');
        const status = trigger.dataset.hsrStatus;
        const needsNote = status !== 'approved';

        form.action = trigger.dataset.hsrAction;
        form.querySelector('input[name="status"]').value = status;
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

    document.querySelectorAll('.hr-auto-alert').forEach((alert) => {
        setTimeout(() => {
            alert.style.transition = 'opacity .3s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 6000);
    });
})();
