@extends('layouts.app')

@section('title', 'Canteen Dashboard')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/canteen-dashboard.css') }}?v=1">
@endpush

@section('content')
<div class="container-fluid py-4 cd-page">
    <header class="cd-header">
        <div>
            <div class="cd-eyebrow"><i class="ti ti-tools-kitchen-2" aria-hidden="true"></i> Canteen operations</div>
            <h1 class="cd-title">Canteen dashboard</h1>
            <p class="cd-subtitle">Plan service around approved and pending meal demand.</p>
        </div>
        <div class="cd-actions">
            @can('viewAny', \App\Models\CanteenReservation::class)
                <a href="{{ route('canteen.index') }}" class="btn btn-outline-light"><i class="ti ti-list-check me-1" aria-hidden="true"></i>Existing reservations</a>
            @endcan
            @can('create', \App\Models\CanteenReservation::class)
                <a href="{{ route('canteen.create') }}" class="btn btn-primary"><i class="ti ti-plus me-1" aria-hidden="true"></i>New reservation</a>
            @endcan
        </div>
    </header>

    <main class="cd-panel">
        <section class="cd-summary" aria-label="Reservation summary">
            <div class="cd-summary-item" style="--metric-color: #1769c2">
                <div class="cd-summary-label"><i class="ti ti-calendar-stats" aria-hidden="true"></i>Total reservations</div>
                <div class="cd-summary-value">{{ number_format($summary['total'] ?? 0) }}</div>
            </div>
            <div class="cd-summary-item" style="--metric-color: #d97706">
                <div class="cd-summary-label"><i class="ti ti-clock-hour-4" aria-hidden="true"></i>Pending approvals</div>
                <div class="cd-summary-value">{{ number_format($summary['pending'] ?? 0) }}</div>
            </div>
            <div class="cd-summary-item" style="--metric-color: #16a77a">
                <div class="cd-summary-label"><i class="ti ti-circle-check" aria-hidden="true"></i>Approved</div>
                <div class="cd-summary-value">{{ number_format($summary['approved'] ?? 0) }}</div>
            </div>
            <div class="cd-summary-item" style="--metric-color: #dc4c5a">
                <div class="cd-summary-label"><i class="ti ti-circle-x" aria-hidden="true"></i>Rejected</div>
                <div class="cd-summary-value">{{ number_format($summary['rejected'] ?? 0) }}</div>
            </div>
        </section>

        @if($canReview)
            <section class="cd-review-queue" aria-labelledby="reviewQueueHeading">
                <div class="cd-review-heading">
                    <div>
                        <div class="cd-eyebrow cd-review-eyebrow"><i class="ti ti-inbox" aria-hidden="true"></i> Approvals</div>
                        <h2 id="reviewQueueHeading">Pending requests</h2>
                        <p>{{ number_format($pendingApprovals->count()) }} {{ \Illuminate\Support\Str::plural('request', $pendingApprovals->count()) }} awaiting review</p>
                    </div>
                    <a href="{{ route('canteen.index', ['status' => 'pending']) }}" class="cd-review-all">View reservation list <i class="ti ti-arrow-up-right" aria-hidden="true"></i></a>
                </div>

                @forelse($pendingApprovals as $reservation)
                    <article class="cd-review-item">
                        <div class="cd-review-info">
                            <a class="cd-review-title" href="{{ route('canteen.show', $reservation) }}">{{ $reservation->reservation_name }}</a>
                            <span class="cd-review-ref">{{ $reservation->reservation_ref }}</span>
                            <div class="cd-review-meta">
                                <span>{{ $reservation->requestedBy?->name ?? 'Unknown requester' }}</span>
                                <span>{{ $reservation->reservation_date->format('d M Y') }} · {{ $reservation->serviceTimeLabel() }}</span>
                                <span>{{ $reservation->mealTypeLabel() }} · {{ number_format($reservation->number_of_orders) }} orders</span>
                                @if($reservation->location)<span>{{ $reservation->location->name }}</span>@endif
                            </div>
                        </div>
                        @can('manageStatus', $reservation)
                            <div class="cd-review-actions">
                                <form method="POST" action="{{ route('canteen.status', $reservation) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="approved">
                                    <button class="btn btn-success" type="submit"><i class="ti ti-check me-1" aria-hidden="true"></i>Approve</button>
                                </form>
                                <button class="btn btn-outline-danger" type="button" data-reject-toggle="#rejectDashboard-{{ $reservation->id }}" aria-expanded="false" aria-controls="rejectDashboard-{{ $reservation->id }}">
                                    <i class="ti ti-x me-1" aria-hidden="true"></i>Reject
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
                    <div class="cd-review-empty"><i class="ti ti-circle-check" aria-hidden="true"></i><span>All requests are up to date.</span></div>
                @endforelse
            </section>
        @endif

        <div class="cd-content">
            <section aria-labelledby="todayMetricsHeading">
                <div class="cd-section-heading">
                    <div>
                        <h2 id="todayMetricsHeading">Today at a glance</h2>
                        <p>Meal demand and service planning</p>
                    </div>
                </div>
                <div class="cd-today-metrics">
                    <div class="cd-today-item">
                        <div class="cd-today-label"><i class="ti ti-users" aria-hidden="true"></i>Expected today</div>
                        <span class="cd-today-value">{{ number_format($todayTotal ?? 0) }} <span class="text-muted fs-6 fw-normal">orders</span></span>
                    </div>
                    <div class="cd-today-item">
                        <div class="cd-today-label"><i class="ti ti-tools-kitchen-2" aria-hidden="true"></i>Approved meals</div>
                        <span class="cd-today-value">{{ number_format($todayConfirmed ?? 0) }} <span class="text-muted fs-6 fw-normal">orders</span></span>
                    </div>
                    <div class="cd-today-item">
                        <div class="cd-today-label"><i class="ti ti-clock" aria-hidden="true"></i>Awaiting review</div>
                        <span class="cd-today-value">{{ number_format($todayPending ?? 0) }} <span class="text-muted fs-6 fw-normal">groups</span></span>
                    </div>
                    <div class="cd-today-item">
                        <div class="cd-today-label"><i class="ti ti-clock-hour-4" aria-hidden="true"></i>Busiest service slot</div>
                        <span class="cd-today-value">{{ $peakSlot ?? 'No bookings' }}</span>
                    </div>
                </div>
            </section>

            <div class="cd-lower">
                <section aria-labelledby="mealBreakdownHeading">
                    <div class="cd-section-heading">
                        <div>
                            <h2 id="mealBreakdownHeading">Today by meal</h2>
                            <p>Pending and approved orders</p>
                        </div>
                    </div>
                    @forelse($mealBreakdown as $row)
                        <div class="cd-meal-row">
                            <div>
                                <div class="cd-meal-name">{{ $row['meal'] }}</div>
                                <div class="cd-meal-meta">{{ number_format($row['groups']) }} {{ \Illuminate\Support\Str::plural('group', $row['groups']) }}</div>
                            </div>
                            <div class="cd-meal-orders">{{ number_format($row['orders']) }}</div>
                        </div>
                    @empty
                        <p class="cd-empty">No pending or approved orders for today.</p>
                    @endforelse
                </section>

                <section aria-labelledby="forecastHeading">
                    <div class="cd-section-heading">
                        <div>
                            <h2 id="forecastHeading"><i class="ti ti-calendar-time" aria-hidden="true"></i> Next 7 days</h2>
                            <p>Pending and approved orders only</p>
                        </div>
                    </div>
                    <div class="cd-forecast-grid">
                        @foreach($forecast as $entry)
                            <a href="{{ route('canteen.forecast', ['date' => $entry['date']]) }}" class="cd-forecast-day">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                    <div>
                                        <div class="fw-semibold">{{ \Carbon\Carbon::parse($entry['date'])->format('D') }}</div>
                                        <div class="cd-forecast-date">{{ \Carbon\Carbon::parse($entry['date'])->format('d M') }}</div>
                                    </div>
                                    <i class="ti ti-arrow-up-right text-muted" aria-hidden="true"></i>
                                </div>
                                <div class="cd-forecast-total">{{ number_format($entry['total'] ?? 0) }} <small>orders</small></div>
                            </a>
                        @endforeach
                    </div>
                </section>
            </div>
        </div>
    </main>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/canteen-ui.js') }}?v=1"></script>
@endpush
