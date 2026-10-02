<?php

namespace App\Services;

use App\Enums\CanteenReservationStatus;
use App\Enums\ReservationItemStatus;
use App\Enums\ReservationStatus;
use App\Enums\ReservationType;
use App\Models\CanteenReservation;
use App\Models\HostelStayDetail;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\Resource;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Route;

class DashboardService
{
    public function for(User $user): array
    {
        $user->loadMissing('role');
        $slug = $user->roleSlug() ?: 'guest';
        $meta = config('rbac.dashboards.'.$slug, [
            'title' => 'Dashboard',
            'subtitle' => 'Your workspace snapshot.',
        ]);

        $body = match ($slug) {
            'developer' => $this->developer($user),
            'super_admin' => $this->operations($user, true),
            'admin' => $this->operations($user, false),
            'coordinator' => $this->coordinator($user),
            'resource_owner' => $this->resourceOwner($user),
            'slt_employee' => $this->personal($user, true),
            'nebula_sms_user' => $this->personal($user, false),
            'management' => $this->management($user),
            'canteen' => $this->canteen($user),
            'hostel_manager' => $this->hostel($user),
            default => [
                'kpis' => [],
                'actions' => [],
                'chart' => $this->emptyChart('Activity'),
                'breakdown' => ['title' => 'Status mix', 'items' => []],
                'queue' => $this->emptyList('Needs attention', 'Nothing is waiting for you.'),
                'upcoming' => $this->emptyList('Upcoming', 'No upcoming bookings.'),
                'spotlight' => null,
            ],
        };

        return array_merge([
            'user' => $user,
            'role' => $user->role,
            'title' => $meta['title'] ?? 'Dashboard',
            'subtitle' => $meta['subtitle'] ?? '',
            'as_of' => $this->now()->format('D, d M Y'),
        ], $body);
    }

    private function developer(User $user): array
    {
        $reservations = Reservation::query();
        $pending = (clone $reservations)->where('status', ReservationStatus::PENDING_APPROVAL->value)->count();
        $exceptions = (clone $reservations)->whereIn('status', [
            ReservationStatus::CANCELLED->value,
            ReservationStatus::EXPIRED->value,
            ReservationStatus::REJECTED->value,
        ])->count();

        return [
            'kpis' => [
                $this->kpi('Active users', User::query()->where('is_active', true)->count(), 'Signed in accounts', 'ti ti-users', 'primary', $this->url($user, 'user.management', 'user.management')),
                $this->kpi('Reservations', (clone $reservations)->count(), $this->vsYesterday((clone $reservations)), 'ti ti-calendar-event', 'info', $this->url($user, 'reservations.index', 'reservations.index')),
                $this->kpi('Resources', Resource::query()->count(), Resource::query()->where('status', 'under_maintenance')->count().' in maintenance', 'ti ti-building', 'success', $this->url($user, 'resources.index', 'resources.index')),
                $this->kpi('Exceptions', $exceptions, $pending.' pending approval', 'ti ti-alert-triangle', 'warning', $this->url($user, 'approvals.index', 'approvals.index')),
            ],
            'actions' => $this->actions($user, [
                ['User management', 'ti ti-users', 'user.management', 'user.management', 'primary'],
                ['Reservations', 'ti ti-calendar-event', 'reservations.index', 'reservations.index', 'outline'],
                ['Resources', 'ti ti-building', 'resources.index', 'resources.index', 'outline'],
                ['Reports', 'ti ti-chart-bar', 'reports.view', 'reports.index', 'outline'],
            ]),
            'chart' => $this->reservationChart((clone $reservations), 'Bookings this week'),
            'breakdown' => $this->roleBreakdown(),
            'queue' => $this->reservationList($user, (clone $reservations)->latest('id'), 'Recent activity', 'No reservations yet.'),
            'upcoming' => $this->reservationList($user, $this->upcomingQuery(), 'Today & upcoming', 'No upcoming bookings.'),
            'spotlight' => [
                'title' => 'Platform',
                'items' => [
                    ['label' => 'Environment', 'value' => config('app.env')],
                    ['label' => 'PHP', 'value' => PHP_VERSION],
                    ['label' => 'Laravel', 'value' => app()->version()],
                    ['label' => 'Inactive users', 'value' => (string) User::query()->where('is_active', false)->count()],
                ],
            ],
        ];
    }

