document.addEventListener('DOMContentLoaded', function () {
    var bell = document.querySelector('.nb-bell');
    if (!bell) return;

    var csrf = document.querySelector('meta[name="csrf-token"]');

    bell.addEventListener('click', function (event) {
        var clearButton = event.target.closest('.nb-clear-all');
        if (clearButton) {
            if (clearButton.disabled) return;
            clearButton.disabled = true;
            fetch(bell.dataset.clearAllUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf ? csrf.content : '',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            }).then(function (response) {
                if (!response.ok) throw new Error('Could not clear notifications.');
                return response.json();
            }).then(function (data) {
                bell.querySelectorAll('.nb-item').forEach(function (item) { item.remove(); });
                var empty = document.createElement('div');
                empty.className = 'nb-empty';
                empty.innerHTML = '<i class="ti ti-bell-off"></i><span>You\'re all caught up.</span>';
                bell.querySelector('.nb-list').appendChild(empty);
                clearButton.remove();
                updateUnreadCount(data.unread_count);
            }).catch(function () {
                clearButton.disabled = false;
                clearButton.title = 'Could not clear notifications. Try again.';
            });
            return;
        }

        var button = event.target.closest('.nb-status');
        if (!button) return;

        var item = button.closest('.nb-item');
        if (!item || button.disabled) return;

        var markUnread = !item.classList.contains('is-unread');
        button.disabled = true;

        fetch(markUnread ? item.dataset.unreadUrl : item.dataset.readUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrf ? csrf.content : '',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        }).then(function (response) {
            if (!response.ok) throw new Error('Could not update notification status.');
            return response.json();
        }).then(function (data) {
            item.classList.toggle('is-unread', markUnread);
            var icon = button.querySelector('.ti');
            icon.classList.toggle('ti-mail', markUnread);
            icon.classList.toggle('ti-mail-opened', !markUnread);
            var label = markUnread ? 'Mark as read' : 'Mark as unread';
            button.setAttribute('aria-label', label);
            button.title = label;
            updateUnreadCount(data.unread_count);
        }).catch(function () {
            button.title = 'Could not update. Try again.';
        }).finally(function () {
            button.disabled = false;
        });
    });

    function updateUnreadCount(count) {
        var badge = bell.querySelector('.nb-badge');
        var headingCount = bell.querySelector('.nb-count');
        var toggle = bell.querySelector('.nb-toggle');
        var iconWrap = bell.querySelector('.nb-icon-wrap') || toggle;

        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'nb-badge';
                iconWrap.appendChild(badge);
            }
            badge.textContent = count > 9 ? '9+' : count;
            if (!headingCount) {
                headingCount = document.createElement('span');
                headingCount.className = 'nb-count';
                bell.querySelector('.nb-heading').appendChild(headingCount);
            }
            headingCount.textContent = count + ' new';
        } else {
            if (badge) badge.remove();
            if (headingCount) headingCount.remove();
        }

        toggle.setAttribute('aria-label', 'Notifications' + (count > 0 ? ' (' + count + ' unread)' : ''));
    }
});
