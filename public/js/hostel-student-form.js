(function () {
    const form = document.getElementById('student-hostel-form');
    const checkIn = document.getElementById('check_in_date');
    const checkOut = document.getElementById('check_out_date');
    if (!form) return;

    function syncCheckoutMin() {
        if (!checkIn?.value || !checkOut) return;
        checkOut.min = checkIn.value;
        if (checkOut.value && checkOut.value < checkIn.value) checkOut.value = checkIn.value;
    }

    checkIn?.addEventListener('change', syncCheckoutMin);
    syncCheckoutMin();

    form.addEventListener('input', (event) => {
        if (event.target.classList?.contains('is-invalid') && event.target.checkValidity()) {
            event.target.classList.remove('is-invalid');
        }
    });

    let submitting = false;
    form.addEventListener('submit', (event) => {
        if (submitting) {
            event.preventDefault();
            return;
        }
        submitting = true;
        const button = form.querySelector('[type="submit"]');
        if (button) {
            button.disabled = true;
            button.textContent = 'Submitting…';
        }
    });

    form.querySelector('.is-invalid')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
})();