    private function operations(User $user, bool $executive): array
    {
        $today = $this->today();
        $reservations = Reservation::query();
        $pending = (clone $reservations)->where('status', ReservationStatus::PENDING_APPROVAL->value)->count();
        $todayCount = (clone $reservations)->whereDate('reservation_date', $today)->count();
        $activeResources = Resource::query()->where('status', 'active')->count();
        $maintenance = Resource::query()->where('status', 'under_maintenance')->count();

        $kpis = $executive
            ? [
                $this->kpi('Reservations', (clone $reservations)->count(), $this->vsYesterday((clone $reservations)), 'ti ti-calendar-stats', 'primary', $this->url($user, 'reservations.index', 'reservations.index')),
                $this->kpi('Active users', User::query()->where('is_active', true)->count(), 'People with access', 'ti ti-users', 'info', $this->url($user, 'user.management', 'user.management')),
                $this->kpi('Pending approvals', $pending, 'Waiting on a decision', 'ti ti-clock-hour-4', 'warning', $this->url($user, 'approvals.index', 'approvals.index')),
                $this->kpi('Active resources', $activeResources, $maintenance.' in maintenance', 'ti ti-building-community', 'success', $this->url($user, 'resources.index', 'resources.index')),
            ]
            : [
                $this->kpi('Today', $todayCount, $this->vsYesterday((clone $reservations)), 'ti ti-calendar-event', 'primary', $this->url($user, 'reservations.calendar', 'reservations.calendar')),
                $this->kpi('Available resources', $activeResources, 'Ready to book', 'ti ti-checks', 'success', $this->url($user, 'resources.index', 'resources.index')),
                $this->kpi('Pending approvals', $pending, 'Needs action', 'ti ti-clock-hour-4', 'warning', $this->url($user, 'approvals.index', 'approvals.index')),
                $this->kpi('Maintenance', $maintenance, 'Resources offline', 'ti ti-tool', 'danger', $this->url($user, 'resources.index', 'resources.index')),
            ];

        $actions = $executive
            ? [
                ['Approvals', 'ti ti-clipboard-check', 'approvals.index', 'approvals.index', 'primary'],
                ['Users', 'ti ti-users', 'user.management', 'user.management', 'outline'],
                ['Reports', 'ti ti-chart-bar', 'reports.view', 'reports.index', 'outline'],
                ['Payments', 'ti ti-receipt', 'payments.view', 'payments.resources', 'outline'],
            ]
            : [
                ['Create reservation', 'ti ti-plus', 'reservations.create', 'reservations.create', 'primary'],
                ['Approvals', 'ti ti-clipboard-check', 'approvals.index', 'approvals.index', 'outline'],
                ['Calendar', 'ti ti-calendar', 'reservations.calendar', 'reservations.calendar', 'outline'],
                ['Resources', 'ti ti-building', 'resources.index', 'resources.index', 'outline'],
            ];

        return [
            'kpis' => $kpis,
            'actions' => $this->actions($user, $actions),
            'chart' => $this->reservationChart((clone $reservations), 'Reservations this week'),
            'breakdown' => $this->statusBreakdown((clone $reservations), 'Booking status'),
            'queue' => $this->reservationList(
                $user,
                (clone $reservations)->where('status', ReservationStatus::PENDING_APPROVAL->value)->latest('id'),
                'Pending approvals',
                'No approvals waiting.'
            ),
            'upcoming' => $this->reservationList($user, $this->upcomingQuery(), 'Upcoming bookings', 'No upcoming bookings.'),
            'spotlight' => null,
        ];
    }

