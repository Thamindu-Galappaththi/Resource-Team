@extends('layouts.app')

@section('title', 'Role Dashboards')

@section('content')
@php
    use App\Helpers\RoleHelper;

    $user = auth()->user();
    $roleSource = $user->user_role ?? ($user->email ?? '');
    $normalizedRole = RoleHelper::normalizeRole($roleSource);
    $isDeveloper = RoleHelper::isDeveloper($roleSource);

    $dashboardMeta = [
        'super admin' => [
            'title' => 'Super Admin Dashboard',
            'subtitle' => 'Cross-functional operations and executive visibility.',
            'theme' => '#2563eb',
            'icon' => 'bi bi-people-fill',
            'short' => 'PA L1',
            'stats' => [
                ['label' => 'Managed Users', 'value' => 42],
                ['label' => 'Open Approvals', 'value' => 8],
                ['label' => 'System Health', 'value' => 'Stable'],
            ],
        ],
        'admin' => [
            'title' => 'Admin Dashboard',
            'subtitle' => 'Daily operational control and process monitoring.',
            'theme' => '#3b82f6',
            'icon' => 'bi bi-person-workspace',
            'short' => 'PA L2',
            'stats' => [
                ['label' => 'Today Reservations', 'value' => 16],
                ['label' => 'Pending Resources', 'value' => 5],
                ['label' => 'Conflicts', 'value' => 1],
            ],
        ],
        'cordinator' => [
            'title' => 'Coordinator Dashboard',
            'subtitle' => 'Schedule coordination and reservation alignment.',
            'theme' => '#0ea5e9',
            'icon' => 'bi bi-megaphone-fill',
            'short' => 'Coordinator',
            'stats' => [
                ['label' => 'Calendar Events', 'value' => 24],
                ['label' => 'Requests to Review', 'value' => 6],
                ['label' => 'Approved Today', 'value' => 11],
            ],
        ],
        'resource owner' => [
            'title' => 'Resource Owner Dashboard',
            'subtitle' => 'Resource lifecycle and availability tracking.',
            'theme' => '#14b8a6',
            'icon' => 'bi bi-building',
            'short' => 'Resource Owner',
            'stats' => [
                ['label' => 'Total Resources', 'value' => 38],
                ['label' => 'Active Assets', 'value' => 34],
                ['label' => 'Maintenance Due', 'value' => 2],
            ],
        ],
        'nebula users' => [
            'title' => 'Nebula Users Dashboard',
            'subtitle' => 'Self-service reservation and request status.',
            'theme' => '#8b5cf6',
            'icon' => 'bi bi-stars',
            'short' => 'Nebula Users',
            'stats' => [
                ['label' => 'My Reservations', 'value' => 4],
                ['label' => 'Upcoming Slots', 'value' => 2],
                ['label' => 'Awaiting Approval', 'value' => 1],
            ],
        ],
    ];

    $rolesToRender = $isDeveloper
        ? ['super admin', 'admin', 'cordinator', 'resource owner', 'nebula users']
        : (array_key_exists($normalizedRole, $dashboardMeta) ? [$normalizedRole] : []);
@endphp

<style>
    .nebula-shell {
        max-width: 1280px;
        margin: 1rem auto;
    }

    .nebula-header-card,
    .nebula-workspace-card {
        border: none;
        border-radius: 14px;
        background: #ffffff;
        box-shadow: 0 14px 30px rgba(9, 30, 66, 0.12);
    }

    .nebula-header-card {
        padding: 1.5rem 1.6rem;
    }

    .nebula-title {
        font-size: 2rem;
        line-height: 1.1;
        margin: 0;
        color: #10335d;
        font-weight: 700;
    }

    .nebula-subtitle {
        margin-top: 0.45rem;
        margin-bottom: 0;
        color: #526281;
    }

    .nebula-role-tabs {
        border-bottom: none;
        gap: 0.5rem;
        margin-top: 1rem;
        flex-wrap: wrap;
    }

    .nebula-role-tabs .nav-link {
        border: 1px solid #dbe3f0;
        background: #ffffff;
        color: #102e55;
        border-radius: 12px;
        font-weight: 600;
        padding: 0.62rem 0.9rem;
        line-height: 1;
    }

    .nebula-role-tabs .nav-link.active {
        color: #ffffff;
        border-color: transparent;
        box-shadow: 0 10px 20px rgba(30, 75, 149, 0.24);
    }

    .nebula-topbar {
        border-radius: 12px;
        background: #f7f9fc;
        padding: 0.8rem 1rem;
        margin-bottom: 0.9rem;
    }

    .nebula-mini-tabs {
        display: flex;
        gap: 0.35rem;
        flex-wrap: wrap;
        margin-bottom: 0.9rem;
    }

    .nebula-mini-tabs .btn {
        border-radius: 10px;
        border: none;
        font-weight: 600;
        padding: 0.42rem 0.9rem;
        color: #fff;
        background: #4f8cff;
    }

    .nebula-mini-tabs .btn:first-child {
        background: #213a6b;
    }

    .nebula-metrics .metric-card {
        border: 1px solid #e7edf5;
        border-radius: 12px;
        padding: 0.9rem;
        background: #ffffff;
        height: 100%;
    }

    .nebula-metrics .metric-label {
        color: #63758f;
        font-size: 0.82rem;
    }

    .nebula-metrics .metric-value {
        color: #0e284a;
        font-size: 1.4rem;
        font-weight: 700;
    }

    .nebula-section-tabs {
        display: flex;
        gap: 0.45rem;
        flex-wrap: wrap;
        margin-bottom: 0.95rem;
    }

    .nebula-section-tab {
        border-radius: 10px;
        padding: 0.5rem 0.95rem;
        background: #e9edf3;
        color: #233b63;
        font-weight: 600;
        font-size: 0.92rem;
    }

    .nebula-section-tab.active {
        background: #2563eb;
        color: #fff;
    }

    @media (max-width: 768px) {
        .nebula-title {
            font-size: 1.55rem;
        }

        .nebula-header-card {
            padding: 1.1rem;
        }
    }
