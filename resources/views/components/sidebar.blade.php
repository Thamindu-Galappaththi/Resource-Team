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
        $canApprovals = $user->hasPermission('approvals.index');
        $canSpecialApprovals = $user->hasPermission('approvals.special');
        $canHostelIndex = $user->hasPermission('hostel.index');
        $canHostelCreate = $user->hasPermission('hostel.create');
        $canCanteenDashboard = $user->hasPermission('canteen.view');
        $canCanteenCreate = $user->hasPermission('canteen.create');
        $canCanteenIndex = $user->hasPermission('canteen.index');
        $canPayments = $user->hasPermission('payments.view');
        $canReports = $user->hasPermission('reports.view');
    @endphp

    <div class="brand-logo d-flex align-items-center justify-content-center py-3 position-relative w-100">
        <a href="javascript:void(0)" aria-label="Close sidebar"
            class="nav-link sidebartoggler d-lg-none position-absolute top-0 end-0 mt-1 me-3">
            <i class="ti ti-x fs-5"></i>
        </a>

        <a href="{{ route('dashboard') }}" class="text-nowrap logo-img">
            <img src="{{ asset('images/logos/nebula.png') }}" alt="Nebula" width="180">
        </a>
    </div>

    <nav class="sidebar-nav scroll-sidebar" aria-label="Main">
        <ul class="metismenu" id="menu">
            @if($canDashboard)
                <li class="nav-small-cap">
                    <span class="nav-small-cap-text">HOME</span>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
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
                        <a class="sidebar-link {{ request()->routeIs('create.user') ? 'active' : '' }}" href="{{ route('create.user') }}">
                            <span><i class="ti ti-user-plus"></i></span>
                            <span class="hide-menu">Create User</span>
                        </a>
                    </li>
                @endif

                @if($canManageUsers)
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('user.management') ? 'active' : '' }}" href="{{ route('user.management') }}">
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
                        <a class="sidebar-link {{ request()->routeIs('reservations.calendar') ? 'active' : '' }}" href="{{ route('reservations.calendar') }}">
                            <span><i class="ti ti-calendar-event"></i></span>
                            <span class="hide-menu">Reservation Calendar</span>
                        </a>
                    </li>
                @endif

                @if($canCreateReservation)
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('reservations.create') ? 'active' : '' }}" href="{{ route('reservations.create') }}">
                            <span><i class="ti ti-calendar-plus"></i></span>
                            <span class="hide-menu">Create Reservations</span>
                        </a>
                    </li>
                @endif

                @if($canExistingReservation)
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('reservations.index') ? 'active' : '' }}" href="{{ route('reservations.index') }}">
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
                        <a class="sidebar-link {{ request()->routeIs('resources.create') ? 'active' : '' }}" href="{{ route('resources.create') }}">
                            <span><i class="ti ti-circle-plus"></i></span>
                            <span class="hide-menu">Create Resources</span>
                        </a>
                    </li>
                @endif

                @if($canExistingResource)
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('resources.index') ? 'active' : '' }}" href="{{ route('resources.index') }}">
                            <span><i class="ti ti-archive"></i></span>
                            <span class="hide-menu">Existing Resources</span>
                        </a>
                    </li>
                @endif

                @if($canResourceCalendar)
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('resources.calendar') ? 'active' : '' }}" href="{{ route('resources.calendar') }}">
                            <span><i class="ti ti-calendar-event"></i></span>
                            <span class="hide-menu">Resource Calendar</span>
                        </a>
                    </li>
                @endif
            @endif

            @if($canApprovals || $canSpecialApprovals)
                <li class="nav-small-cap">
                    <span class="nav-small-cap-text">APPROVALS</span>
                </li>

                @if($canApprovals)
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('approvals.index') ? 'active' : '' }}" href="{{ route('approvals.index') }}">
                            <span><i class="ti ti-clipboard-check"></i></span>
                            <span class="hide-menu">Approvals</span>
                        </a>
                    </li>
                @endif

                @if($canSpecialApprovals)
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('approvals.special') ? 'active' : '' }}" href="{{ route('approvals.special') }}">
                            <span><i class="ti ti-check"></i></span>
                            <span class="hide-menu">Special Approvals</span>
                        </a>
                    </li>
                @endif
            @endif

            @if($canHostelIndex || $canHostelCreate)
                <li class="nav-small-cap">
                    <span class="nav-small-cap-text">HOSTEL</span>
                </li>

                @if($canHostelIndex)
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('hostel.index') ? 'active' : '' }}" href="{{ route('hostel.index') }}">
                            <span><i class="ti ti-building-community"></i></span>
                            <span class="hide-menu">Hostel Reservations</span>
                        </a>
                    </li>
                @endif

                @if($canHostelCreate)
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('hostel.create') ? 'active' : '' }}" href="{{ route('hostel.create') }}">
                            <span><i class="ti ti-bed"></i></span>
                            <span class="hide-menu">Create Hostel Reservation</span>
                        </a>
                    </li>
                @endif
            @endif

            @if($canCanteenDashboard || $canCanteenCreate || $canCanteenIndex)
                <li class="nav-small-cap">
                    <span class="nav-small-cap-text">CANTEEN</span>
                </li>

                @if($canCanteenDashboard)
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('canteen.dashboard', 'canteen.forecast') ? 'active' : '' }}" href="{{ route('canteen.dashboard') }}">
                            <span><i class="ti ti-soup"></i></span>
                            <span class="hide-menu">Canteen Dashboard</span>
                        </a>
                    </li>
                @endif

                @if($canCanteenCreate)
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('canteen.create', 'canteen.edit') ? 'active' : '' }}" href="{{ route('canteen.create') }}">
                            <span><i class="ti ti-plus"></i></span>
                            <span class="hide-menu">Create Canteen Reservation</span>
                        </a>
                    </li>
                @endif

                @if($canCanteenIndex)
                    <li class="sidebar-item">
                        <a class="sidebar-link {{ request()->routeIs('canteen.index', 'canteen.show') ? 'active' : '' }}" href="{{ route('canteen.index') }}">
                            <span><i class="ti ti-list-check"></i></span>
                            <span class="hide-menu">Existing Canteen Reservations</span>
                        </a>
                    </li>
                @endif
            @endif

            @if($canPayments)
                <li class="nav-small-cap">
                    <span class="nav-small-cap-text">PAYMENTS</span>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link {{ request()->routeIs('payments.lecture-fees') ? 'active' : '' }}" href="{{ route('payments.lecture-fees') }}">
                        <span><i class="ti ti-cash"></i></span>
                        <span class="hide-menu">Lecture Fees</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link {{ request()->routeIs('payments.resources') ? 'active' : '' }}" href="{{ route('payments.resources') }}">
                        <span><i class="ti ti-receipt"></i></span>
                        <span class="hide-menu">Resource Payments</span>
                    </a>
                </li>
            @endif

            @if($canReports)
                <li class="nav-small-cap">
                    <span class="nav-small-cap-text">REPORTS</span>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link {{ request()->routeIs('reports.index') ? 'active' : '' }}" href="{{ route('reports.index') }}">
                        <span><i class="ti ti-chart-bar"></i></span>
                        <span class="hide-menu">Reports & Analytics</span>
                    </a>
                </li>
            @endif

        </ul>
        <div class="sidebar-footer">
            <div class="bg-light rounded p-3 d-flex flex-column gap-2 align-items-center">
                <a href="{{ route('user.profile') }}" class="btn w-100" style="background-color: #6c8cff; color: #fff; font-weight: 500;">My Profile</a>
                <a href="{{ route('logout') }}" class="btn w-100" style="background-color: #ff8c7a; color: #fff; font-weight: 500;">Logout</a>
            </div>
        </div>
    </nav>
</div>