    private function coordinator(User $user): array
    {
        $mine = Reservation::query()->where(function (Builder $query) use ($user) {
            $query->where('created_by_user_id', $user->id)->orWhere('requester_id', $user->id);
        });
        $all = Reservation::query();
        $pending = (clone $all)->where('status', ReservationStatus::PENDING_APPROVAL->value)->count();
        $upcomingWeek = (clone $this->upcomingQuery())->whereDate('reservation_date', '<=', $this->today()->copy()->addDays(7))->count();

        return [
            'kpis' => [
                $this->kpi('Open requests', $pending, 'Awaiting approval', 'ti ti-inbox', 'warning', $this->url($user, 'reservations.index', 'reservations.index')),
                $this->kpi('Next 7 days', $upcomingWeek, 'Confirmed & pending', 'ti ti-calendar-week', 'primary', $this->url($user, 'reservations.calendar', 'reservations.calendar')),
                $this->kpi('On the calendar today', (clone $all)->whereDate('reservation_date', $this->today())->count(), 'Live schedule', 'ti ti-clock', 'info'),
                $this->kpi('Created by me', (clone $mine)->count(), 'This workspace', 'ti ti-pencil', 'success', $this->url($user, 'reservations.create', 'reservations.create')),
            ],
            'actions' => $this->actions($user, [
                ['New reservation', 'ti ti-plus', 'reservations.create', 'reservations.create', 'primary'],
                ['Calendar', 'ti ti-calendar', 'reservations.calendar', 'reservations.calendar', 'outline'],
                ['Existing', 'ti ti-list', 'reservations.index', 'reservations.index', 'outline'],
                ['Hostel booking', 'ti ti-bed', 'hostel.create', 'hostel.create', 'outline'],
                ['Canteen order', 'ti ti-tools-kitchen-2', 'canteen.create', 'canteen.create', 'outline'],
            ]),
            'chart' => $this->reservationChart((clone $all), 'Schedule volume'),
            'breakdown' => $this->statusBreakdown((clone $all), 'Request status'),
            'queue' => $this->reservationList(
                $user,
                (clone $all)->where('status', ReservationStatus::PENDING_APPROVAL->value)->latest('id'),
                'Requests to follow up',
                'No open requests.'
            ),
            'upcoming' => $this->reservationList($user, $this->upcomingQuery(), 'Upcoming schedule', 'Nothing scheduled.'),
            'spotlight' => null,
        ];
    }

    private function resourceOwner(User $user): array
    {
        $pending = Reservation::query()->where('status', ReservationStatus::PENDING_APPROVAL->value)->count();
        $approvedWeek = Reservation::query()
            ->whereIn('status', [ReservationStatus::APPROVED->value, ReservationStatus::CONFIRMED->value])
            ->where('reservation_date', '>=', $this->today()->copy()->subDays(6)->toDateString())
            ->count();

        return [
            'kpis' => [
                $this->kpi('Resources', Resource::query()->count(), Resource::query()->where('status', 'active')->count().' active', 'ti ti-building', 'primary', $this->url($user, 'resources.index', 'resources.index')),
                $this->kpi('Pending approvals', $pending, 'Decisions needed', 'ti ti-clock-hour-4', 'warning', $this->url($user, 'approvals.index', 'approvals.index')),
                $this->kpi('Approved this week', $approvedWeek, 'Bookings cleared', 'ti ti-circle-check', 'success'),
                $this->kpi('Maintenance', Resource::query()->where('status', 'under_maintenance')->count(), 'Unavailable assets', 'ti ti-tool', 'danger', $this->url($user, 'resources.index', 'resources.index')),
            ],
            'actions' => $this->actions($user, [
                ['Approvals', 'ti ti-clipboard-check', 'approvals.index', 'approvals.index', 'primary'],
                ['Resources', 'ti ti-building', 'resources.index', 'resources.index', 'outline'],
                ['Resource calendar', 'ti ti-calendar', 'resources.calendar', 'resources.calendar', 'outline'],
                ['Reservations', 'ti ti-list', 'reservations.index', 'reservations.index', 'outline'],
            ]),
            'chart' => $this->reservationChart(Reservation::query(), 'Usage this week'),
            'breakdown' => $this->statusBreakdown(Reservation::query(), 'Request outcomes'),
            'queue' => $this->reservationList(
                $user,
                Reservation::query()->where('status', ReservationStatus::PENDING_APPROVAL->value)->latest('id'),
                'Waiting for you',
                'No pending resource requests.'
            ),
            'upcoming' => $this->reservationList($user, $this->upcomingQuery(), 'Booked resources', 'No upcoming usage.'),
            'spotlight' => [
                'title' => 'Asset health',
                'items' => Resource::query()
                    ->whereIn('status', ['under_maintenance', 'inactive', 'pending_deletion'])
                    ->orderBy('name_model')
                    ->limit(6)
                    ->get()
                    ->map(fn (Resource $resource) => [
                        'label' => $resource->name_model,
                        'value' => str_replace('_', ' ', $resource->status),
                    ])
                    ->all(),
            ],
        ];
    }

