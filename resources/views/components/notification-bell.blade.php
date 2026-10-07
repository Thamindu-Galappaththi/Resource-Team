<li class="nav-item dropdown nb-bell" data-mark-read-url="{{ route('notifications.mark-read') }}">
    <a class="nav-link nav-icon-hover nb-toggle" href="javascript:void(0)" id="notificationsDropdown" role="button"
        data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
        aria-label="Notifications{{ $unreadNotificationCount > 0 ? ' ('.$unreadNotificationCount.' unread)' : '' }}">
        <i class="ti ti-bell"></i>
        @if ($unreadNotificationCount > 0)
            <span class="nb-badge">{{ $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount }}</span>
        @endif
    </a>
    <div class="dropdown-menu dropdown-menu-end nb-menu" aria-labelledby="notificationsDropdown">
        <div class="nb-heading">
            <span>Notifications</span>
            @if ($unreadNotificationCount > 0)
                <span class="nb-count">{{ $unreadNotificationCount }} new</span>
            @endif
        </div>
        <div class="nb-list">
            @forelse ($headerNotifications as $notification)
                <a class="nb-item {{ $notification['unread'] ? 'is-unread' : '' }}"
                    @if ($notification['url']) href="{{ $notification['url'] }}" @endif>
                    <span class="nb-icon"><i class="ti {{ $notification['icon'] }}"></i></span>
                    <span class="nb-copy">
                        <span class="nb-message">{{ $notification['message'] }}</span>
                        <small class="nb-time">{{ $notification['time'] }}</small>
                    </span>
                </a>
            @empty
                <div class="nb-empty">
                    <i class="ti ti-bell-off"></i>
                    <span>You're all caught up.</span>
                </div>
            @endforelse
        </div>
    </div>
</li>
