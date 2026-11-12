(function () {
    function flash(text, type) {
        if (typeof showCanteenFlash === 'function') {
            showCanteenFlash(text, type);
            return;
        }
        const el = document.getElementById('cp-flash');
        if (!el || !text) return;
        el.className = `alert alert-${type} cp-auto-alert`;
        el.textContent = text;
        el.classList.remove('d-none');
    }

    function firstError(payload) {
        if (payload?.errors) {
            const item = Object.values(payload.errors).flat()[0];
            if (item) return item;
        }
        return payload?.message || 'Could not publish the menu.';
    }

    function resetDrop(input, drop) {
        if (input) input.value = '';
        drop?.classList.remove('has-file', 'is-over');
        const preview = drop?.querySelector('img');
        if (preview) preview.removeAttribute('src');
    }

    function bindPicker(inputId) {
        const input = document.getElementById(inputId);
        const drop = document.querySelector('[data-mm-drop]');
        if (!input || !drop) return;

        const preview = drop.querySelector('img');
        const showFile = (file) => {
            if (!file || !preview) return;
            preview.src = URL.createObjectURL(file);
            drop.classList.add('has-file');
        };

        input.addEventListener('change', () => showFile(input.files?.[0]));
        ['dragenter', 'dragover'].forEach((type) => drop.addEventListener(type, (event) => {
            event.preventDefault();
            drop.classList.add('is-over');
        }));
        ['dragleave', 'drop'].forEach((type) => drop.addEventListener(type, (event) => {
            event.preventDefault();
            drop.classList.remove('is-over');
        }));
        drop.addEventListener('drop', (event) => {
            const file = event.dataTransfer?.files?.[0];
            if (!file) return;
            const transfer = new DataTransfer();
            transfer.items.add(file);
            input.files = transfer.files;
            showFile(file);
        });

        document.querySelectorAll('[data-mm-sample]').forEach((button) => {
            button.addEventListener('click', async () => {
                try {
                    const response = await fetch(button.dataset.mmSample);
                    const blob = await response.blob();
                    const file = new File([blob], button.dataset.mmName || 'menu.jpg', { type: blob.type || 'image/jpeg' });
                    const transfer = new DataTransfer();
                    transfer.items.add(file);
                    input.files = transfer.files;
                    showFile(file);
                } catch (error) {
                    flash('Could not load that sample photo. Choose a file instead.', 'danger');
                }
            });
        });
    }

    function bindPublish() {
        const form = document.getElementById('mm-add-form');
        if (!form) return;
        const submit = document.getElementById('mm-publish');
        const name = form.querySelector('#name');
        const image = form.querySelector('#image');
        const drop = form.querySelector('[data-mm-drop]');
        let saving = false;

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (saving) return;
            if (!name?.value.trim()) {
                name?.classList.add('is-invalid');
                name?.focus();
                return;
            }
            if (!image?.files?.length) {
                flash('Choose a menu photo before publishing.', 'danger');
                return;
            }
            saving = true;
            if (submit) submit.disabled = true;
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    credentials: 'same-origin',
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(firstError(payload));
                const gallery = document.getElementById('mm-gallery');
                const empty = document.getElementById('mm-empty');
                const count = document.getElementById('mm-count');
                if (gallery && payload.html) {
                    empty?.classList.add('d-none');
                    gallery.classList.remove('d-none');
                    gallery.insertAdjacentHTML('afterbegin', payload.html);
                }
                if (count && payload.count != null) {
                    const n = Number(payload.count);
                    count.textContent = `${n} ${n === 1 ? 'menu' : 'menus'} available for reservations`;
                }
                form.reset();
                resetDrop(image, drop);
                name?.classList.remove('is-invalid');
                flash(payload.message || 'Meal menu published.', 'success');
            } catch (error) {
                flash(error.message, 'danger');
            } finally {
                saving = false;
                if (submit) submit.disabled = false;
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        bindPicker('image');
        bindPublish();
    });
})();