    private function personal(User $user, bool $canCreate): array
    {
        $mine = Reservation::query()->where(function (Builder $query) use ($user) {
            $query->where('requester_id', $user->id)->orWhere('created_by_user_id', $user->id);
        });
        $upcoming = (clone $mine)->whereDate('reservation_date', '>=', $this->today())
            ->whereNotIn('status', [
                ReservationStatus::CANCELLED->value,
                ReservationStatus::REJECTED->value,
                ReservationStatus::EXPIRED->value,
            ]);
        $pending = (clone $mine)->where('status', ReservationStatus::PENDING_APPROVAL->value)->count();

        $actions = $canCreate
            ? [
                ['New reservation', 'ti ti-plus', 'reservations.create', 'reservations.create', 'primary'],
                ['My bookings', 'ti ti-list', 'reservations.index', 'reservations.index', 'outline'],
                ['Calendar', 'ti ti-calendar', 'reservations.calendar', 'reservations.calendar', 'outline'],
                ['Hostel stay', 'ti ti-bed', 'hostel.create', 'hostel.create', 'outline'],
            ]
            : [
                ['My schedule', 'ti ti-calendar', 'reservations.calendar', 'reservations.calendar', 'primary'],
                ['Reservation history', 'ti ti-list', 'reservations.index', 'reservations.index', 'outline'],
            ];

        return [
            'kpis' => [
                $this->kpi('My reservations', (clone $mine)->count(), 'All statuses', 'ti ti-bookmark', 'primary', $this->url($user, 'reservations.index', 'reservations.index')),
                $this->kpi('Upcoming', (clone $upcoming)->count(), 'Still on the calendar', 'ti ti-calendar-event', 'info', $this->url($user, 'reservations.calendar', 'reservations.calendar')),
                $this->kpi('Awaiting approval', $pending, 'Submitted, not decided', 'ti ti-hourglass', 'warning'),
                $this->kpi(
                    $canCreate ? 'Open resources' : 'Completed',
                    $canCreate
                        ? Resource::query()->where('status', 'active')->count()
                        : (clone $mine)->where('status', ReservationStatus::COMPLETED->value)->count(),
                    $canCreate ? 'Ready to book' : 'Past bookings',
                    $canCreate ? 'ti ti-building' : 'ti ti-circle-check',
                    'success'
                ),
            ],
            'actions' => $this->actions($user, $actions),
            'chart' => $this->reservationChart((clone $mine), 'My week'),
            'breakdown' => $this->statusBreakdown((clone $mine), 'My booking status'),
            'queue' => $this->reservationList(
                $user,
                (clone $mine)->where('status', ReservationStatus::PENDING_APPROVAL->value)->latest('id'),
                'Waiting on approval',
                'No pending requests.'
            ),
            'upcoming' => $this->reservationList($user, (clone $upcoming)->orderBy('reservation_date')->orderBy('start_time'), 'My upcoming', 'You have no upcoming bookings.'),
            'spotlight' => null,
        ];
    }

