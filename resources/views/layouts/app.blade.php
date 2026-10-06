<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<!-- ϥϙϜϞϧϰαα -->

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'NEBULA')</title>
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/logos/favicon.jpg') }}" />

    <!-- Tabler Icons CSS -->
    <link rel="stylesheet" href="{{ asset('css/icons/tabler-icons/tabler-icons.css') }}">
    <!-- Bootstrap Icons (CDN) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <!-- CSS -->
    <link href="{{ asset('css/styles.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/sidebar-responsive.css') }}?v=9" rel="stylesheet">
    @stack('styles')

    <!-- JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>
    <script src="{{ asset('libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('libs/simplebar/dist/simplebar.js') }}"></script>
    <!-- Sidebar + layout interactions (hamburger toggle, responsive sidebar) -->
    <script src="{{ asset('js/app.min.js') }}?v=8"></script>
    <script src="{{ asset('js/sidebarmenu.js') }}"></script>
    <!-- Global utilities -->
    <script src="{{ asset('js/global-utilities.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            background: url('data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"%3E%3C/svg%3E') no-repeat center center fixed;
            background-size: cover;
            width: 100%;
            height: 100vh;
        }

        body.loaded {
            background-image: url('{{ asset('images/backgrounds/nebula.jpg') }}');
        }

        .navbar {
            box-shadow: 0 8px 8px -8px rgba(0, 0, 0, 0.1);
        }

        .dropdown-menu-outline-shadow {
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        }

        /* Apply the class to your dropdown menu */
        .dropdown-menu.dropdown-menu-end.dropdown-menu-animate-up.bg-light-primary.outline-shadow {
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        }
        .notification-toggle { position: relative; }
        .notification-badge { position: absolute; top: 4px; right: 3px; min-width: 9px; height: 9px; padding: 0 3px; border-radius: 10px; background: #e95778; color: #fff; font-size: 9px; line-height: 9px; }
        .notification-menu { width: min(420px, calc(100vw - 24px)); padding: 0; overflow: hidden; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 8px 24px rgba(0,0,0,.16); }
        .notification-heading { padding: 14px 18px; border-bottom: 1px solid #e5e7eb; font-weight: 600; }
        .notification-list { max-height: 360px; overflow-y: auto; }
        .notification-item { display: flex; align-items: center; gap: 14px; padding: 16px 18px; border-bottom: 1px solid #e5e7eb; background: #f7fafb; }
        .notification-item.unread { background: #eaf3f6; }
        .notification-icon { display: grid; flex: 0 0 44px; width: 44px; height: 44px; place-items: center; border-radius: 50%; background: #d4ebf2; color: #1683a3; font-size: 20px; }
        .notification-copy { min-width: 0; color: #46515b; line-height: 1.35; }
        .notification-copy small { display: block; margin-top: 6px; color: #9aa6b2; }
        .notification-empty { padding: 24px 18px; color: #697586; text-align: center; }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const body = document.querySelector('body');
            body.classList.add('loaded');
        });
    </script>
</head>

<body class="d-flex flex-column">
    

    <!--  Body Wrapper -->
    <div class="page-wrapper" id="main-wrapper" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
        data-sidebar-position="fixed" data-header-position="fixed">
        <!-- Sidebar Start -->
        <aside class="left-sidebar" id="leftSidebar">
            @include('components.sidebar')
        </aside>
        <div class="sidebar-backdrop" aria-hidden="true"></div>

        <div class="body-wrapper d-flex flex-column min-vh-100">
            <!--  Header Start -->
            <header class="app-header">
                <nav class="navbar navbar-expand-lg navbar-light">
                    <ul class="navbar-nav">
                        <li class="nav-item d-block d-lg-none">
                            <a class="nav-link sidebartoggler nav-icon-hover" id="headerCollapse"
                                href="javascript:void(0)" aria-label="Open menu" aria-controls="leftSidebar">
                                <i class="ti ti-menu-2"></i>
                            </a>
                        </li>
                    </ul>
                    <div class="navbar-collapse justify-content-end px-0" id="navbarNav">
                        <ul class="navbar-nav flex-row ms-auto align-items-center justify-content-end">
                            <div class="user-name">
                                <li class="nav-item mr-10" id="greeting"></li>
                            </div>
                            @if (auth()->user()->hasRole('admin'))
                                @php
                                    $headerNotifications = auth()->user()->notifications()->latest()->take(8)->get();
                                    $unreadNotificationCount = auth()->user()->unreadNotifications()->count();
                                @endphp
                                <li class="nav-item dropdown">
                                <a class="nav-link nav-icon-hover notification-toggle" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications">
                                    <i class="ti ti-bell fs-6"></i>
                                    @if ($unreadNotificationCount > 0)<span class="notification-badge">{{ $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount }}</span>@endif
                                </a>
                                <div class="dropdown-menu dropdown-menu-end notification-menu" aria-labelledby="notificationsDropdown">
                                    <div class="notification-heading"><i class="ti ti-bell me-2"></i>Notifications ({{ $unreadNotificationCount }})</div>
                                    <div class="notification-list">
                                        @forelse ($headerNotifications as $notification)
                                            <div class="notification-item {{ $notification->read_at ? '' : 'unread' }}">
                                                <span class="notification-icon"><i class="ti ti-user-plus"></i></span>
                                                <div class="notification-copy">{{ $notification->data['message'] ?? 'You have a new notification.' }}<small>{{ $notification->created_at->diffForHumans() }}</small></div>
                                            </div>
                                        @empty
                                            <div class="notification-empty">No notifications yet.</div>
                                        @endforelse
                                    </div>
                                </div>
                                </li>
                            @endif
                            <li class="nav-item">
                                <div class="dropdown-menu dropdown-menu-end dropdown-menu-animate-up"
                                    aria-labelledby="drop1">
                                    <div class="message-body">
                                        <a href="javascript:void(0)"
                                            class="d-flex align-items-center gap-2 dropdown-item">
                                            <i class="ti ti-user fs-6"></i>
                                            <p class="mb-0 fs-3">My Profile</p>
                                        </a>
                                        <a href="./authentication-login.html"
                                            class="btn btn-outline-primary mx-3 mt-2 d-block">Logout</a>
                                    </div>
                                </div>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link nav-icon-hover" href="javascript:void(0)" id="drop2"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <img id="headerAvatar" src="{{ auth()->user()->avatarUrl() }}" alt="User avatar"
                                        width="35" height="35" class="rounded-circle">
                                </a>
                                <div class="dropdown-menu dropdown-menu-end dropdown-menu-animate-up outline-shadow"
                                    aria-labelledby="drop2">
                                    <div class="message-body">
                                        <a href="{{ route('user.profile') }}"
                                            class="d-flex align-items-center gap-2 dropdown-item">
                                            <i class="ti ti-user fs-6"></i>
                                            <p class="mb-0 fs-3">My Profile</p>
                                        </a>
                                        <a href="{{ route('logout') }}"
                                            class="btn btn-outline-primary mx-3 mt-2 d-block">Logout</a>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </nav>
            </header>
            <!--  Header End -->
            <div class="container-fluid flex-grow-1">
                @yield('content')
            </div>
            <div class="footer-wrapper mt-auto">
                <footer class="footer bg-dark text-center py-3">
                    <div class="container">
                        <p class="mb-0 text-white">© {{ now()->year }} Nebula Institute of Technology. All rights reserved.</p>
                    </div>
                </footer>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const notificationDropdown = document.getElementById('notificationsDropdown');
            if (notificationDropdown) {
                notificationDropdown.addEventListener('shown.bs.dropdown', function() {
                    const badge = notificationDropdown.querySelector('.notification-badge');
                    if (!badge) return;

                    fetch(@json(route('notifications.mark-read')), {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                    }).then(function(response) {
                        if (!response.ok) return;
                        badge.remove();
                        document.querySelectorAll('.notification-item.unread').forEach(function(item) {
                            item.classList.remove('unread');
                        });
                        const heading = document.querySelector('.notification-heading');
                        if (heading) heading.innerHTML = '<i class="ti ti-bell me-2"></i>Notifications (0)';
                    });
                });
            }

            // Get the current time
            var currentTime = new Date();
            var currentHour = currentTime.getHours();
            var greeting;

            // Define the greeting based on the current time
            if (currentHour >= 5 && currentHour < 12) {
                greeting = 'Good morning';
            } else if (currentHour >= 12 && currentHour < 18) {
                greeting = 'Good afternoon';
            } else {
                greeting = 'Good evening ';
            }

            // Get the user's name
            var userName = "{{ auth()->check() ? auth()->user()->name : '' }}";

            // Display the greeting and user's name
            if (userName) {
                document.getElementById("greeting").innerHTML = greeting + ", <b>" + userName + "</b>";
            }
        });
    </script>

    @yield('scripts')
    @stack('scripts')

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const courseManagementLink = document.getElementById('course-management-link');
            if (courseManagementLink && window.location.pathname.startsWith('/course-management')) {
                courseManagementLink.classList.add('active');
            }
        });
    </script>
    <div class="toast-container position-fixed bottom-0 end-0 p-3"></div>
</body>
</html>

