document.addEventListener('pointerdown', (event) => {
    const button = event.target.closest('.cp-page .btn');

    if (!button || button.disabled) return;

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