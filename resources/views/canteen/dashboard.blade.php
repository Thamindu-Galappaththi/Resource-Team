@extends('layouts.app')

@section('title', 'Canteen Dashboard')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/canteen-dashboard.css') }}?v={{ @filemtime(public_path('css/canteen-dashboard.css')) ?: time() }}">
@endpush

@php
    $mealMax = max(1, (int) collect($mealBreakdown ?? [])->max('orders'));
    $todayKey = now()->toDateString();
@endphp

@section('content')
<div class="container-fluid py-4 cd-page">
    <header class="cd-hero">
        <div class="cd-hero__copy">
            <p class="cd-hero__badge">
                <i class="ti ti-tools-kitchen-2" aria-hidden="true"></i>
                Canteen operations
            </p>
            <h1>Canteen dashboard</h1>
            <p class="cd-hero__sub">Plan service around approved and pending meal demand.</p>
        </div>
        <nav class="cd-hero__actions" aria-label="Canteen shortcuts">
            @can('viewAny', \App\Models\CanteenReservation::class)
                <a href="{{ route('canteen.index') }}" class="cd-btn cd-btn--outline">
                    <i class="ti ti-list-check" aria-hidden="true"></i>
                    <span>Existing reservations</span>
                </a>
            @endcan
            @can('create', \App\Models\CanteenReservation::class)
                <a href="{{ route('canteen.create') }}" class="cd-btn cd-btn--solid">
                    <i class="ti ti-plus" aria-hidden="true"></i>
                    <span>New reservation</span>
                </a>
            @endcan
        </nav>
    </header>

    <section class="cd-kpis" aria-label="Reservation summary">
        <article class="cd-kpi tone-primary">
            <div class="cd-kpi__top">
                <span class="cd-kpi__label">Total reservations</span>
                <span class="cd-kpi__icon" aria-hidden="true"><i class="ti ti-calendar-stats"></i></span>
            </div>
            <span class="cd-kpi__value">{{ number_format($summary['total'] ?? 0) }}</span>
            <span class="cd-kpi__hint">All recorded orders</span>
        </article>
        <article class="cd-kpi tone-warning">
            <div class="cd-kpi__top">
                <span class="cd-kpi__label">Pending approvals</span>
                <span class="cd-kpi__icon" aria-hidden="true"><i class="ti ti-clock-hour-4"></i></span>
            </div>
            <span class="cd-kpi__value">{{ number_format($summary['pending'] ?? 0) }}</span>
            <span class="cd-kpi__hint">Waiting for a decision</span>
        </article>
        <article class="cd-kpi tone-success">
            <div class="cd-kpi__top">
                <span class="cd-kpi__label">Approved</span>
                <span class="cd-kpi__icon" aria-hidden="true"><i class="ti ti-circle-check"></i></span>
            </div>
            <span class="cd-kpi__value">{{ number_format($summary['approved'] ?? 0) }}</span>
            <span class="cd-kpi__hint">Kitchen can prepare</span>
        </article>
        <article class="cd-kpi tone-danger">
            <div class="cd-kpi__top">
                <span class="cd-kpi__label">Rejected</span>
                <span class="cd-kpi__icon" aria-hidden="true"><i class="ti ti-circle-x"></i></span>
            </div>
            <span class="cd-kpi__value">{{ number_format($summary['rejected'] ?? 0) }}</span>
            <span class="cd-kpi__hint">Not going ahead</span>
        </article>
    </section>

    @if($canReview)
        <section class="cd-card" aria-labelledby="reviewQueueHeading">
            <div class="cd-card__head">
                <div>
                    <p class="cd-card__eyebrow"><i class="ti ti-inbox" aria-hidden="true"></i> Approvals</p>
                    <h2 id="reviewQueueHeading">Pending requests</h2>
                    <p class="cd-card__sub">{{ number_format($pendingApprovals->count()) }} {{ \Illuminate\Support\Str::plural('request', $pendingApprovals->count()) }} awaiting review</p>
                </div>
                <a href="{{ route('canteen.index', ['status' => 'pending']) }}" class="cd-link">
                    View reservation list <i class="ti ti-arrow-up-right" aria-hidden="true"></i>
                </a>
            </div>

            <div class="cd-card__body">
                @forelse($pendingApprovals as $reservation)
                    <article class="cd-request">
                        <div class="cd-request__icon" aria-hidden="true">
                            <i class="ti ti-tools-kitchen-2"></i>
                        </div>
                        <div class="cd-request__info">
                            <a class="cd-request__title" href="{{ route('canteen.show', $reservation) }}">{{ $reservation->reservation_name }}</a>
                            <span class="cd-request__ref">{{ $reservation->reservation_ref }}</span>
                            <div class="cd-request__meta">
                                <span>{{ $reservation->requestedBy?->name ?? 'Unknown requester' }}</span>
                                <span>{{ $reservation->reservation_date->format('d M Y') }} · {{ $reservation->serviceTimeLabel() }}</span>
                                <span>{{ $reservation->mealTypeLabel() }} · {{ number_format($reservation->number_of_orders) }} orders</span>
                                @if($reservation->location)<span>{{ $reservation->location->name }}</span>@endif
                            </div>
                        </div>
                        @can('manageStatus', $reservation)
                            <div class="cd-request__actions">
                                <form method="POST" action="{{ route('canteen.status', $reservation) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="approved">
                                    <button class="cd-chip cd-chip--ok" type="submit"><i class="ti ti-check" aria-hidden="true"></i>Approve</button>
                                </form>
                                <button class="cd-chip cd-chip--no" type="button" data-reject-toggle="#rejectDashboard-{{ $reservation->id }}" aria-expanded="false" aria-controls="rejectDashboard-{{ $reservation->id }}">
                                    <i class="ti ti-x" aria-hidden="true"></i>Reject
                                </button>
                            </div>
                            <form id="rejectDashboard-{{ $reservation->id }}" class="cd-reject-form" method="POST" action="{{ route('canteen.status', $reservation) }}" hidden>
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="rejected">
                                <label class="form-label" for="rejectDashboardReason-{{ $reservation->id }}">Reason for rejection</label>
                                <textarea id="rejectDashboardReason-{{ $reservation->id }}" name="approval_comments" class="form-control" rows="2" maxlength="1000" required></textarea>
                                <div class="cd-reject-actions">
                                    <button class="btn btn-danger" type="submit">Confirm rejection</button>
                                    <button class="btn btn-outline-secondary" type="button" data-reject-close>Keep pending</button>
                                </div>
                            </form>
                        @endcan
                    </article>
                @empty
                    <div class="cd-empty">
                        <i class="ti ti-circle-check" aria-hidden="true"></i>
                        <span>All requests are up to date.</span>
                    </div>
                @endforelse
            </div>
        </section>
    @endif

    <section class="cd-card" aria-labelledby="todayMetricsHeading">
        <div class="cd-card__head">
            <div>
                <h2 id="todayMetricsHeading">Today at a glance</h2>
                <p class="cd-card__sub">Meal demand and service planning</p>
            </div>
        </div>
        <div class="cd-glance">
            <article class="cd-glance__item">
                <div class="cd-glance__label"><i class="ti ti-users" aria-hidden="true"></i>Expected today</div>
                <p class="cd-glance__value">{{ number_format($todayTotal ?? 0) }} <small>orders</small></p>
            </article>
            <article class="cd-glance__item">
                <div class="cd-glance__label"><i class="ti ti-tools-kitchen-2" aria-hidden="true"></i>Approved meals</div>
                <p class="cd-glance__value">{{ number_format($todayConfirmed ?? 0) }} <small>orders</small></p>
            </article>
            <article class="cd-glance__item">
                <div class="cd-glance__label"><i class="ti ti-clock" aria-hidden="true"></i>Awaiting review</div>
                <p class="cd-glance__value">{{ number_format($todayPending ?? 0) }} <small>groups</small></p>
            </article>
            <article class="cd-glance__item">
                <div class="cd-glance__label"><i class="ti ti-clock-hour-4" aria-hidden="true"></i>Busiest service slot</div>
                <p class="cd-glance__value">{{ $peakSlot ?? 'No bookings' }}</p>
            </article>
        </div>
    </section>

    <div class="cd-split">
        <section class="cd-card" aria-labelledby="mealBreakdownHeading">
            <div class="cd-card__head">
                <div>
                    <h2 id="mealBreakdownHeading">Today by meal</h2>
                    <p class="cd-card__sub">Pending and approved orders</p>
                </div>
            </div>
            <div class="cd-card__body">
                @forelse($mealBreakdown as $row)
                    @php
                        $pct = (int) round(100 * ($row['orders'] / $mealMax));
                    @endphp
                    <div class="cd-meal">
                        <span class="cd-meal__icon" aria-hidden="true"><i class="ti ti-tools-kitchen-2"></i></span>
                        <div class="cd-meal__copy">
                            <div class="cd-meal__name">{{ $row['meal'] }}</div>
                            <div class="cd-meal__meta">{{ number_format($row['groups']) }} {{ \Illuminate\Support\Str::plural('group', $row['groups']) }}</div>
                            <div class="cd-meal__bar" aria-hidden="true"><span style="width: {{ $pct }}%"></span></div>
                        </div>
                        <div class="cd-meal__orders">{{ number_format($row['orders']) }}</div>
                    </div>
                @empty
                    <p class="cd-empty">No pending or approved orders for today.</p>
                @endforelse
            </div>
        </section>

        <section class="cd-card" aria-labelledby="forecastHeading">
            <div class="cd-card__head">
                <div>
                    <h2 id="forecastHeading"><i class="ti ti-calendar-time" aria-hidden="true"></i> Next 7 days</h2>
                    <p class="cd-card__sub">Pending and approved orders only</p>
                </div>
            </div>
            <div class="cd-forecast">
                @foreach($forecast as $entry)
                    @php $isToday = ($entry['date'] ?? '') === $todayKey; @endphp
                    <a href="{{ route('canteen.forecast', ['date' => $entry['date']]) }}" class="cd-day{{ $isToday ? ' is-today' : '' }}">
                        <div class="cd-day__top">
                            <strong>{{ \Carbon\Carbon::parse($entry['date'])->format('D') }}</strong>
                            <i class="ti ti-arrow-up-right" aria-hidden="true"></i>
                        </div>
                        <div class="cd-day__date">{{ \Carbon\Carbon::parse($entry['date'])->format('d M') }}</div>
                        <div class="cd-day__total">{{ number_format($entry['total'] ?? 0) }} <small>orders</small></div>
                    </a>
                @endforeach
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/canteen-ui.js') }}?v=2"></script>
@endpush
