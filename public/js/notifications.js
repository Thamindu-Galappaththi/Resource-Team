document.addEventListener('DOMContentLoaded', function () {
    var bell = document.querySelector('.nb-bell');
    if (!bell) return;

    var csrf = document.querySelector('meta[name="csrf-token"]');
    var list = bell.querySelector('.nb-list');
    var seenIds = {};
    var activeSwipe = null;
    var pollTimer = null;

    bell.querySelectorAll('.nb-item').forEach(function (item) {
        if (item.dataset.notificationId) seenIds[item.dataset.notificationId] = true;
    });

    requestPushPermission();
    bell.addEventListener('shown.bs.dropdown', requestPushPermission);

    bell.addEventListener('pointerdown', function (event) {
        if (event.pointerType !== 'touch') return;
        var item = event.target.closest('.nb-item');
        if (!item || event.target.closest('button')) return;

        activeSwipe = {
            item: item,
            pointerId: event.pointerId,
            startX: event.clientX,
            startY: event.clientY,
        };
    });

    bell.addEventListener('pointerup', function (event) {
        if (!activeSwipe || activeSwipe.pointerId !== event.pointerId) return;

        var swipe = activeSwipe;
        activeSwipe = null;
        var deltaX = event.clientX - swipe.startX;
        var deltaY = event.clientY - swipe.startY;

        if (deltaX < 80 || deltaX < Math.abs(deltaY) * 1.3) return;
        dismissNotification(swipe.item);
    });

    bell.addEventListener('pointercancel', function () {
        activeSwipe = null;
    });

    bell.addEventListener('click', function (event) {
        var clearButton = event.target.closest('.nb-clear-all');
        if (clearButton) {
            event.preventDefault();
            if (clearButton.disabled) return;
            clearButton.disabled = true;
            requestJson(bell.dataset.clearAllUrl, 'POST').then(function (data) {
                bell.querySelectorAll('.nb-item').forEach(function (item) { item.remove(); });
                seenIds = {};
                showEmptyState();
                updateUnreadCount(data.unread_count);
            }).catch(function () {
                clearButton.disabled = false;
                clearButton.title = 'Could not clear notifications. Try again.';
            });
            return;
        }

        var deleteButton = event.target.closest('.nb-delete');
        if (deleteButton) {
            event.preventDefault();
            dismissNotification(deleteButton.closest('.nb-item'));
            return;
        }

        var statusButton = event.target.closest('.nb-status');
        if (statusButton) {
            event.preventDefault();
            toggleReadState(statusButton);
            return;
        }

        var content = event.target.closest('.nb-content');
        if (!content) return;

        var item = content.closest('.nb-item');
        if (!item) return;

        // Let the anchor perform normal browser navigation. Intercepting it and
        // assigning window.location can fail when the dropdown or another page
        // handler also processes the same click.
        if (item.classList.contains('is-unread') && item.dataset.readUrl) {
            requestJson(item.dataset.readUrl, 'POST').catch(function () {});
        }

        if (content.tagName !== 'A' && item.dataset.url) {
            window.location.assign(item.dataset.url);
        }
    });

    var feedInFlight = false;
    pollTimer = window.setInterval(pollFeed, 15000);
    window.setTimeout(pollFeed, 4000);
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') pollFeed();
    });

    function headers() {
        return {
            'X-CSRF-TOKEN': csrf ? csrf.content : '',
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        };
    }

    function requestJson(url, method) {
        return fetch(url, {
            method: method,
            headers: headers(),
            credentials: 'same-origin',
            keepalive: true,
        }).then(function (response) {
            if (!response.ok) throw new Error('Request failed');
            return response.json();
        });
    }

    function pollFeed() {
        if (!bell.dataset.feedUrl || feedInFlight || document.visibilityState === 'hidden') return;

        feedInFlight = true;
        fetch(bell.dataset.feedUrl, {
            headers: headers(),
            credentials: 'same-origin',
            cache: 'no-store',
        }).then(function (response) {
            if (!response.ok) throw new Error('Could not load notifications.');
            return response.json();
        }).then(function (data) {
            var incoming = Array.isArray(data.notifications) ? data.notifications : [];
            var fresh = [];
            incoming.slice().reverse().forEach(function (notification) {
                if (!notification.id || seenIds[notification.id]) return;
                seenIds[notification.id] = true;
                prependItem(notification);
                fresh.push(notification);
            });
            updateUnreadCount(data.unread_count);
            fresh.forEach(announceArrival);
        }).catch(function () {}).finally(function () {
            feedInFlight = false;
        });
    }

    function prependItem(notification) {
        var empty = list.querySelector('.nb-empty');
        if (empty) empty.remove();
        ensureClearAll();

        var item = buildItem(notification);
        list.insertBefore(item, list.firstChild);
    }

    function buildItem(notification) {
        var item = document.createElement('div');
        item.className = 'nb-item' + (notification.unread ? ' is-unread' : '');
        item.dataset.notificationId = notification.id;
        item.dataset.url = notification.url || '';
        item.dataset.readUrl = notification.read_url || '';
        item.dataset.unreadUrl = notification.unread_url || '';
        item.dataset.deleteUrl = notification.delete_url || '';

        var content = document.createElement(notification.url ? 'a' : 'span');
        content.className = 'nb-content';
        if (notification.url) content.href = notification.url;

        var icon = document.createElement('span');
        icon.className = 'nb-icon';
        icon.innerHTML = '<i class="ti ' + safeIcon(notification.icon) + '"></i>';

        var copy = document.createElement('span');
        copy.className = 'nb-copy';
        copy.appendChild(textEl('span', 'nb-title', notification.title || 'Notification'));
        var message = textEl('span', 'nb-message', notification.message || 'You have a new notification.');
        if (notification.full_message) message.title = notification.full_message;
        copy.appendChild(message);
        copy.appendChild(textEl('small', 'nb-time', notification.time || 'Just now'));

        content.appendChild(icon);
        content.appendChild(copy);

        var actions = document.createElement('div');
        actions.className = 'nb-actions';
        actions.appendChild(iconButton('nb-status', notification.unread ? 'ti-mail' : 'ti-mail-opened', notification.unread ? 'Mark as read' : 'Mark as unread'));
        actions.appendChild(iconButton('nb-delete', 'ti-trash', 'Delete notification'));

        item.appendChild(content);
        item.appendChild(actions);
        return item;
    }

    function textEl(tag, className, value) {
        var el = document.createElement(tag);
        el.className = className;
        el.textContent = value;
        return el;
    }

    function iconButton(className, icon, label) {
        var button = document.createElement('button');
        button.type = 'button';
        button.className = className;
        button.setAttribute('aria-label', label);
        button.title = label;
        button.innerHTML = '<i class="ti ' + safeIcon(icon) + '"></i>';
        return button;
    }

    function safeIcon(icon) {
        return /^[a-z0-9-]+$/i.test(icon || '') ? icon : 'ti-bell';
    }

    function announceArrival(notification) {
        showBrowserPush(notification);
    }

    function showBrowserPush(notification) {
        if (!('Notification' in window) || Notification.permission !== 'granted') return;

        try {
            var toast = new Notification(notification.title || bell.dataset.appName || 'NEBULA', {
                body: notification.message || 'You have a new notification.',
                icon: bell.dataset.appIcon || undefined,
                tag: 'nb-' + notification.id,
            });
            toast.onclick = function () {
                window.focus();
                if (notification.url) window.location.href = notification.url;
                toast.close();
            };
        } catch (error) {}
    }

    function requestPushPermission() {
        if (!('Notification' in window) || Notification.permission !== 'default') return;
        Notification.requestPermission();
    }

    function toggleReadState(button) {
        var item = button.closest('.nb-item');
        if (!item || button.disabled) return;

        var markUnread = !item.classList.contains('is-unread');
        button.disabled = true;

        requestJson(markUnread ? item.dataset.unreadUrl : item.dataset.readUrl, 'POST').then(function (data) {
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
    }

    function dismissNotification(item) {
        if (!item || item.dataset.deleting === 'true') return;
        item.dataset.deleting = 'true';

        requestJson(item.dataset.deleteUrl, 'DELETE').then(function (data) {
            if (item.dataset.notificationId) delete seenIds[item.dataset.notificationId];
            item.classList.add('is-dismissing');
            window.setTimeout(function () {
                item.remove();
                if (!bell.querySelector('.nb-item')) showEmptyState();
            }, 180);
            updateUnreadCount(data.unread_count);
        }).catch(function () {
            delete item.dataset.deleting;
            item.classList.add('nb-delete-failed');
            window.setTimeout(function () { item.classList.remove('nb-delete-failed'); }, 1200);
        });
    }

    function ensureClearAll() {
        if (bell.querySelector('.nb-clear-all')) return;
        var actions = bell.querySelector('.nb-heading-actions');
        if (!actions) return;
        var button = document.createElement('button');
        button.type = 'button';
        button.className = 'nb-clear-all';
        button.textContent = 'Clear all';
        actions.appendChild(button);
    }

    function showEmptyState() {
        var clearButton = bell.querySelector('.nb-clear-all');
        if (clearButton) clearButton.remove();

        if (!list.querySelector('.nb-empty')) {
            var empty = document.createElement('div');
            empty.className = 'nb-empty';
            empty.innerHTML = '<i class="ti ti-bell-off"></i><span>You\'re all caught up.</span>';
            list.appendChild(empty);
        }
    }

    function updateUnreadCount(count) {
        var badge = bell.querySelector('.nb-badge');
        var headingCount = bell.querySelector('.nb-count');
        var toggle = bell.querySelector('.nb-toggle');
        var iconWrap = bell.querySelector('.nb-icon-wrap') || toggle;
        var actions = bell.querySelector('.nb-heading-actions');

        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'nb-badge';
                iconWrap.appendChild(badge);
            }
            badge.textContent = count > 9 ? '9+' : count;
            if (!headingCount && actions) {
                headingCount = document.createElement('span');
                headingCount.className = 'nb-count';
                actions.insertBefore(headingCount, actions.firstChild);
            }
            if (headingCount) headingCount.textContent = count + ' new';
        } else {
            if (badge) badge.remove();
            if (headingCount) headingCount.remove();
        }

        toggle.setAttribute('aria-label', 'Notifications' + (count > 0 ? ' (' + count + ' unread)' : ''));
    }

    window.addEventListener('beforeunload', function () {
        if (pollTimer) window.clearInterval(pollTimer);
    });
});
