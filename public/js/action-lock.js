(function () {
    const skipForm = 'form[data-action-lock="off"], .rsv-filters, #ap-filters, #rsv-filters, #um-filters, .hr-filters, #hr-filters, .cp-filters';
    const skipButton = '[data-bs-dismiss], [data-bs-toggle="dropdown"], [data-bs-toggle="modal"], [data-bs-toggle="tab"], .rsv-filter-clear, .um-filter-clear, .hr-filter-clear, .rc-filter-clear';

    function rootOf(scope) {
        if (!scope) return null;
        if (scope instanceof HTMLFormElement) return scope;
        if (scope.form instanceof HTMLFormElement) return scope.form;
        return scope.closest?.('form') || scope;
    }

    function formMethod(form) {
        const spoof = form.querySelector?.('input[name="_method"]')?.value;
        return String(spoof || form.getAttribute('method') || 'get').toLowerCase();
    }

    function shouldLockForm(form) {
        if (!(form instanceof HTMLFormElement) || form.matches(skipForm)) return false;
        return !['get', 'head'].includes(formMethod(form));
    }

    function isActionButton(button) {
        if (!(button instanceof HTMLButtonElement) && !(button instanceof HTMLInputElement)) return false;
        if (button.matches(skipButton)) return false;
        const type = (button.getAttribute('type') || (button instanceof HTMLButtonElement ? 'submit' : button.type || '')).toLowerCase();
        if (type === 'reset' || type === 'button') return button.matches('[data-action-lock]');
        return type === 'submit' || type === 'image' || button.hasAttribute('form');
    }

    function actionButtons(root) {
        if (!root) return [];
        const selector = 'button, input[type="submit"], input[type="button"], input[type="image"]';
        if (!(root instanceof HTMLFormElement)) {
            return [...root.querySelectorAll(selector)];
        }
        const nodes = [];
        root.querySelectorAll(selector).forEach((button) => {
            if (isActionButton(button)) nodes.push(button);
        });
        if (root.id) {
            document.querySelectorAll(`[form="${root.id}"]`).forEach((button) => {
                if (isActionButton(button) && !nodes.includes(button)) nodes.push(button);
            });
        }
        return nodes;
    }

    function keepSubmitter(form, submitter) {
        if (!(form instanceof HTMLFormElement) || !submitter || !submitter.name) return;
        let input = form.querySelector('input[data-rm-submitter]');
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.dataset.rmSubmitter = '1';
            form.appendChild(input);
        }
        input.name = submitter.name;
        input.value = submitter.value;
    }

    function disableButtons(root) {
        actionButtons(root).forEach((button) => {
            if (!button.dataset.rmWasDisabled) {
                button.dataset.rmWasDisabled = button.disabled ? '1' : '0';
            }
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
        });
    }

    function lock(scope) {
        const root = rootOf(scope);
        if (!root) return;
        root.dataset.rmBusy = '1';
        root.classList.add('is-busy');
        disableButtons(root);
    }

    function unlock(scope) {
        const root = rootOf(scope);
        if (!root) return;
        delete root.dataset.rmBusy;
        delete root.dataset.rmAjax;
        root.classList.remove('is-busy');
        actionButtons(root).forEach((button) => {
            if (button.dataset.rmWasDisabled !== '1') {
                button.disabled = false;
            }
            button.removeAttribute('aria-busy');
            delete button.dataset.rmWasDisabled;
        });
    }

    async function around(scope, work) {
        const root = rootOf(scope);
        if (root) root.dataset.rmAjax = '1';
        lock(scope);
        try {
            return await work();
        } finally {
            unlock(scope);
        }
    }

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!shouldLockForm(form)) return;
        if (event.defaultPrevented) return;
        if (form.dataset.rmBusy === '1') {
            event.preventDefault();
            return;
        }
        keepSubmitter(form, event.submitter);
        form.dataset.rmBusy = '1';
        form.classList.add('is-busy');
        setTimeout(() => {
            if (form.dataset.rmBusy === '1') disableButtons(form);
        }, 0);
    });

    window.RMAction = { lock, unlock, around };
})();
