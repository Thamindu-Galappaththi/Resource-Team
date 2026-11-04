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
        input.pattern = '[a-zA-Z0-9][a-zA-Z0-9\\-\\/]*';
        input.title = 'The employee ID cannot contain special characters.';
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
        if (raw.includes('-')) {
            setFieldClientError(guestPhone, 'The phone number cannot contain a negative sign.');
            return false;
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

    function validateEmployeeIdInput(input) {
        if (!input) return true;
        const val = input.value.trim();
        if (!val) {
            setFieldClientError(input, '');
            return true;
        }

        if (val.startsWith('-')) {
            setFieldClientError(input, 'The employee ID cannot be a negative value.');
            return false;
        }

        if (/[@#$%^&*!~+=<>[\]{}|;:",?]/.test(val) || !/^[a-zA-Z0-9][a-zA-Z0-9\-\/]*$/.test(val)) {
            setFieldClientError(input, 'The employee ID cannot contain special characters.');
            return false;
        }

        setFieldClientError(input, '');
        return true;
    }

    function getSelectedCapacity() {
        const locationInput = document.getElementById('location_id') || form?.querySelector('input[name="location_id"]');
        const bedGroupInput = document.getElementById('bed_group') || form?.querySelector('input[name="bed_group"]');
        const locationId = locationInput?.value;
        const bedGroup = bedGroupInput?.value;
        if (!locationId || !bedGroup || !window.hostelCapacities) return null;
        const locationCounts = window.hostelCapacities[String(locationId)];
        if (!locationCounts || locationCounts[bedGroup] === undefined) return null;
        return Number(locationCounts[bedGroup]);
    }

    function validateGuestCount() {
        if (!guests) return true;
        const count = Number.parseInt(guests.value, 10);
        if (Number.isNaN(count) || count < 1) {
            setFieldClientError(guests, 'Please enter a valid number of guests.');
            return false;
        }
        const capacity = getSelectedCapacity();
        if (capacity !== null && count > capacity) {
            setFieldClientError(guests, `The number of guests exceeds the room capacity of ${capacity}.`);
            return false;
        }
        setFieldClientError(guests, '');
        return true;
    }

    function invalidFields() {
        const invalid = [];
        if (guestPhone && !validateGuestPhone(true)) {
            invalid.push(guestPhone);
        }
        if (guests && !validateGuestCount()) {
            invalid.push(guests);
        }
        form.querySelectorAll('input[name="employee_ids[]"]').forEach((input) => {
            if (!validateEmployeeIdInput(input)) {
                invalid.push(input);
            }
        });
        form.querySelectorAll('[data-cu-select], input:not([type="hidden"]), textarea, select').forEach((field) => {
            if (field === guestPhone || field.name === 'employee_ids[]' || field === guests) return;
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
        let isSubmitting = false;
        form.noValidate = true;
        form.addEventListener('submit', (event) => {
            if (isSubmitting) {
                event.preventDefault();
                return false;
            }
            const invalid = invalidFields();
            if (invalid.length > 0) {
                event.preventDefault();
                revealField(invalid[0]);
                return;
            }
            isSubmitting = true;
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.style.pointerEvents = 'none';
                submitBtn.classList.add('disabled');
                const isEditing = submitBtn.textContent.includes('Update');
                submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>${isEditing ? 'Updating reservation...' : 'Creating reservation...'}`;
                setTimeout(() => {
                    submitBtn.disabled = true;
                }, 0);
            }
        });
        form.addEventListener('input', (event) => {
            if (event.target.classList?.contains('is-invalid') && event.target.checkValidity()) {
                event.target.classList.remove('is-invalid');
            }
        });
        form.addEventListener('change', (event) => {
            const select = event.target.closest?.('[data-cu-select]');
            if (select && event.target.value) setSelectError(select, '');
            if (['location_id', 'bed_group'].includes(event.target.id) || event.target.name === 'location_id' || event.target.name === 'bed_group') {
                validateGuestCount();
            }
        });

        const serverError = form.querySelector('.is-invalid, .invalid-feedback.d-block');
        if (serverError) {
            const field = serverError.closest('[data-cu-select]')
                || serverError.closest('[class*="col-"]')?.querySelector('[data-cu-select], input:not([type="hidden"]), textarea')
                || serverError;
            field.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    employeeList?.addEventListener('input', (event) => {
        if (event.target?.name === 'employee_ids[]') validateEmployeeIdInput(event.target);
    });
    employeeList?.addEventListener('change', (event) => {
        if (event.target?.name === 'employee_ids[]') validateEmployeeIdInput(event.target);
    });
    employeeList?.addEventListener('blur', (event) => {
        if (event.target?.name === 'employee_ids[]') validateEmployeeIdInput(event.target);
    }, true);
    checkIn?.addEventListener('change', syncCheckoutMin);
    guests?.addEventListener('input', () => {
        syncEmployeeIds();
        validateGuestCount();
    });
    guests?.addEventListener('change', () => {
        syncEmployeeIds();
        validateGuestCount();
    });
    guestPhone?.addEventListener('input', () => validateGuestPhone(false));
    guestPhone?.addEventListener('change', () => validateGuestPhone(true));
    guestPhone?.addEventListener('blur', () => validateGuestPhone(true));
    syncCheckoutMin();
    syncEmployeeIds();
    document.querySelectorAll('.hc-auto-alert').forEach((alert) => dismissAlert(alert));
})();
