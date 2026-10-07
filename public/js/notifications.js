document.addEventListener('DOMContentLoaded', function () {
    var bell = document.querySelector('.nb-bell');
    if (!bell) return;

    var toggle = bell.querySelector('.nb-toggle');
    var csrf = document.querySelector('meta[name="csrf-token"]');
    var marking = false;

    toggle.addEventListener('shown.bs.dropdown', function () {
        if (marking || !bell.querySelector('.nb-badge')) return;
        marking = true;

        fetch(bell.dataset.markReadUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf ? csrf.content : '',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        }).then(function (response) {
            if (!response.ok) return;

            var badge = bell.querySelector('.nb-badge');
            var count = bell.querySelector('.nb-count');
            if (badge) badge.remove();
            if (count) count.remove();
            toggle.setAttribute('aria-label', 'Notifications');
        }).finally(function () {
            marking = false;
        });
    });

    // Keep unread rows highlighted while the menu is open so the user can see what was new.
    toggle.addEventListener('hidden.bs.dropdown', function () {
        if (bell.querySelector('.nb-badge')) return;
        bell.querySelectorAll('.nb-item.is-unread').forEach(function (item) {
            item.classList.remove('is-unread');
        });
    });
});
