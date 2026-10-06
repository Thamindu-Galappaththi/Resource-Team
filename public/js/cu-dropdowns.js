(function () {
    function trapMenuScroll(menu) {
        if (menu.dataset.cuScrollBound === 'true') {
            return;
        }

        menu.dataset.cuScrollBound = 'true';
        menu.addEventListener('wheel', (event) => {
            event.stopPropagation();
            const atTop = menu.scrollTop <= 0 && event.deltaY < 0;
            const atBottom = menu.scrollTop + menu.clientHeight >= menu.scrollHeight - 1 && event.deltaY > 0;
            if (atTop || atBottom || menu.scrollHeight <= menu.clientHeight) {
                event.preventDefault();
            }
        }, { passive: false });
        menu.addEventListener('touchmove', (event) => {
            event.stopPropagation();
        }, { passive: true });
    }

    document.querySelectorAll('.cu-dropdown-menu').forEach(trapMenuScroll);

    document.addEventListener('shown.bs.dropdown', (event) => {
        const menu = event.target.querySelector('.cu-dropdown-menu');
        if (menu) {
            trapMenuScroll(menu);
        }
    });
})();