    private function management(User $user): array
    {
        $monthStart = $this->today()->copy()->startOfMonth()->toDateString();
        $month = Reservation::query()->whereDate('reservation_date', '>=', $monthStart);
        $completed = (clone $month)->whereIn('status', [
            ReservationStatus::COMPLETED->value,
            ReservationStatus::CONFIRMED->value,
            ReservationStatus::APPROVED->value,
        ])->count();
        $totalMonth = (clone $month)->count();

        return [
            'kpis' => [
                $this->kpi('Utilization today', $this->utilizationToday().'%', 'Active resources in use', 'ti ti-chart-donut', 'primary', $this->url($user, 'reports.view', 'reports.index')),
                $this->kpi('This month', $totalMonth, $this->vsYesterday(Reservation::query()), 'ti ti-calendar-stats', 'info'),
                $this->kpi('Fulfilled', $completed, $totalMonth ? round(100 * $completed / $totalMonth).'% of month' : 'No volume yet', 'ti ti-circle-check', 'success'),
                $this->kpi('Still pending', Reservation::query()->where('status', ReservationStatus::PENDING_APPROVAL->value)->count(), 'Open decisions', 'ti ti-clock', 'warning'),
            ],
            'actions' => $this->actions($user, [
                ['Reports', 'ti ti-chart-bar', 'reports.view', 'reports.index', 'primary'],
                ['Calendar', 'ti ti-calendar', 'reservations.calendar', 'reservations.calendar', 'outline'],
                ['Resources', 'ti ti-building', 'resources.index', 'resources.index', 'outline'],
                ['Payments', 'ti ti-receipt', 'payments.view', 'payments.resources', 'outline'],
            ]),
            'chart' => $this->reservationChart(Reservation::query(), 'Volume this week'),
            'breakdown' => $this->typeBreakdown(Reservation::query(), 'Booking mix'),
            'queue' => $this->reservationList(
                $user,
                Reservation::query()->where('status', ReservationStatus::PENDING_APPROVAL->value)->latest('id'),
                'Operational backlog',
                'No pending items.'
            ),
            'upcoming' => $this->reservationList($user, $this->upcomingQuery(), 'Forward schedule', 'No upcoming bookings.'),
            'spotlight' => null,
        ];
    }

    private function canteen(User $user): array
    {
        $today = $this->today()->toDateString();
        $orders = CanteenReservation::query();
        $todayOrders = (clone $orders)->whereDate('reservation_date', $today);
        $expected = (int) (clone $todayOrders)->whereIn('status', [
            CanteenReservationStatus::PENDING->value,
            CanteenReservationStatus::CONFIRMED->value,
        ])->sum('number_of_orders');
        $confirmed = (int) (clone $todayOrders)->where('status', CanteenReservationStatus::CONFIRMED->value)->sum('number_of_orders');
        $pending = (clone $orders)->where('status', CanteenReservationStatus::PENDING->value)->count();
        $weekVolume = (int) (clone $orders)
            ->whereDate('reservation_date', '>=', $today)
            ->whereDate('reservation_date', '<=', $this->today()->copy()->addDays(6)->toDateString())
            ->whereIn('status', [CanteenReservationStatus::PENDING->value, CanteenReservationStatus::CONFIRMED->value])
            ->sum('number_of_orders');

        $chart = $this->canteenChart();

        return [
            'kpis' => [
                $this->kpi('Expected today', $expected, 'Pending + confirmed covers', 'ti ti-users-group', 'primary', $this->url($user, 'canteen.view', 'canteen.dashboard')),
                $this->kpi('Confirmed meals', $confirmed, 'Kitchen can prepare', 'ti ti-tools-kitchen-2', 'success'),
                $this->kpi('Pending orders', $pending, 'Need confirmation', 'ti ti-clock', 'warning', $this->url($user, 'canteen.index', 'canteen.index')),
                $this->kpi('7-day volume', $weekVolume, 'Covers in the window', 'ti ti-chart-line', 'info'),
            ],
            'actions' => $this->actions($user, [
                ['Canteen dashboard', 'ti ti-tools-kitchen-2', 'canteen.view', 'canteen.dashboard', 'primary'],
                ['Existing orders', 'ti ti-list', 'canteen.index', 'canteen.index', 'outline'],
                ['New order', 'ti ti-plus', 'canteen.create', 'canteen.create', 'outline'],
            ]),
            'chart' => $chart,
            'breakdown' => $this->canteenStatusBreakdown(),
            'queue' => $this->canteenList(
                $user,
                CanteenReservation::query()->where('status', CanteenReservationStatus::PENDING->value)->latest('id'),
                'Orders to confirm',
                'No pending canteen orders.'
            ),
            'upcoming' => $this->canteenList(
                $user,
                CanteenReservation::query()->whereDate('reservation_date', '>=', $today)->orderBy('reservation_date')->orderBy('reservation_time'),
                'Upcoming meals',
                'No upcoming meal orders.'
            ),
            'spotlight' => null,
        ];
    }

