(function () {
    const checkIn = document.getElementById('check_in_date');
    const checkOut = document.getElementById('check_out_date');
    const guests = document.getElementById('number_of_guests');
    const employeeList = document.querySelector('[data-hc-employee-list]');
    const guestPhone = document.getElementById('guest_phone');

    function dismissAlert(alert, delay = 5000) {
        if (!alert || alert.classList.contains('d-none')) return;
        clearTimeout(alert._hcTimer);
        alert._hcTimer = setTimeout(() => {
            alert.style.transition = 'opacity .3s ease';
            alert.style.opacity = '0';
            setTimeout(() => {
                if (alert.id === 'hc-flash') {
                    alert.classList.add('d-none');
                    alert.style.opacity = '';
                    alert.textContent = '';
                } else {
                    alert.remove();
                }
            }, 300);
        }, delay);
    }

    function syncCheckoutMin() {
        if (!checkIn || !checkOut || !checkIn.value) return;
        checkOut.min = checkIn.value;
        if (checkOut.value && checkOut.value < checkIn.value) {
            checkOut.value = checkIn.value;
        }
    }

    function employeeRow(index, total, value) {
        const col = document.createElement('div');
        col.className = 'col-12 col-sm-6';
        col.dataset.hcEmployeeRow = '';

        const label = document.createElement('label');
        label.className = 'form-label';
        label.htmlFor = `employee_id_${index}`;
        label.textContent = total > 1 ? `Employee ID (guest ${index + 1})` : 'Employee ID';

        const input = document.createElement('input');
        input.type = 'text';
        input.id = `employee_id_${index}`;
        input.name = 'employee_ids[]';
        input.className = 'form-control';
        input.maxLength = 50;
        input.autocomplete = 'off';
        input.placeholder = '010456';
        input.required = true;
        input.value = value || '';

        col.append(label, input);
        return col;
    }

    function syncEmployeeIds() {
        if (!guests || !employeeList) return;
        const max = Number(guests.max) || 20;
        const count = Math.min(max, Math.max(1, Number.parseInt(guests.value, 10) || 1));
        const rows = [...employeeList.querySelectorAll('[data-hc-employee-row]')];
        const values = rows.map((row) => row.querySelector('input')?.value || '');
        if (rows.length === count) {
            rows.forEach((row, index) => {
                const label = row.querySelector('label');
                if (label) label.textContent = count > 1 ? `Employee ID (guest ${index + 1})` : 'Employee ID';
            });
            return;
        }
        employeeList.replaceChildren(...Array.from({ length: count }, (_, index) => employeeRow(index, count, values[index])));
    }

    const form = document.getElementById('hostel-booking-form');

    function selectMessage(select) {
        const label = form.querySelector(`label[for="${select.querySelector('input[type="hidden"]')?.id}"]`);
        const name = (label?.textContent || 'an option').replace('*', '').trim().toLowerCase();
        return `Please select ${name}.`;
    }

    function setSelectError(select, message) {
        select.classList.toggle('is-invalid', Boolean(message));
        select.querySelector('.cu-dropdown-toggle')?.classList.toggle('is-invalid', Boolean(message));
        let note = select.parentElement.querySelector('.invalid-feedback');
        if (!message) {
            note?.remove();
            return;
        }
        if (!note) {
            note = document.createElement('div');
            note.className = 'invalid-feedback d-block';
            note.dataset.hcClientError = '';
            select.insertAdjacentElement('afterend', note);
        }
        note.textContent = message;
    }

    function setFieldClientError(field, message) {
        if (!field) return;
        field.classList.toggle('is-invalid', Boolean(message));
        let note = field.parentElement?.querySelector('.invalid-feedback');
        if (!message) {
            if (note && note.dataset.hcClientError !== undefined) {
                note.remove();
            }
            field.setCustomValidity('');
            return;
        }
        field.setCustomValidity(message);
        if (!note) {
            note = document.createElement('div');
            note.className = 'invalid-feedback d-block';
            note.dataset.hcClientError = '';
            field.insertAdjacentElement('afterend', note);
        }
        note.textContent = message;
    }

    function validateGuestPhone(isSubmitting = false) {
        if (!guestPhone) return true;
        const raw = guestPhone.value.trim();
        if (!raw) {
            setFieldClientError(guestPhone, '');
            return true;
        }
        if (/[a-zA-Z]/.test(raw)) {
            setFieldClientError(guestPhone, 'The phone number cannot contain letters.');
            return false;
        }

        const digits = raw.replace(/\D/g, '');
        const isPlus94 = raw.startsWith('+94') || (digits.startsWith('94') && digits.length > 10);
        const effectiveDigits = isPlus94 ? digits.replace(/^94/, '') : digits;
        const requiredDigits = isPlus94 ? 9 : 10;

        if (effectiveDigits.length < requiredDigits) {
            const hasError = guestPhone.parentElement?.querySelector('.invalid-feedback[data-hc-client-error]');
            if (isSubmitting || hasError) {
                setFieldClientError(guestPhone, 'The phone number is incomplete.');
                return false;
            }
            setFieldClientError(guestPhone, '');
            return true;
        }

        if (effectiveDigits.length > requiredDigits) {
            setFieldClientError(guestPhone, 'The phone number cannot be more than 10 digits.');
            return false;
        }

        setFieldClientError(guestPhone, '');
        return true;
    }

    function invalidFields() {
        const invalid = [];
        if (guestPhone && !validateGuestPhone(true)) {
            invalid.push(guestPhone);
        }
        form.querySelectorAll('[data-cu-select], input:not([type="hidden"]), textarea, select').forEach((field) => {
            if (field === guestPhone) return;
            if (field.matches('[data-cu-select]')) {
                const required = field.querySelector('.cu-dropdown-toggle')?.getAttribute('aria-required') === 'true';
                const empty = !(field.querySelector('input[type="hidden"]')?.value || '').trim();
                setSelectError(field, required && empty ? selectMessage(field) : '');
                if (required && empty) invalid.push(field);
                return;
            }
            if (field.closest('[data-cu-select]') || field.disabled) return;
            const ok = field.checkValidity();
            field.classList.toggle('is-invalid', !ok);
            if (!ok) invalid.push(field);
        });
        return invalid;
    }

    function revealField(field) {
        field.scrollIntoView({ behavior: 'smooth', block: 'center' });
        const focusTarget = field.matches('[data-cu-select]') ? field.querySelector('.cu-dropdown-toggle') : field;
        setTimeout(() => {
            focusTarget?.focus({ preventScroll: true });
            if (!field.matches('[data-cu-select]')) field.reportValidity();
        }, 350);
    }

    if (form) {
        form.noValidate = true;
        form.addEventListener('submit', (event) => {
            const invalid = invalidFields();
            if (invalid.length === 0) return;
            event.preventDefault();
            revealField(invalid[0]);
        });
        form.addEventListener('input', (event) => {
            if (event.target.classList?.contains('is-invalid') && event.target.checkValidity()) {
                event.target.classList.remove('is-invalid');
            }
        });
        form.addEventListener('change', (event) => {
            const select = event.target.closest?.('[data-cu-select]');
            if (select && event.target.value) setSelectError(select, '');
        });

        const serverError = form.querySelector('.is-invalid, .invalid-feedback.d-block');
        if (serverError) {
            const field = serverError.closest('[data-cu-select]')
                || serverError.closest('[class*="col-"]')?.querySelector('[data-cu-select], input:not([type="hidden"]), textarea')
                || serverError;
            field.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    checkIn?.addEventListener('change', syncCheckoutMin);
    guests?.addEventListener('input', syncEmployeeIds);
    guests?.addEventListener('change', syncEmployeeIds);
    guestPhone?.addEventListener('input', () => validateGuestPhone(false));
    guestPhone?.addEventListener('change', () => validateGuestPhone(true));
    guestPhone?.addEventListener('blur', () => validateGuestPhone(true));
    syncCheckoutMin();
    syncEmployeeIds();
    document.querySelectorAll('.hc-auto-alert').forEach((alert) => dismissAlert(alert));
})();