</style>

<div class="container-fluid nebula-shell">
    <div class="nebula-header-card mb-3">
        <h1 class="nebula-title">Nebula Developer Dashboard</h1>
        <p class="nebula-subtitle">Access all role dashboards from a single interface</p>
    </div>

    @if(empty($rolesToRender))
        <div class="row">
            <div class="col-12">
                <div class="alert alert-warning border-0 shadow-sm">
                    No dashboard is assigned for this role yet. Configure permissions and role mapping to continue.
                </div>
            </div>
        </div>
    @endif

    @if(!empty($rolesToRender))
        <div class="nebula-workspace-card p-3 p-md-4 mb-4">
            <ul class="nav nav-tabs nebula-role-tabs" id="roleDashboardTabs" role="tablist">
                @foreach($rolesToRender as $index => $roleKey)
                    @php
                        $tabId = str_replace(' ', '-', $roleKey);
                        $meta = $dashboardMeta[$roleKey];
                    @endphp
                    <li class="nav-item" role="presentation">
                        <button
                            class="nav-link {{ $index === 0 ? 'active' : '' }}"
                            id="tab-{{ $tabId }}"
                            data-bs-toggle="tab"
                            data-bs-target="#pane-{{ $tabId }}"
                            type="button"
                            role="tab"
                            aria-controls="pane-{{ $tabId }}"
                            aria-selected="{{ $index === 0 ? 'true' : 'false' }}"
                            style="{{ $index === 0 ? 'background:' . $meta['theme'] . ';' : '' }}">
                            <i class="{{ $meta['icon'] }} me-1"></i>{{ $meta['short'] }}
                        </button>
                    </li>
                @endforeach
                @if($isDeveloper)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" type="button" disabled>
                            <i class="bi bi-code-slash me-1"></i>Developer
                        </button>
                    </li>
                @endif
            </ul>

            <div class="nebula-mini-tabs mt-3">
                <button class="btn" type="button">Two View</button>
                <button class="btn" type="button">Three View</button>
                <button class="btn" type="button">Four View</button>
            </div>

            <div class="tab-content" id="roleDashboardTabsContent">
                @foreach($rolesToRender as $index => $roleKey)
                    @php
                        $tabId = str_replace(' ', '-', $roleKey);
                        $meta = $dashboardMeta[$roleKey];
                    @endphp
                    <div
                        class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}"
                        id="pane-{{ $tabId }}"
                        role="tabpanel"
                        aria-labelledby="tab-{{ $tabId }}"
                        tabindex="0">

                        <div class="nebula-topbar d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-list fs-5"></i>
                                <span class="fw-semibold">{{ $meta['title'] }}</span>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <span class="text-secondary">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, <strong>{{ $user->name ?? 'Developer' }}</strong></span>
                                <button class="btn btn-primary btn-sm" type="button" onclick="toggleNebulaFullscreen(this)">
                                    <i class="bi bi-fullscreen me-1"></i>Fullscreen
                                </button>
                            </div>
                        </div>

                        <div class="nebula-section-tabs">
                            <span class="nebula-section-tab active"><i class="bi bi-graph-up-arrow me-1"></i>Overview</span>
                            <span class="nebula-section-tab"><i class="bi bi-people-fill me-1"></i>Students</span>
                            <span class="nebula-section-tab"><i class="bi bi-currency-dollar me-1"></i>Revenues</span>
                            <span class="nebula-section-tab"><i class="bi bi-exclamation-circle-fill me-1"></i>Outstanding</span>
                            <span class="nebula-section-tab"><i class="bi bi-diagram-3-fill me-1"></i>Marketing</span>
                        </div>

                        <p class="text-muted mb-3">{{ $meta['subtitle'] }}</p>

                        <div class="row nebula-metrics">
                            @foreach($meta['stats'] as $stat)
                                <div class="col-md-4 mb-3">
                                    <div class="metric-card">
                                        <div class="metric-label">{{ $stat['label'] }}</div>
                                        <div class="metric-value">{{ $stat['value'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Focus Area</th>
                                        <th>Status</th>
                                        <th>Owner</th>
                                        <th>Updated</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Reservations Pipeline</td>
                                        <td><span class="badge bg-success">On Track</span></td>
                                        <td>{{ ucwords($roleKey) }}</td>
                                        <td>{{ now()->format('Y-m-d') }}</td>
                                    </tr>
                                    <tr>
                                        <td>Resource Allocation</td>
                                        <td><span class="badge bg-warning text-dark">Review</span></td>
                                        <td>{{ ucwords($roleKey) }}</td>
                                        <td>{{ now()->format('Y-m-d') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

</div>

<script>
    function toggleNebulaFullscreen(btn) {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen();
            return;
        }

        document.exitFullscreen();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const roleTabs = document.querySelectorAll('#roleDashboardTabs .nav-link');
        roleTabs.forEach(function (tab) {
            tab.addEventListener('shown.bs.tab', function () {
                roleTabs.forEach(function (item) {
                    item.style.background = item.classList.contains('active')
                        ? item.dataset.activeColor || '#2563eb'
                        : '#ffffff';
                });
            });
        });

        roleTabs.forEach(function (tab) {
            const activeStyle = tab.getAttribute('style');
            if (activeStyle && activeStyle.includes('background:')) {
                const parts = activeStyle.split('background:');
                tab.dataset.activeColor = parts[1].replace(';', '').trim();
            }
        });
    });
</script>

@endsection