    private function hostel(User $user): array
    {
        $today = $this->today();
        $hostel = Reservation::query()->where('type', ReservationType::HOSTEL->value);
        $rooms = Resource::query()->hostelRooms()->count();
        $occupied = ReservationItem::query()
            ->whereHas('reservation', function (Builder $query) {
                $query->where('type', ReservationType::HOSTEL->value)
                    ->whereIn('status', [
                        ReservationStatus::APPROVED->value,
                        ReservationStatus::CONFIRMED->value,
                        ReservationStatus::IN_PROGRESS->value,
                    ]);
            })
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->distinct()
            ->count('resource_id');
        $pending = (clone $hostel)->where('status', ReservationStatus::PENDING_APPROVAL->value)->count();
        $checkIns = HostelStayDetail::query()
            ->whereDate('check_in_at', $today->toDateString())
            ->whereHas('reservation', fn (Builder $query) => $query->whereNotIn('status', [
                ReservationStatus::CANCELLED->value,
                ReservationStatus::REJECTED->value,
            ]))
            ->count();

        return [
            'kpis' => [
                $this->kpi('Rooms ready', $rooms, 'Active hostel rooms', 'ti ti-bed', 'primary', $this->url($user, 'hostel.index', 'hostel.index')),
                $this->kpi('Occupied now', $occupied, $rooms ? round(100 * $occupied / max(1, $rooms)).'% occupancy' : 'No rooms configured', 'ti ti-door', 'info'),
                $this->kpi('Pending stays', $pending, 'Need a decision', 'ti ti-clock', 'warning', $this->url($user, 'hostel.index', 'hostel.index')),
                $this->kpi('Check-ins today', $checkIns, 'Arrivals', 'ti ti-login', 'success'),
            ],
            'actions' => $this->actions($user, [
                ['Hostel bookings', 'ti ti-bed', 'hostel.index', 'hostel.index', 'primary'],
                ['Calendar', 'ti ti-calendar', 'reservations.calendar', 'reservations.calendar', 'outline'],
            ]),
            'chart' => $this->reservationChart((clone $hostel), 'Hostel stays this week'),
            'breakdown' => $this->statusBreakdown((clone $hostel), 'Stay status'),
            'queue' => $this->reservationList(
                $user,
                (clone $hostel)->where('status', ReservationStatus::PENDING_APPROVAL->value)->latest('id'),
                'Pending hostel requests',
                'No pending hostel bookings.',
                true
            ),
            'upcoming' => $this->reservationList(
                $user,
                (clone $hostel)->whereDate('reservation_date', '>=', $today->toDateString())
                    ->whereNotIn('status', [ReservationStatus::CANCELLED->value, ReservationStatus::REJECTED->value])
                    ->orderBy('reservation_date'),
                'Upcoming stays',
                'No upcoming check-ins.',
                true
            ),
            'spotlight' => null,
        ];
    }

    private function kpi(string $label, int|string $value, string $hint, string $icon, string $tone, ?string $href = null): array
    {
        return compact('label', 'value', 'hint', 'icon', 'tone', 'href');
    }

    /**
     * @param  array<int, array{0:string,1:string,2:string,3:string,4?:string}>  $items
     * @return array<int, array{label:string,icon:string,href:string,variant:string}>
     */
    private function actions(User $user, array $items): array
    {
        $out = [];
        foreach ($items as $item) {
            [$label, $icon, $permission, $route, $variant] = array_pad($item, 5, 'outline');
            $href = $this->url($user, $permission, $route);
            if ($href === null) {
                continue;
            }
            $out[] = compact('label', 'icon', 'href', 'variant');
        }

        return $out;
    }

    private function url(User $user, string $permission, string $route, array $params = []): ?string
    {
        if (! $user->hasPermission($permission) || ! Route::has($route)) {
            return null;
        }

        return route($route, $params);
    }

    private function reservationChart(Builder $query, string $title): array
    {
        $points = [];
        $max = 1;
        for ($i = 6; $i >= 0; $i--) {
            $day = $this->today()->copy()->subDays($i);
            $value = (clone $query)->whereDate('reservation_date', $day->toDateString())->count();
            $max = max($max, $value);
            $points[] = [
                'label' => $day->format('D'),
                'value' => $value,
                'pct' => 0,
            ];
        }

        foreach ($points as &$point) {
            $point['pct'] = $point['value'] === 0 ? 0 : max(8, (int) round(100 * $point['value'] / $max));
        }

        return compact('title', 'points');
    }

