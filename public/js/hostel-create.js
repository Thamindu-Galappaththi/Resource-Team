(function () {
    const checkIn = document.getElementById('check_in_date');
    const checkOut = document.getElementById('check_out_date');
    const guests = document.getElementById('number_of_guests');
    const employeeList = document.querySelector('[data-hc-employee-list]');

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

    checkIn?.addEventListener('change', syncCheckoutMin);
    guests?.addEventListener('input', syncEmployeeIds);
    guests?.addEventListener('change', syncEmployeeIds);
    syncCheckoutMin();
    syncEmployeeIds();
    document.querySelectorAll('.hc-auto-alert').forEach((alert) => dismissAlert(alert));
})();
