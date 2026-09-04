<div>
    @php
        $user = auth()->user();
        $canDashboard = $user->hasPermission('dashboard');
        $canCreateUser = $user->hasPermission('user.create');
        $canManageUsers = $user->hasPermission('user.management');
        $canReservationCalendar = $user->hasPermission('reservations.calendar');
        $canCreateReservation = $user->hasPermission('reservations.create');
        $canExistingReservation = $user->hasPermission('reservations.index');
        $canCreateResource = $user->hasPermission('resources.create');
        $canExistingResource = $user->hasPermission('resources.index');
        $canResourceCalendar = $user->hasPermission('resources.calendar');
        $canSpecialApprovals = $user->hasPermission('approvals.special');
    @endphp

    <div class="brand-logo d-flex align-items-center justify-content-center py-3 position-relative w-100">
        <a href="javascript:void(0)" aria-label="Close sidebar"
            class="nav-link sidebartoggler d-xl-none position-absolute top-0 end-0 mt-1 me-3">
            <i class="ti ti-x fs-5"></i>
        </a>

        <a href="{{ route('dashboard') }}" class="text-nowrap logo-img">
            <img src="{{ asset('images/logos/nebula.png') }}" alt="Nebula" width="180">
        </a>
    </div>

    <nav class="sidebar-nav scroll-sidebar" data-simplebar="">
        <ul class="metismenu" id="menu">
            @if($canDashboard)
                <li class="nav-small-cap">
                    <span class="nav-small-cap-text">HOME</span>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link {{ Route::currentRouteName() === 'dashboard' ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <span><i class="ti ti-layout-dashboard"></i></span>
                        <span class="hide-menu">Dashboard</span>
                    </a>
                </li>
            @endif

            @if($canCreateUser || $canManageUsers)
                <li class="nav-small-cap">
                    <span class="nav-small-cap-text">USER MANAGEMENT</span>
                </li>

                @if($canCreateUser)
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ Route::currentRouteName() === 'create.user' ? 'active' : '' }}" href="{{ route('create.user') }}">
                            <span><i class="ti ti-user-plus"></i></span>
                            <span class="hide-menu">Create User</span>
                        </a>
                    </li>
                @endif

                @if($canManageUsers)
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ Route::currentRouteName() === 'user.management' ? 'active' : '' }}" href="{{ route('user.management') }}">
                            <span><i class="ti ti-users"></i></span>
                            <span class="hide-menu">User Management</span>
                        </a>
                    </li>
                @endif
            @endif

            @if($canReservationCalendar || $canCreateReservation || $canExistingReservation)
                <li class="nav-small-cap">
                    <span class="nav-small-cap-text">RESERVATION MANAGEMENT</span>
                </li>

                @if($canReservationCalendar)
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ Route::currentRouteName() === 'reservations.calendar' ? 'active' : '' }}" href="{{ route('reservations.calendar') }}">
                            <span><i class="ti ti-calendar-event"></i></span>
                            <span class="hide-menu">Reservation Calendar</span>
                        </a>
                    </li>
                @endif

                @if($canCreateReservation)
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ Route::currentRouteName() === 'reservations.create' ? 'active' : '' }}" href="{{ route('reservations.create') }}">
                            <span><i class="ti ti-calendar-plus"></i></span>
                            <span class="hide-menu">Create Reservations</span>
                        </a>
                    </li>
                @endif

                @if($canExistingReservation)
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ Route::currentRouteName() === 'reservations.index' ? 'active' : '' }}" href="{{ route('reservations.index') }}">
                            <span><i class="ti ti-list-details"></i></span>
                            <span class="hide-menu">Existing Reservations</span>
                        </a>
                    </li>
                @endif
            @endif

            @if($canCreateResource || $canExistingResource || $canResourceCalendar)
                <li class="nav-small-cap">
                    <span class="nav-small-cap-text">RESOURCE MANAGEMENT</span>
                </li>

                @if($canCreateResource)
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ Route::currentRouteName() === 'resources.create' ? 'active' : '' }}" href="{{ route('resources.create') }}">
                            <span><i class="ti ti-circle-plus"></i></span>
                            <span class="hide-menu">Create Resources</span>
                        </a>
                    </li>
                @endif

                @if($canExistingResource)
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ Route::currentRouteName() === 'resources.index' ? 'active' : '' }}" href="{{ route('resources.index') }}">
                            <span><i class="ti ti-archive"></i></span>
                            <span class="hide-menu">Existing Resources</span>
                        </a>
                    </li>
                @endif

                @if($canResourceCalendar)
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ Route::currentRouteName() === 'resources.calendar' ? 'active' : '' }}" href="{{ route('resources.calendar') }}">
                            <span><i class="ti ti-calendar-event"></i></span>
                            <span class="hide-menu">Resource Calendar</span>
                        </a>
                    </li>
                @endif
            @endif

            @if($canSpecialApprovals)
                <li class="nav-small-cap">
                    <span class="nav-small-cap-text">APPROVALS</span>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link {{ Route::currentRouteName() === 'approvals.special' ? 'active' : '' }}" href="{{ route('approvals.special') }}">
                        <span><i class="ti ti-check"></i></span>
                        <span class="hide-menu">Special Approvals</span>
                    </a>
                </li>
            @endif

            <hr>
            <div class="px-3 pb-3">
                <div class="bg-light rounded p-3 d-flex flex-column gap-2 align-items-center">
                    <a href="{{ route('user.profile') }}" class="btn w-100" style="background-color: #6c8cff; color: #fff; font-weight: 500;">My Profile</a>
                    <a href="{{ route('logout') }}" class="btn w-100" style="background-color: #ff8c7a; color: #fff; font-weight: 500;">Logout</a>
                </div>
            </div>
        </ul>
    </nav>
</div>