    private function canteenChart(): array
    {
        $points = [];
        $max = 1;
        for ($i = 6; $i >= 0; $i--) {
            $day = $this->today()->copy()->subDays($i);
            $value = (int) CanteenReservation::query()
                ->whereDate('reservation_date', $day->toDateString())
                ->whereIn('status', [
                    CanteenReservationStatus::PENDING->value,
                    CanteenReservationStatus::CONFIRMED->value,
                    CanteenReservationStatus::COMPLETED->value,
                ])
                ->sum('number_of_orders');
            $max = max($max, $value);
            $points[] = [
                'label' => $day->format('D'),
                'value' => $value,
                'pct' => 0,
            ];
        }

        foreach ($points as &$point) {
            $point['pct'] = $point['value'] === 0 ? 0 : max(8, (int) round(100 * $point['value'] / $max));
        }

        return ['title' => 'Covers this week', 'points' => $points];
    }

    private function emptyChart(string $title): array
    {
        return $this->reservationChart(Reservation::query()->whereRaw('1 = 0'), $title);
    }

    private function statusBreakdown(Builder $query, string $title): array
    {
        $counts = (clone $query)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $total = max(1, (int) $counts->sum());
        $order = [
            ReservationStatus::PENDING_APPROVAL->value => 'warning',
            ReservationStatus::APPROVED->value => 'success',
            ReservationStatus::CONFIRMED->value => 'success',
            ReservationStatus::IN_PROGRESS->value => 'info',
            ReservationStatus::COMPLETED->value => 'primary',
            ReservationStatus::CHANGES_REQUESTED->value => 'warning',
            ReservationStatus::REJECTED->value => 'danger',
            ReservationStatus::CANCELLED->value => 'danger',
            ReservationStatus::DRAFT->value => 'neutral',
            ReservationStatus::EXPIRED->value => 'neutral',
        ];

        $items = [];
        foreach ($order as $status => $tone) {
            $value = (int) ($counts[$status] ?? 0);
            if ($value === 0) {
                continue;
            }
            $items[] = [
                'label' => ReservationStatus::from($status)->label(),
                'value' => $value,
                'pct' => (int) round(100 * $value / $total),
                'tone' => $tone,
            ];
        }

        return compact('title', 'items');
    }

    private function typeBreakdown(Builder $query, string $title): array
    {
        $counts = (clone $query)->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type');
        $total = max(1, (int) $counts->sum());
        $items = [];
        foreach ($counts as $type => $value) {
            $items[] = [
                'label' => ucfirst(str_replace('_', ' ', (string) $type)),
                'value' => (int) $value,
                'pct' => (int) round(100 * (int) $value / $total),
                'tone' => 'primary',
            ];
        }

        return compact('title', 'items');
    }

    private function roleBreakdown(): array
    {
        $counts = User::query()
            ->selectRaw('user_role, count(*) as total')
            ->groupBy('user_role')
            ->pluck('total', 'user_role');
        $names = Role::query()->pluck('name', 'slug');
        $total = max(1, (int) $counts->sum());
        $items = [];
        foreach ($counts as $slug => $value) {
            $items[] = [
                'label' => $names[$slug] ?? str((string) $slug)->replace('_', ' ')->title()->toString(),
                'value' => (int) $value,
                'pct' => (int) round(100 * (int) $value / $total),
                'tone' => 'info',
            ];
        }

        return ['title' => 'Users by role', 'items' => $items];
    }

    private function canteenStatusBreakdown(): array
    {
        $counts = CanteenReservation::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $total = max(1, (int) $counts->sum());
        $items = [];
        foreach ($counts as $status => $value) {
            $items[] = [
                'label' => ucfirst(str_replace('_', ' ', (string) $status)),
                'value' => (int) $value,
                'pct' => (int) round(100 * (int) $value / $total),
                'tone' => match ($status) {
                    CanteenReservationStatus::CONFIRMED->value, CanteenReservationStatus::COMPLETED->value => 'success',
                    CanteenReservationStatus::PENDING->value => 'warning',
                    default => 'danger',
                },
            ];
        }

        return ['title' => 'Order status', 'items' => $items];
    }

