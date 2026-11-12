(function () {
    const money = value => 'LKR ' + Number(value || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char]));

    function todayStamp() {
        const now = new Date();
        return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
    }

    function ensureDialog() {
        let root = document.getElementById('rp-pay-dialog');
        if (root) return root;
        root = document.createElement('div');
        root.id = 'rp-pay-dialog';
        root.className = 'rpm-overlay';
        root.hidden = true;
        root.innerHTML = `
            <div class="rpm-dialog" role="dialog" aria-modal="true" aria-labelledby="rpm-title">
                <header class="rpm-head">
                    <span class="rpm-icon" aria-hidden="true"><i class="ti ti-receipt-2"></i></span>
                    <div>
                        <p class="rpm-kicker">Resource invoicing</p>
                        <h2 id="rpm-title">Record payment</h2>
                    </div>
                    <button type="button" class="rpm-close" data-rpm-close aria-label="Close"><i class="ti ti-x" aria-hidden="true"></i></button>
                </header>
                <div class="rpm-body">
                    <div class="rpm-summary"><span id="rpm-invoice-label"></span><strong id="rpm-invoice-total"></strong></div>
                    <div class="rpm-field">
                        <span class="rpm-label">Payment method</span>
                        <div class="rpm-methods" role="radiogroup" aria-label="Payment method">
                            <button type="button" class="rpm-method is-selected" data-method="cash" role="radio" aria-checked="true"><i class="ti ti-cash-banknote"></i><span>Cash</span><b>✓</b></button>
                            <button type="button" class="rpm-method" data-method="bank_transfer" role="radio" aria-checked="false"><i class="ti ti-building-bank"></i><span>Bank transfer</span><b>✓</b></button>
                        </div>
                    </div>
                    <label class="rpm-field"><span class="rpm-label">Reference number <em id="rpm-required" hidden>Required</em></span>
                        <input id="rpm-reference" class="rpm-input" maxlength="120" placeholder="Receipt no. (optional)" autocomplete="off">
                        <small id="rpm-reference-error" class="rpm-error" aria-live="polite"></small>
                    </label>
                    <label class="rpm-field"><span class="rpm-label">Payment date</span>
                        <input id="rpm-date" class="rpm-input" type="date">
                        <small id="rpm-date-error" class="rpm-error" aria-live="polite"></small>
                    </label>
                    <p class="rpm-form-error" id="rpm-form-error" hidden></p>
                </div>
                <footer class="rpm-foot">
                    <button type="button" class="rpm-cancel" data-rpm-close>Cancel</button>
                    <button type="button" class="rpm-submit" id="rpm-submit">Mark as paid</button>
                </footer>
            </div>`;
        document.body.appendChild(root);
        return root;
    }

    function selectedMethod(root) {
        return root.querySelector('.rpm-method.is-selected')?.dataset.method || 'cash';
    }

    function setMethod(root, method) {
        root.querySelectorAll('.rpm-method').forEach(button => {
            const selected = button.dataset.method === method;
            button.classList.toggle('is-selected', selected);
            button.setAttribute('aria-checked', String(selected));
        });
        const bank = method === 'bank_transfer';
        const required = root.querySelector('#rpm-required');
        const reference = root.querySelector('#rpm-reference');
        if (required) required.hidden = !bank;
        if (reference) reference.placeholder = bank ? 'Bank transaction reference' : 'Receipt no. (optional)';
        const error = root.querySelector('#rpm-reference-error');
        if (error) error.textContent = '';
    }

    function closeDialog(root, settle, value) {
        if (root.hidden) return;
        root.hidden = true;
        root.classList.remove('is-open');
        document.body.classList.remove('rpm-lock');
        document.removeEventListener('keydown', root._onKey);
        if (typeof settle === 'function') settle(value);
    }

    function showSuccess(payload) {
        return new Promise(resolve => {
            const toast = document.createElement('div');
            toast.className = 'rpm-toast';
            toast.innerHTML = '<i class="ti ti-circle-check" aria-hidden="true"></i><strong>Payment recorded</strong>';
            document.body.appendChild(toast);
            window.setTimeout(() => {
                toast.remove();
                resolve(payload);
            }, 1600);
        });
    }

    window.ResourcePaymentRecord = {
        open({ invoiceNumber, total, url, extraHours = 0 }) {
            const root = ensureDialog();
            const today = todayStamp();
            root.querySelector('#rpm-invoice-label').textContent = invoiceNumber || 'Invoice';
            root.querySelector('#rpm-invoice-total').textContent = money(total);
            root.querySelector('#rpm-reference').value = '';
            root.querySelector('#rpm-date').value = today;
            root.querySelector('#rpm-date').max = today;
            root.querySelector('#rpm-reference-error').textContent = '';
            root.querySelector('#rpm-date-error').textContent = '';
            const formError = root.querySelector('#rpm-form-error');
            formError.hidden = true;
            formError.textContent = '';
            setMethod(root, 'cash');
            const submit = root.querySelector('#rpm-submit');
            submit.disabled = false;
            submit.classList.remove('is-loading');
            submit.textContent = 'Mark as paid';

            return new Promise(resolve => {
                let settled = false;
                const finish = value => {
                    if (settled) return;
                    settled = true;
                    closeDialog(root, resolve, value);
                };

                root.querySelectorAll('[data-rpm-close]').forEach(button => {
                    button.onclick = event => {
                        event.preventDefault();
                        event.stopPropagation();
                        finish(null);
                    };
                });
                root.onclick = event => {
                    if (event.target === root) finish(null);
                };
                root.querySelector('.rpm-dialog').onclick = event => event.stopPropagation();
                root.querySelectorAll('.rpm-method').forEach(button => {
                    button.onclick = () => setMethod(root, button.dataset.method);
                });
                root._onKey = event => {
                    if (event.key === 'Escape') {
                        event.preventDefault();
                        finish(null);
                    }
                };
                document.addEventListener('keydown', root._onKey);

                submit.onclick = async () => {
                    const method = selectedMethod(root);
                    const reference = root.querySelector('#rpm-reference').value.trim();
                    const paymentDate = root.querySelector('#rpm-date').value;
                    const referenceError = root.querySelector('#rpm-reference-error');
                    const dateError = root.querySelector('#rpm-date-error');
                    referenceError.textContent = '';
                    dateError.textContent = '';
                    formError.hidden = true;
                    if (method === 'bank_transfer' && !reference) {
                        referenceError.textContent = 'Enter the bank transaction reference.';
                        root.querySelector('#rpm-reference').focus();
                        return;
                    }
                    if (!paymentDate || paymentDate > today) {
                        dateError.textContent = 'Choose today or an earlier payment date.';
                        root.querySelector('#rpm-date').focus();
                        return;
                    }
                    submit.disabled = true;
                    submit.classList.add('is-loading');
                    submit.textContent = 'Saving…';
                    try {
                        const response = await fetch(url, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                Accept: 'application/json',
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            },
                            body: JSON.stringify({
                                method,
                                reference_number: reference || null,
                                payment_date: paymentDate,
                                extra_hours: Number(extraHours || 0),
                            }),
                        });
                        const payload = await response.json();
                        if (!response.ok) throw new Error((payload.errors && Object.values(payload.errors).flat()[0]) || payload.message || 'Payment could not be recorded.');
                        closeDialog(root);
                        settled = true;
                        await showSuccess(payload);
                        resolve(payload);
                    } catch (error) {
                        submit.disabled = false;
                        submit.classList.remove('is-loading');
                        submit.textContent = 'Mark as paid';
                        formError.hidden = false;
                        formError.textContent = error.message;
                    }
                };

                root.hidden = false;
                root.classList.add('is-open');
                document.body.classList.add('rpm-lock');
                window.setTimeout(() => root.querySelector('#rpm-reference')?.focus(), 30);
            });
        },
    };
})();
