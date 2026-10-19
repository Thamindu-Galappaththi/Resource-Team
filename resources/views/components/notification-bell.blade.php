<li class="nav-item dropdown nb-bell" data-clear-all-url="{{ route('notifications.clear-all') }}">
    <a class="nav-link nav-icon-hover nb-toggle" href="javascript:void(0)" id="notificationsDropdown" role="button"
        data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
        aria-label="Notifications{{ $unreadNotificationCount > 0 ? ' ('.$unreadNotificationCount.' unread)' : '' }}">
        <span class="nb-icon-wrap">
            <i class="ti ti-bell"></i>
            @if ($unreadNotificationCount > 0)
                <span class="nb-badge">{{ $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount }}</span>
            @endif
        </span>
    </a>
    <div class="dropdown-menu dropdown-menu-end nb-menu" aria-labelledby="notificationsDropdown">
        <div class="nb-heading">
            <span>Notifications</span>
            <div class="nb-heading-actions">
                @if ($unreadNotificationCount > 0)
                    <span class="nb-count">{{ $unreadNotificationCount }} new</span>
                @endif
                @if ($headerNotifications->isNotEmpty())
                    <button type="button" class="nb-clear-all">Clear all</button>
                @endif
            </div>
        </div>
        <div class="nb-list">
            @forelse ($headerNotifications as $notification)
                <div class="nb-item {{ $notification['unread'] ? 'is-unread' : '' }}" data-notification-id="{{ $notification['id'] }}"
                    data-read-url="{{ route('notifications.read', $notification['id']) }}"
                    data-unread-url="{{ route('notifications.unread', $notification['id']) }}"
                    data-delete-url="{{ route('notifications.destroy', $notification['id']) }}">
                    @if ($notification['url'])
                        <a class="nb-content" href="{{ $notification['url'] }}">
                    @else
                        <span class="nb-content">
                    @endif
                    <span class="nb-icon"><i class="ti {{ $notification['icon'] }}"></i></span>
                    <span class="nb-copy">
                        <span class="nb-message">{{ $notification['message'] }}</span>
                        <small class="nb-time">{{ $notification['time'] }}</small>
                    </span>
                    @if ($notification['url'])</a>@else</span>@endif
                    <button type="button" class="nb-status" aria-label="{{ $notification['unread'] ? 'Mark as read' : 'Mark as unread' }}"
                        title="{{ $notification['unread'] ? 'Mark as read' : 'Mark as unread' }}">
                        <i class="ti {{ $notification['unread'] ? 'ti-mail' : 'ti-mail-opened' }}"></i>
                    </button>
                    <button type="button" class="nb-delete" aria-label="Delete notification" title="Delete notification">
                        <i class="ti ti-trash"></i>
                    </button>
                </div>
            @empty
                <div class="nb-empty">
                    <i class="ti ti-bell-off"></i>
                    <span>You're all caught up.</span>
                </div>
            @endforelse
        </div>
    </div>
</li>
