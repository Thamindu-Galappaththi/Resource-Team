<?php

namespace App\Services;

use App\Enums\CanteenReservationStatus;
use App\Enums\ReservationStatus;
use App\Models\CanteenReservation;
use App\Models\HostelStayDetail;
use App\Models\LecturerPayable;
use App\Models\LectureSession;
use App\Models\Location;
use App\Models\Reservation;
use App\Models\ReservationItem;
use App\Models\Resource;
use App\Models\ResourceCategory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportService
{
    // -----------------------------------------------------------------------
    // PUBLIC ENTRY POINT
    // -----------------------------------------------------------------------

    public function getReportData(array $filters): array
    {
        $timezone = config('reservations.display_timezone', 'Asia/Colombo');
        $now      = now($timezone);

        $fromDate = ! empty($filters['from_date'])
            ? Carbon::parse($filters['from_date'], $timezone)->startOfDay()
            : $now->copy()->subDays(29)->startOfDay();

        $toDate = ! empty($filters['to_date'])
            ? Carbon::parse($filters['to_date'], $timezone)->endOfDay()
            : $now->copy()->endOfDay();

        if ($fromDate->gt($toDate)) {
            [$fromDate, $toDate] = [$toDate->copy()->startOfDay(), $fromDate->copy()->endOfDay()];
        }

        $locationId = !empty($filters['location_id']) ? (int) $filters['location_id'] : null;
        $categoryId = !empty($filters['resource_category_id']) ? (int) $filters['resource_category_id'] : null;
        $status     = !empty($filters['status']) ? (string) $filters['status'] : null;
        $search     = !empty($filters['search']) ? trim((string) $filters['search']) : null;

        $daysInPeriod = max(1, (int) round(
            $fromDate->copy()->startOfDay()->diffInDays($toDate->copy()->startOfDay())
        ) + 1);

        // ── Base reservation query ──────────────────────────────────────────
        $rq = Reservation::query()
            ->whereDate('reservation_date', '>=', $fromDate->toDateString())
            ->whereDate('reservation_date', '<=', $toDate->toDateString())
            ->when($locationId, fn (Builder $q) => $q->where('location_id', $locationId))
            ->when($status,     fn (Builder $q) => $q->where('status', $status))
            ->when($categoryId, function (Builder $q) use ($categoryId) {
                $q->whereHas('items.resource.type', fn ($tq) => $tq->where('resource_category_id', $categoryId));
            })
            ->when($search, function (Builder $q) use ($search) {
                $q->where(function (Builder $sq) use ($search) {
                    $sq->where('title', 'like', "%{$search}%")
                        ->orWhere('purpose', 'like', "%{$search}%")
                        ->orWhere('reference', 'like', "%{$search}%")
                        ->orWhereHas('requester', fn ($uq) => $uq->where('name', 'like', "%{$search}%"));
                });
            });

        // ── Summary KPIs ────────────────────────────────────────────────────
        $total     = (clone $rq)->count();
        $approved  = (clone $rq)->whereIn('status', [
            ReservationStatus::APPROVED->value,
            ReservationStatus::CONFIRMED->value,
            ReservationStatus::COMPLETED->value,
        ])->count();
        $pending   = (clone $rq)->where('status', ReservationStatus::PENDING_APPROVAL->value)->count();
        $cancelled = (clone $rq)->whereIn('status', [
            ReservationStatus::CANCELLED->value,
            ReservationStatus::REJECTED->value,
        ])->count();

        $approvalRate = $total > 0 ? round(($approved / $total) * 100, 1) : 0.0;

        $items = ReservationItem::query()
            ->whereHas('reservation', function (Builder $q) use ($fromDate, $toDate, $locationId, $status, $search) {
                $q->whereDate('reservation_date', '>=', $fromDate->toDateString())
                    ->whereDate('reservation_date', '<=', $toDate->toDateString())
                    ->when($locationId, fn ($lq) => $lq->where('location_id', $locationId))
                    ->when($status, fn ($sq) => $sq->where('status', $status))
                    ->when($search, function (Builder $sq) use ($search) {
                        $sq->where(function (Builder $inner) use ($search) {
                            $inner->where('title', 'like', "%{$search}%")
                                ->orWhere('purpose', 'like', "%{$search}%")
                                ->orWhere('reference', 'like', "%{$search}%")
                                ->orWhereHas('requester', fn ($uq) => $uq->where('name', 'like', "%{$search}%"));
                        });
                    });
            })
            ->when($categoryId, function (Builder $q) use ($categoryId) {
                $q->whereHas('resource.type', fn ($tq) => $tq->where('resource_category_id', $categoryId));
            })->get();

        $totalMinutes    = $items->sum(fn ($i) => $i->starts_at && $i->ends_at ? max(0, $i->starts_at->diffInMinutes($i->ends_at)) : 0);
        $totalHrsBooked  = round($totalMinutes / 60, 1);
        $activeResources = Resource::query()
            ->where('status', 'active')
            ->when($locationId, fn ($q) => $q->where('location_id', $locationId))
            ->when($categoryId, fn ($q) => $q->whereHas('type', fn ($tq) => $tq->where('resource_category_id', $categoryId)))
            ->count();

        $totalCapacity       = max(1, $activeResources * $daysInPeriod * 8);
        $overallUtilization  = min(100, round(($totalHrsBooked / $totalCapacity) * 100, 1));

        return [
            'filters' => [
                'from_date'            => $fromDate->toDateString(),
                'to_date'              => $toDate->toDateString(),
                'location_id'          => $locationId,
                'resource_category_id' => $categoryId,
                'status'               => $status,
                'search'               => $search,
            ],
            'summary' => [
                'total_reservations'   => $total,
                'approved_count'       => $approved,
                'pending_count'        => $pending,
                'cancelled_count'      => $cancelled,
                'approval_rate'        => $approvalRate,
                'total_hours_booked'   => $totalHrsBooked,
                'active_resources_count' => $activeResources,
                'utilization_rate'     => $overallUtilization,
                'days_count'           => $daysInPeriod,
            ],
            // Tab 1 – Facility Utilization
            'resource_utilization' => $this->buildResourceUtilization($fromDate, $toDate, $locationId, $categoryId, $daysInPeriod),
            'campus_comparison'    => $this->buildCampusComparison($fromDate, $toDate, $categoryId, $daysInPeriod),
            'peak_hours'           => $this->buildPeakHours($rq),
            'reservations'         => (clone $rq)
                ->with(['requester', 'location'])
                ->orderByDesc('reservation_date')
                ->orderByDesc('start_time')
                ->get(),
            // Tab 2 – Approval & Booking Health
            'approval_health'      => $this->buildApprovalHealth($rq),
            'booking_trend'        => $this->buildBookingTrend($rq, $fromDate, $toDate),
            'category_breakdown'   => $this->buildCategoryBreakdown($fromDate, $toDate, $locationId),
            // Tab 3 – Financial & Lecturer Fees
            'financial'            => $this->buildFinancial($fromDate, $toDate, $locationId),
            // Tab 4 – Hostel
            'hostel'               => $this->buildHostel($fromDate, $toDate, $locationId),
            // Tab 5 – Canteen
            'canteen'              => $this->buildCanteen($fromDate, $toDate, $locationId),
        ];
    }

    // -----------------------------------------------------------------------
    // TAB 1 – FACILITY UTILIZATION
    // -----------------------------------------------------------------------

    private function buildResourceUtilization(Carbon $from, Carbon $to, ?int $locId, ?int $catId, int $days): Collection
    {
        $resources = Resource::query()
            ->with(['type.category', 'location'])
            ->when($locId, fn ($q) => $q->where('location_id', $locId))
            ->when($catId, fn ($q) => $q->whereHas('type', fn ($tq) => $tq->where('resource_category_id', $catId)))
            ->orderBy('name_model')
            ->get();

        $capHours = max(1, $days * 8);
        $itemsByResource = $resources->isEmpty()
            ? collect()
            : ReservationItem::query()
                ->whereIn('resource_id', $resources->pluck('id'))
                ->whereHas('reservation', function (Builder $q) use ($from, $to) {
                    $q->whereDate('reservation_date', '>=', $from->toDateString())
                        ->whereDate('reservation_date', '<=', $to->toDateString())
                        ->whereIn('status', [
                            ReservationStatus::APPROVED->value,
                            ReservationStatus::CONFIRMED->value,
                            ReservationStatus::COMPLETED->value,
                            ReservationStatus::IN_PROGRESS->value,
                        ]);
                })
                ->get()
                ->groupBy('resource_id');

        return $resources->map(function (Resource $r) use ($itemsByResource, $capHours) {
            $items = $itemsByResource->get($r->id, collect());

            $mins    = $items->sum(fn ($i) => $i->starts_at && $i->ends_at ? max(0, $i->starts_at->diffInMinutes($i->ends_at)) : 0);
            $hours   = round($mins / 60, 1);
            $pct     = min(100, round(($hours / $capHours) * 100, 1));

            [$decision, $badge] = match (true) {
                $r->status === 'under_maintenance' => ['Under Maintenance – Track completion date', 'bg-secondary'],
                $pct >= 80 => ['Critical Demand – Procure additional units', 'bg-danger'],
                $pct >= 55 => ['High Demand – Monitor & restrict ad-hoc slots', 'bg-warning'],
                $pct >= 30 => ['Healthy Utilization – No action needed', 'bg-success'],
                $items->count() === 0 => ['Idle – Review need or reallocate space', 'bg-dark'],
                default => ['Underused – Open to more intake sessions', 'bg-info'],
            };

            return [
                'id'             => $r->id,
                'name'           => $r->name_model,
                'serial_number'  => $r->serial_number,
                'category'       => $r->type?->category?->name ?? '—',
                'type'           => $r->type?->name ?? '—',
                'location'       => $r->location?->name ?? '—',
                'status'         => $r->status,
                'total_bookings' => $items->count(),
                'booked_hours'   => $hours,
                'capacity_hours' => $capHours,
                'utilization_pct'        => $pct,
                'decision_recommendation' => $decision,
                'decision_badge'         => $badge,
            ];
        });
    }

    private function buildCampusComparison(Carbon $from, Carbon $to, ?int $catId, int $days): array
    {
        $capPerRes = max(1, $days * 8);
        return Location::query()->ordered()->get()->map(function (Location $loc) use ($from, $to, $catId, $capPerRes) {
            $resCount = Resource::query()
                ->where('location_id', $loc->id)->where('status', 'active')
                ->when($catId, fn ($q) => $q->whereHas('type', fn ($tq) => $tq->where('resource_category_id', $catId)))
                ->count();

            $bookings = Reservation::query()
                ->where('location_id', $loc->id)
                ->whereDate('reservation_date', '>=', $from->toDateString())
                ->whereDate('reservation_date', '<=', $to->toDateString())
                ->count();

            $items = ReservationItem::query()
                ->whereHas('reservation', function (Builder $q) use ($from, $to, $loc) {
                    $q->where('location_id', $loc->id)
                        ->whereDate('reservation_date', '>=', $from->toDateString())
                        ->whereDate('reservation_date', '<=', $to->toDateString())
                        ->whereIn('status', [
                            ReservationStatus::APPROVED->value,
                            ReservationStatus::CONFIRMED->value,
                            ReservationStatus::COMPLETED->value,
                            ReservationStatus::IN_PROGRESS->value,
                        ]);
                })->get();

            $hours    = round($items->sum(fn ($i) => $i->starts_at && $i->ends_at ? max(0, $i->starts_at->diffInMinutes($i->ends_at)) : 0) / 60, 1);
            $cap      = max(1, $resCount * $capPerRes);
            $pct      = min(100, round(($hours / $cap) * 100, 1));

            [$rec, $tone] = match (true) {
                $resCount === 0  => ['No active facilities mapped', 'secondary'],
                $pct >= 75       => ['High congestion – restrict ad-hoc bookings', 'danger'],
                $pct >= 40       => ['Balanced load – ideal operation', 'success'],
                $bookings === 0  => ['Zero bookings – schedule intake cohorts here', 'warning'],
                default          => ['Available capacity – can absorb more sessions', 'info'],
            };

            return [
                'name'            => $loc->name,
                'resource_count'  => $resCount,
                'bookings_count'  => $bookings,
                'hours_booked'    => $hours,
                'utilization_pct' => $pct,
                'recommendation'  => $rec,
                'tone'            => $tone,
            ];
        })->values()->toArray();
    }

    private function buildPeakHours(Builder $baseQuery): array
    {
        $buckets = ['08-10' => 0, '10-12' => 0, '12-14' => 0, '14-16' => 0, '16-18' => 0, '18-20' => 0];
        $labels  = ['08:00–10:00', '10:00–12:00', '12:00–14:00', '14:00–16:00', '16:00–18:00', '18:00–20:00'];

        foreach ((clone $baseQuery)->whereNotNull('start_time')->get(['start_time']) as $r) {
            $h = (int) substr((string) $r->start_time, 0, 2);
            if ($h >= 8  && $h < 10) { $buckets['08-10']++; }
            elseif ($h >= 10 && $h < 12) { $buckets['10-12']++; }
            elseif ($h >= 12 && $h < 14) { $buckets['12-14']++; }
            elseif ($h >= 14 && $h < 16) { $buckets['14-16']++; }
            elseif ($h >= 16 && $h < 18) { $buckets['16-18']++; }
            elseif ($h >= 18 && $h <= 20) { $buckets['18-20']++; }
        }

        $max = max(1, max(array_values($buckets)));
        $out = [];
        foreach (array_values($buckets) as $i => $count) {
            $pct    = round(($count / $max) * 100);
            $status = $pct >= 75 ? ['Congested – shift load', 'danger']
                : ($pct <= 25 ? ['Open capacity – target this slot', 'success'] : ['Moderate', 'primary']);
            $out[] = ['slot' => $labels[$i], 'count' => $count, 'pct' => $pct, 'status' => $status[0], 'tone' => $status[1]];
        }
        return $out;
    }

    // -----------------------------------------------------------------------
    // TAB 2 – APPROVAL & BOOKING HEALTH
    // -----------------------------------------------------------------------

    private function buildApprovalHealth(Builder $baseQuery): array
    {
        $all       = (clone $baseQuery)->get(['id', 'status', 'submitted_at', 'approved_at', 'cancelled_at', 'cancellation_reason']);
        $pending   = $all->where('status', ReservationStatus::PENDING_APPROVAL->value);
        $approved  = $all->whereIn('status', [ReservationStatus::APPROVED->value, ReservationStatus::CONFIRMED->value]);
        $cancelled = $all->whereIn('status', [ReservationStatus::CANCELLED->value, ReservationStatus::REJECTED->value]);

        $turns = $approved->filter(fn ($r) => $r->submitted_at && $r->approved_at)
            ->map(fn ($r) => max(0.5, round($r->submitted_at->diffInMinutes($r->approved_at) / 60, 1)));
        $avgTurnaround = $turns->count() ? round($turns->average(), 1) : 0;

        $stale = $pending->filter(fn ($r) => $r->submitted_at && $r->submitted_at->diffInHours(now()) >= 24)->count();

        $conflict = $policy = $withdrawn = $other = 0;
        foreach ($cancelled as $r) {
            $reason = strtolower((string) $r->cancellation_reason);
            if (str_contains($reason, 'conflict') || str_contains($reason, 'overlap')) { $conflict++; }
            elseif (str_contains($reason, 'policy') || str_contains($reason, 'permission')) { $policy++; }
            elseif ($r->status === ReservationStatus::CANCELLED->value) { $withdrawn++; }
            else { $other++; }
        }

        return [
            'pending_count'       => $pending->count(),
            'stale_pending'       => $stale,
            'avg_turnaround_hrs'  => $avgTurnaround,
            'cancellation_causes' => [
                ['label' => 'Scheduling Conflict / Overlap', 'count' => $conflict, 'icon' => 'ti-calendar-off', 'tone' => 'danger'],
                ['label' => 'Requester Withdrawn',           'count' => $withdrawn, 'icon' => 'ti-user-minus', 'tone' => 'warning'],
                ['label' => 'Policy / Permission Issue',     'count' => $policy,    'icon' => 'ti-shield-x',   'tone' => 'secondary'],
                ['label' => 'Other / Operational',           'count' => $other,     'icon' => 'ti-dots',       'tone' => 'info'],
            ],
            'policy_alert' => $stale > 3
                ? "⚠ {$stale} requests are awaiting approval for over 24 hours. Consider SLA escalation."
                : null,
        ];
    }

    private function buildBookingTrend(Builder $baseQuery, Carbon $from, Carbon $to): array
    {
        $labels = [];
        $approved = [];
        $cancelled = [];
        $cursor = $from->copy();
        $diff   = $from->diffInDays($to);

        while ($cursor <= $to) {
            if ($diff <= 31) {
                $dStr = $cursor->toDateString();
                $labels[]    = $cursor->format('d M');
                $approved[]  = (clone $baseQuery)->whereDate('reservation_date', $dStr)
                    ->whereIn('status', [ReservationStatus::APPROVED->value, ReservationStatus::CONFIRMED->value, ReservationStatus::COMPLETED->value])->count();
                $cancelled[] = (clone $baseQuery)->whereDate('reservation_date', $dStr)
                    ->whereIn('status', [ReservationStatus::CANCELLED->value, ReservationStatus::REJECTED->value])->count();
                $cursor->addDay();
            } else {
                $wEnd = $cursor->copy()->endOfWeek();
                $labels[]    = $cursor->format('d M');
                $approved[]  = (clone $baseQuery)
                    ->whereDate('reservation_date', '>=', $cursor->toDateString())
                    ->whereDate('reservation_date', '<=', $wEnd->toDateString())
                    ->whereIn('status', [ReservationStatus::APPROVED->value, ReservationStatus::CONFIRMED->value, ReservationStatus::COMPLETED->value])->count();
                $cancelled[] = (clone $baseQuery)
                    ->whereDate('reservation_date', '>=', $cursor->toDateString())
                    ->whereDate('reservation_date', '<=', $wEnd->toDateString())
                    ->whereIn('status', [ReservationStatus::CANCELLED->value, ReservationStatus::REJECTED->value])->count();
                $cursor->addWeek();
            }
        }

        return ['labels' => $labels, 'approved' => $approved, 'cancelled' => $cancelled];
    }

    private function buildCategoryBreakdown(Carbon $from, Carbon $to, ?int $locId): array
    {
        $cats   = ResourceCategory::query()->orderBy('name')->get();
        $labels = [];
        $series = [];
        $colors = ['#1769c2', '#16a77a', '#d97706', '#7c3aed', '#dc3545', '#0284c7', '#e11d48', '#059669'];

        foreach ($cats as $cat) {
            $count = ReservationItem::query()
                ->whereHas('resource.type', fn ($q) => $q->where('resource_category_id', $cat->id))
                ->whereHas('reservation', function (Builder $q) use ($from, $to, $locId) {
                    $q->whereDate('reservation_date', '>=', $from->toDateString())
                        ->whereDate('reservation_date', '<=', $to->toDateString())
                        ->when($locId, fn ($lq) => $lq->where('location_id', $locId));
                })->count();
            if ($count > 0) {
                $labels[] = $cat->name;
                $series[] = $count;
            }
        }

        return ['labels' => $labels, 'series' => $series, 'colors' => array_slice($colors, 0, count($labels))];
    }

    // -----------------------------------------------------------------------
    // TAB 3 – FINANCIAL & LECTURER FEES
    // -----------------------------------------------------------------------

    private function buildFinancial(Carbon $from, Carbon $to, ?int $locId): array
    {
        // Lecturer payables in period
        $payables = LecturerPayable::query()
            ->with('lecturer', 'lectureSession')
            ->whereHas('lectureSession', function (Builder $q) use ($from, $to) {
                $q->whereDate('session_date', '>=', $from->toDateString())
                    ->whereDate('session_date', '<=', $to->toDateString());
            })->get();

        $totalPayable    = $payables->sum('net_amount_minor') / 100;  // convert minor units
        $paidPayables    = $payables->where('status', 'paid');
        $unpaidPayables  = $payables->where('status', 'unpaid');
        $pendingPayables = $payables->whereNotIn('status', ['paid', 'unpaid']);

        $totalPaid   = $paidPayables->sum('net_amount_minor') / 100;
        $totalUnpaid = $unpaidPayables->sum('net_amount_minor') / 100;

        // Per-lecturer summary
        $byLecturer = $payables->groupBy('lecturer_id')->map(function ($group) {
            $first = $group->first();
            return [
                'name'        => $first->lecturer?->name ?? 'Unknown',
                'sessions'    => $group->count(),
                'total_hours' => round($group->sum('hours'), 1),
                'total_lkr'   => round($group->sum('net_amount_minor') / 100, 2),
                'paid_lkr'    => round($group->where('status', 'paid')->sum('net_amount_minor') / 100, 2),
                'unpaid_lkr'  => round($group->where('status', 'unpaid')->sum('net_amount_minor') / 100, 2),
                'status'      => $group->where('status', 'unpaid')->count() > 0 ? 'has_unpaid' : 'all_paid',
            ];
        })->values();

        // Sessions per period
        $sessions = LectureSession::query()
            ->whereDate('session_date', '>=', $from->toDateString())
            ->whereDate('session_date', '<=', $to->toDateString())
            ->count();

        $totalHrs = LectureSession::query()
            ->whereDate('session_date', '>=', $from->toDateString())
            ->whereDate('session_date', '<=', $to->toDateString())
            ->sum('hours');

        $alerts = [];
        if ($payables->isEmpty()) {
            $alerts[] = ['type' => 'info', 'icon' => 'ti-info-circle', 'msg' => 'No lecturer payable records for this period.'];
        } elseif ($totalUnpaid > 0) {
            $alerts[] = ['type' => 'danger', 'icon' => 'ti-coin-off', 'msg' => number_format($totalUnpaid, 2).' LKR in lecturer fees remain unpaid. Process payments before next cycle.'];
            if ($unpaidPayables->count() > 5) {
                $alerts[] = ['type' => 'warning', 'icon' => 'ti-alert-triangle', 'msg' => $unpaidPayables->count().' payable records are unpaid. Risk of delayed disbursement.'];
            }
        } else {
            $alerts[] = ['type' => 'success', 'icon' => 'ti-circle-check', 'msg' => 'All lecturer payables for this period are settled.'];
        }

        return [
            'total_payable_lkr' => round($totalPayable, 2),
            'total_paid_lkr'    => round($totalPaid, 2),
            'total_unpaid_lkr'  => round($totalUnpaid, 2),
            'total_sessions'    => $sessions,
            'total_hours'       => round((float) $totalHrs, 1),
            'payable_count'     => $payables->count(),
            'unpaid_count'      => $unpaidPayables->count(),
            'by_lecturer'       => $byLecturer,
            'alerts'            => $alerts,
        ];
    }

    // -----------------------------------------------------------------------
    // TAB 4 – HOSTEL
    // -----------------------------------------------------------------------

    private function buildHostel(Carbon $from, Carbon $to, ?int $locId): array
    {
        $stays = HostelStayDetail::query()
            ->with(['reservation.location', 'roomType', 'roomCategory'])
            ->whereHas('reservation', function (Builder $q) use ($from, $to, $locId) {
                $q->whereDate('reservation_date', '>=', $from->toDateString())
                    ->whereDate('reservation_date', '<=', $to->toDateString())
                    ->when($locId, fn ($lq) => $lq->where('location_id', $locId));
            })->get();

        $checkedIn  = $stays->whereNotNull('check_in_at')->whereNull('check_out_at')->count();
        $checkedOut = $stays->whereNotNull('check_out_at')->count();
        $upcoming   = $stays->whereNull('check_in_at')->count();
        $totalGuests = $stays->sum('number_of_guests');

        // Average stay duration (in days)
        $avgStay = $stays->filter(fn ($s) => $s->check_in_at && $s->check_out_at)
            ->map(fn ($s) => max(1, $s->check_in_at->diffInDays($s->check_out_at)))
            ->average();

        // By room category
        $byCategory = $stays->groupBy(fn ($s) => $s->roomCategory?->name ?? 'Unspecified')
            ->map(fn ($group, $name) => [
                'category'    => $name,
                'total_stays' => $group->count(),
                'guests'      => $group->sum('number_of_guests'),
            ])->values();

        $alerts = [];
        if ($checkedIn > 20) {
            $alerts[] = ['type' => 'warning', 'icon' => 'ti-home-exclamation', 'msg' => "High occupancy: {$checkedIn} guests currently checked in. Ensure housekeeping is scaled up."];
        }
        if ($upcoming > 10) {
            $alerts[] = ['type' => 'info', 'icon' => 'ti-home-check', 'msg' => "{$upcoming} upcoming hostel reservations need room pre-assignment."];
        }
        if (empty($alerts)) {
            $alerts[] = ['type' => 'success', 'icon' => 'ti-circle-check', 'msg' => 'Hostel load is within normal operational range.'];
        }

        return [
            'checked_in'   => $checkedIn,
            'checked_out'  => $checkedOut,
            'upcoming'     => $upcoming,
            'total_guests' => $totalGuests,
            'avg_stay_days' => $avgStay ? round($avgStay, 1) : 0,
            'by_category'  => $byCategory,
            'alerts'       => $alerts,
        ];
    }

    // -----------------------------------------------------------------------
    // TAB 5 – CANTEEN
    // -----------------------------------------------------------------------

    private function buildCanteen(Carbon $from, Carbon $to, ?int $locId): array
    {
        $orders = CanteenReservation::query()
            ->whereDate('reservation_date', '>=', $from->toDateString())
            ->whereDate('reservation_date', '<=', $to->toDateString())
            ->when($locId, fn ($q) => $q->where('location_id', $locId))
            ->get();

        $totalOrders   = $orders->count();
        $totalMeals    = $orders->sum('number_of_orders');
        $confirmed     = $orders->where('status', CanteenReservationStatus::CONFIRMED->value)->count();
        $pending       = $orders->where('status', CanteenReservationStatus::PENDING->value)->count();
        $cancelled     = $orders->whereIn('status', [CanteenReservationStatus::CANCELLED->value, CanteenReservationStatus::REJECTED->value])->count();
        $avgPerOrder   = $totalOrders > 0 ? round($totalMeals / $totalOrders, 1) : 0;

        // By meal type
        $byMealType = $orders->groupBy('meal_type')
            ->map(fn ($group, $type) => [
                'type'   => ucfirst(str_replace('_', ' ', $type ?: 'Other')),
                'orders' => $group->count(),
                'meals'  => $group->sum('number_of_orders'),
            ])->values();

        // Large group orders (> threshold)
        $threshold   = (int) config('canteen.large_group_threshold', 50);
        $largeGroups = $orders->where('number_of_orders', '>=', $threshold)->count();

        // Daily average
        $daysPeriod = max(1, $from->diffInDays($to) + 1);
        $dailyAvg   = round($totalMeals / $daysPeriod, 1);

        $alerts = [];
        if ($pending > 0) {
            $alerts[] = ['type' => 'warning', 'icon' => 'ti-chef-hat', 'msg' => "{$pending} canteen orders are awaiting confirmation. Notify the kitchen team."];
        }
        if ($largeGroups > 3) {
            $alerts[] = ['type' => 'info', 'icon' => 'ti-users', 'msg' => "{$largeGroups} large group orders (≥{$threshold} pax) detected. Ensure stock and staffing is prepared."];
        }
        if (empty($alerts)) {
            $alerts[] = ['type' => 'success', 'icon' => 'ti-circle-check', 'msg' => 'Canteen operations are running smoothly for this period.'];
        }

        return [
            'total_orders'   => $totalOrders,
            'total_meals'    => $totalMeals,
            'confirmed'      => $confirmed,
            'pending'        => $pending,
            'cancelled'      => $cancelled,
            'avg_per_order'  => $avgPerOrder,
            'daily_avg'      => $dailyAvg,
            'large_groups'   => $largeGroups,
            'by_meal_type'   => $byMealType,
            'alerts'         => $alerts,
        ];
    }
}
