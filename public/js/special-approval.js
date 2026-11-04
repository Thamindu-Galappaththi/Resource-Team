(function () {
    document.querySelectorAll('[data-special-approval]').forEach((box) => {
        const checkbox = box.querySelector('input[type="checkbox"][name="special_approval"]');
        const docs = box.querySelector('[data-special-docs]');
        const input = box.querySelector('input[type="file"]');
        const picked = box.querySelector('[data-special-docs-picked]');
        const error = box.querySelector('[data-special-docs-error]');
        if (!checkbox || !docs || !input) return;

        const maxFiles = Number(input.dataset.maxFiles || 5);
        const maxBytes = Number(input.dataset.maxKb || 5120) * 1024;

        function setError(message) {
            input.classList.toggle('is-invalid', Boolean(message));
            if (!error) return;
            error.textContent = message || '';
            error.classList.toggle('d-block', Boolean(message));
        }

        function sizeLabel(bytes) {
            return bytes >= 1048576 ? `${(bytes / 1048576).toFixed(1)} MB` : `${Math.max(1, Math.round(bytes / 1024))} KB`;
        }

        function validate() {
            const files = [...input.files];
            if (files.length > maxFiles) return `Attach up to ${maxFiles} supporting documents.`;
            const tooBig = files.find((file) => file.size > maxBytes);
            return tooBig ? `${tooBig.name} is larger than 5 MB.` : '';
        }

        function renderPicked() {
            if (!picked) return;
            const files = [...input.files];
            picked.hidden = !files.length;
            picked.replaceChildren(...files.map((file) => {
                const item = document.createElement('li');
                item.innerHTML = '<i class="ti ti-paperclip" aria-hidden="true"></i>';
                const name = document.createElement('span');
                name.textContent = file.name;
                const size = document.createElement('span');
                size.className = 'text-muted';
                size.textContent = sizeLabel(file.size);
                item.append(name, size);
                return item;
            }));
        }

        checkbox.addEventListener('change', () => {
            docs.hidden = !checkbox.checked;
            input.disabled = !checkbox.checked;
            if (!checkbox.checked) setError('');
        });
        input.disabled = !checkbox.checked;
        input.addEventListener('change', () => {
            renderPicked();
            setError(validate());
        });

        box.closest('form')?.addEventListener('submit', (event) => {
            if (!checkbox.checked) return;
            const message = validate();
            if (!message) return;
            event.preventDefault();
            event.stopImmediatePropagation();
            setError(message);
            input.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, true);
    });
})();