    private function reservationList(User $user, Builder $query, string $title, string $empty, bool $hostel = false): array
    {
        $rows = $query->with(['requester', 'hostelStay'])->limit(8)->get()->map(function (Reservation $reservation) use ($user, $hostel) {
            $href = $hostel
                ? $this->url($user, 'hostel.index', 'hostel.show', [$reservation])
                : $this->url($user, 'reservations.index', 'reservations.show', [$reservation]);

            $when = optional($reservation->reservation_date)?->format('d M Y');
            if ($reservation->start_time) {
                $when .= ' · '.substr((string) $reservation->start_time, 0, 5);
            }

            return [
                'title' => $reservation->title ?: ($reservation->purpose ?: $reservation->reference),
                'meta' => trim(($reservation->reference ?? '').' · '.($reservation->requester?->name ?? '—').' · '.$when, ' ·'),
                'status' => $reservation->statusEnum()->label(),
                'tone' => $this->statusTone($reservation->status),
                'href' => $href,
            ];
        })->all();

        return compact('title', 'empty', 'rows');
    }

    private function canteenList(User $user, Builder $query, string $title, string $empty): array
    {
        $rows = $query->with('requestedBy')->limit(8)->get()->map(function (CanteenReservation $reservation) use ($user) {
            $when = optional($reservation->reservation_date)?->format('d M Y');
            if ($reservation->reservation_time) {
                $when .= ' · '.substr((string) $reservation->reservation_time, 0, 5);
            }

            return [
                'title' => $reservation->reservation_name ?: $reservation->reservation_ref,
                'meta' => trim(($reservation->reservation_ref ?? '').' · '.$reservation->number_of_orders.' covers · '.$when, ' ·'),
                'status' => ucfirst(str_replace('_', ' ', (string) $reservation->status)),
                'tone' => match ($reservation->status) {
                    CanteenReservationStatus::CONFIRMED->value => 'success',
                    CanteenReservationStatus::PENDING->value => 'warning',
                    default => 'neutral',
                },
                'href' => $this->url($user, 'canteen.index', 'canteen.show', [$reservation]),
            ];
        })->all();

        return compact('title', 'empty', 'rows');
    }

    private function emptyList(string $title, string $empty): array
    {
        return ['title' => $title, 'empty' => $empty, 'rows' => []];
    }

    private function upcomingQuery(): Builder
    {
        return Reservation::query()
            ->whereDate('reservation_date', '>=', $this->today()->toDateString())
            ->whereNotIn('status', [
                ReservationStatus::CANCELLED->value,
                ReservationStatus::REJECTED->value,
                ReservationStatus::EXPIRED->value,
            ])
            ->orderBy('reservation_date')
            ->orderBy('start_time');
    }

    private function vsYesterday(Builder $query): string
    {
        $today = (clone $query)->whereDate('reservation_date', $this->today()->toDateString())->count();
        $yesterday = (clone $query)->whereDate('reservation_date', $this->today()->copy()->subDay()->toDateString())->count();

        if ($yesterday === 0 && $today === 0) {
            return 'No movement vs yesterday';
        }

        $delta = $today - $yesterday;

        return ($delta >= 0 ? '+' : '').$delta.' vs yesterday';
    }

    private function utilizationToday(): int
    {
        $active = Resource::query()->where('status', 'active')->count();
        if ($active === 0) {
            return 0;
        }

        $busy = ReservationItem::query()
            ->whereIn('status', [ReservationItemStatus::HELD->value, ReservationItemStatus::CONFIRMED->value])
            ->where('starts_at', '<=', now()->endOfDay())
            ->where('ends_at', '>', now()->startOfDay())
            ->distinct()
            ->count('resource_id');

        return (int) min(100, round(100 * $busy / $active));
    }

    private function statusTone(string $status): string
    {
        return match ($status) {
            ReservationStatus::APPROVED->value, ReservationStatus::CONFIRMED->value, ReservationStatus::COMPLETED->value => 'success',
            ReservationStatus::PENDING_APPROVAL->value, ReservationStatus::CHANGES_REQUESTED->value, ReservationStatus::DRAFT->value => 'warning',
            ReservationStatus::REJECTED->value, ReservationStatus::CANCELLED->value, ReservationStatus::EXPIRED->value => 'danger',
            default => 'info',
        };
    }

    private function timezone(): string
    {
        return config('reservations.display_timezone', 'Asia/Colombo');
    }

    private function now(): Carbon
    {
        return now($this->timezone());
    }

    private function today(): Carbon
    {
        return $this->now()->startOfDay();
    }
}
