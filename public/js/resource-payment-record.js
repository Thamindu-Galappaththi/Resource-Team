(function () {
    const money = value => 'LKR ' + Number(value || 0).toLocaleString('en-LK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    window.ResourcePaymentRecord = {
        async open({ invoiceNumber, total, url, extraHours = 0 }) {
            const safeInvoice = String(invoiceNumber || 'Draft invoice').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
            const localNow = new Date();
            const today = `${localNow.getFullYear()}-${String(localNow.getMonth() + 1).padStart(2, '0')}-${String(localNow.getDate()).padStart(2, '0')}`;
            const result = await Swal.fire({
                customClass: { popup: 'rp-payment-modal', title: 'rp-payment-modal__title', htmlContainer: 'rp-payment-modal__content', confirmButton: 'rp-payment-modal__confirm', cancelButton: 'rp-payment-modal__cancel', actions: 'rp-payment-modal__actions' },
                title: 'Record payment',
                iconHtml: '<i class="ti ti-receipt-2" aria-hidden="true"></i>',
                html: `<div class="rpm-summary"><span>${safeInvoice}</span><strong>${money(total)}</strong></div>
                    <div class="rpm-field"><span class="rpm-label">Payment method</span><div class="rpm-methods" role="radiogroup" aria-label="Payment method">
                    <button type="button" class="rpm-method is-selected" data-method="cash" role="radio" aria-checked="true"><i class="ti ti-cash-banknote"></i><span>Cash</span><b>✓</b></button>
                    <button type="button" class="rpm-method" data-method="bank_transfer" role="radio" aria-checked="false"><i class="ti ti-building-bank"></i><span>Bank transfer</span><b>✓</b></button></div></div>
                    <label class="rpm-field"><span class="rpm-label">Reference number <em id="rpm-required" hidden>Required</em></span><input id="rpm-reference" class="rpm-input" maxlength="120" placeholder="Receipt no. (optional)" autocomplete="off"><small id="rpm-reference-error" class="rpm-error" aria-live="polite"></small></label>
                    <label class="rpm-field"><span class="rpm-label">Payment date</span><input id="rpm-date" class="rpm-input" type="date" value="${today}" max="${today}"><small id="rpm-date-error" class="rpm-error" aria-live="polite"></small></label>`,
                showCancelButton: true,
                confirmButtonText: 'Mark as paid',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                buttonsStyling: false,
                showCloseButton: true,
                allowEscapeKey: true,
                focusConfirm: false,
                didOpen: popup => {
                    const methods = [...popup.querySelectorAll('.rpm-method')];
                    const reference = popup.querySelector('#rpm-reference');
                    const required = popup.querySelector('#rpm-required');
                    methods.forEach(button => button.addEventListener('click', () => {
                        methods.forEach(item => { const selected = item === button; item.classList.toggle('is-selected', selected); item.setAttribute('aria-checked', String(selected)); });
                        const bank = button.dataset.method === 'bank_transfer';
                        required.hidden = !bank;
                        reference.placeholder = bank ? 'Bank transaction reference' : 'Receipt no. (optional)';
                        popup.querySelector('#rpm-reference-error').textContent = '';
                    }));
                },
                preConfirm: async () => {
                    const popup = Swal.getPopup();
                    const method = popup.querySelector('.rpm-method.is-selected').dataset.method;
                    const reference = popup.querySelector('#rpm-reference').value.trim();
                    const paymentDate = popup.querySelector('#rpm-date').value;
                    const referenceError = popup.querySelector('#rpm-reference-error');
                    const dateError = popup.querySelector('#rpm-date-error');
                    referenceError.textContent = '';
                    dateError.textContent = '';
                    if (method === 'bank_transfer' && !reference) {
                        referenceError.textContent = 'Enter the bank transaction reference.';
                        popup.querySelector('#rpm-reference').focus();
                        return false;
                    }
                    if (!paymentDate || paymentDate > today) {
                        dateError.textContent = 'Choose today or an earlier payment date.';
                        popup.querySelector('#rpm-date').focus();
                        return false;
                    }
                    const confirm = Swal.getConfirmButton();
                    const cancel = Swal.getCancelButton();
                    confirm.disabled = true;
                    cancel.disabled = true;
                    Swal.showLoading();
                    confirm.classList.add('is-loading');
                    try {
                        const response = await fetch(url, {
                            method: 'POST', credentials: 'same-origin',
                            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                            body: JSON.stringify({ method, reference_number: reference || null, payment_date: paymentDate, extra_hours: Number(extraHours || 0) }),
                        });
                        const payload = await response.json();
                        if (!response.ok) throw new Error((payload.errors && Object.values(payload.errors).flat()[0]) || payload.message || 'Payment could not be recorded.');
                        return payload;
                    } catch (error) {
                        Swal.hideLoading();
                        confirm.disabled = false;
                        cancel.disabled = false;
                        confirm.classList.remove('is-loading');
                        popup.classList.remove('rpm-shake');
                        void popup.offsetWidth;
                        popup.classList.add('rpm-shake');
                        Swal.showValidationMessage(error.message);
                        return false;
                    }
                },
            });
            if (!result.isConfirmed) return null;
            await Swal.fire({
                customClass: { popup: 'rp-payment-modal rpm-success', title: 'rp-payment-modal__title' },
                icon: 'success', title: 'Payment Recorded Successfully', showConfirmButton: false, timer: 2500, timerProgressBar: true,
                didOpen: popup => {
                    window.setTimeout(() => {
                        if (Swal.getPopup() === popup) Swal.close();
                    }, 2500);
                },
            });
            return result.value;
        },
    };
})();
