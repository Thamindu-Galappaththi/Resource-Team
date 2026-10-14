(function () {
    document.querySelectorAll('.rd-auto-alert').forEach((alert) => {
        setTimeout(() => {
            alert.style.transition = 'opacity .3s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
})();
