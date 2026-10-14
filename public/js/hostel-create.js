(function () {
    const checkIn = document.getElementById('check_in_date');
    const checkOut = document.getElementById('check_out_date');

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

    function nextDay(value) {
        if (!value) return '';
        const date = new Date(`${value}T00:00:00`);
        if (Number.isNaN(date.getTime())) return '';
        date.setDate(date.getDate() + 1);
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${date.getFullYear()}-${month}-${day}`;
    }

    function syncCheckoutMin() {
        if (!checkIn || !checkOut) return;
        const min = nextDay(checkIn.value);
        if (!min) return;
        checkOut.min = min;
        if (checkOut.value && checkOut.value <= checkIn.value) {
            checkOut.value = min;
        }
    }

    checkIn?.addEventListener('change', syncCheckoutMin);
    syncCheckoutMin();
    document.querySelectorAll('.hc-auto-alert').forEach((alert) => dismissAlert(alert));
})();
