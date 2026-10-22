@extends('layouts.app')

@section('title', 'Existing Canteen Reservations')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/create-user.css') }}?v=15">
<link rel="stylesheet" href="{{ asset('css/canteen-dashboard.css') }}?v={{ @filemtime(public_path('css/canteen-dashboard.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('css/canteen-pages.css') }}?v={{ @filemtime(public_path('css/canteen-pages.css')) ?: time() }}">
@endpush

@section('content')
<div class="container-fluid py-4 cp-page">
    <header class="cd-header">
        <div>
            <div class="cd-eyebrow"><i class="ti ti-tools-kitchen-2" aria-hidden="true"></i> Canteen operations</div>
            <h1 class="cd-title">Existing reservations</h1>
            <p class="cd-subtitle">Review requests, status, and upcoming meal service.</p>
        </div>
        <div class="cd-actions">
        @can('create', \App\Models\CanteenReservation::class)
            <a href="{{ route('canteen.create') }}" class="btn btn-primary"><i class="ti ti-plus me-1" aria-hidden="true"></i>New reservation</a>
        @endcan
        </div>
    </header>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <main class="cp-surface">
        <section class="cp-summary" aria-label="Reservation summary">
            <div class="cp-stat" style="--cp-tone: #1769c2">
                <div class="cp-stat-label"><i class="ti ti-calendar-stats" aria-hidden="true"></i>Reservations</div>
                <div class="cp-stat-value">{{ number_format($summary['total'] ?? 0) }}</div>
            </div>
            <div class="cp-stat" style="--cp-tone: #d97706">
                <div class="cp-stat-label"><i class="ti ti-clock-hour-4" aria-hidden="true"></i>Awaiting review</div>
                <div class="cp-stat-value">{{ number_format($summary['pending'] ?? 0) }}</div>
            </div>
            <div class="cp-stat" style="--cp-tone: #16a77a">
                <div class="cp-stat-label"><i class="ti ti-circle-check" aria-hidden="true"></i>Approved</div>
                <div class="cp-stat-value">{{ number_format($summary['approved'] ?? 0) }}</div>
            </div>
            <div class="cp-stat" style="--cp-tone: #dc4c5a">
                <div class="cp-stat-label"><i class="ti ti-circle-x" aria-hidden="true"></i>Rejected</div>
                <div class="cp-stat-value">{{ number_format($summary['rejected'] ?? 0) }}</div>
            </div>
        </section>

        <section class="cp-filter-area" aria-label="Filter reservations">
            <form method="GET" action="{{ route('canteen.index') }}" class="cp-filters" data-action-lock="off">
                <div class="cp-filter cp-f-search">
                    <label class="form-label" for="search">Search</label>
                    <input id="search" type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Reference, reservation, requester">
                </div>
                <div class="cp-filter">
                    <label class="form-label" for="from_date">From</label>
                    <input id="from_date" type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                </div>
                <div class="cp-filter">
                    <label class="form-label" for="to_date">To</label>
                    <input id="to_date" type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                </div>
                @php
                    $selectedMeal = collect(\App\Enums\MealType::cases())->first(fn ($type) => $type->value === request('meal_type'));
                    $statusOptions = [\App\Enums\CanteenReservationStatus::PENDING, \App\Enums\CanteenReservationStatus::APPROVED, \App\Enums\CanteenReservationStatus::REJECTED];
                    $selectedCanteenStatus = collect($statusOptions)->first(fn ($status) => $status->value === request('status'));
                    $selectedRequester = $requesters->firstWhere('id', (int) request('requested_by_user_id'));
                @endphp
                <div class="cp-filter">
                    <label class="form-label" for="meal_type">Meal type</label>
                    <x-rm-select id="meal_type" name="meal_type" placeholder="All meals" :value="(string) request('meal_type', '')" :selected-label="$selectedMeal?->label() ?? 'All meals'"
                        :options="collect([['value' => '', 'label' => 'All meals']])->concat(collect(\App\Enums\MealType::cases())->map(fn ($type) => ['value' => $type->value, 'label' => $type->label()]))->all()" />
                </div>
                <div class="cp-filter">
                    <label class="form-label" for="status">Status</label>
                    <x-rm-select id="status" name="status" placeholder="All statuses" :value="(string) request('status', '')" :selected-label="$selectedCanteenStatus?->label() ?? 'All statuses'"
                        :options="collect([['value' => '', 'label' => 'All statuses']])->concat(collect($statusOptions)->map(fn ($status) => ['value' => $status->value, 'label' => $status->label()]))->all()" />
                </div>
                @if($requesters->isNotEmpty())
                    <div class="cp-filter">
                        <label class="form-label" for="requested_by_user_id">Requester</label>
                        <x-rm-select id="requested_by_user_id" name="requested_by_user_id" placeholder="All requesters" :value="(string) request('requested_by_user_id', '')" :selected-label="$selectedRequester?->name ?? 'All requesters'"
                            :options="collect([['value' => '', 'label' => 'All requesters']])->concat($requesters->map(fn ($requester) => ['value' => (string) $requester->id, 'label' => $requester->name]))->all()" />
                    </div>
                @endif
                <div class="cp-filter-actions">
                    <button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>Apply</button>
                    @if(request()->query())
                        <a href="{{ route('canteen.index') }}" class="btn btn-outline-secondary">Clear</a>
                    @endif
                </div>
            </form>
        </section>

        <section class="cp-register">
            <div class="cp-register-heading">
                <h2 class="h5 mb-0">Reservation register</h2>
                <span class="cp-record-count">{{ $reservations->total() }} records</span>
            </div>
            @if($reservations->isEmpty())
                <div class="cp-empty-state">
                    <i class="ti ti-calendar-off fs-1 text-muted" aria-hidden="true"></i>
                    <h3>No reservations found</h3>
                    <p class="text-muted mb-0">Try adjusting your filters or create a new reservation.</p>
                </div>
            @else
                <div class="cp-table-scroll">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Reference / reservation</th>
                                <th scope="col">Requester</th>
                                <th scope="col">Service</th>
                                <th scope="col">Meal</th>
                                <th scope="col">Location</th>
                                <th scope="col">Orders</th>
                                <th scope="col">Status</th>
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reservations as $reservation)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $reservation->reservation_name }}</div>
                                        <span class="small text-muted font-monospace">{{ $reservation->reservation_ref }}</span>
                                    </td>
                                    <td>{{ $reservation->requestedBy?->name ?? 'Unknown' }}</td>
                                    <td>
                                        <div>{{ $reservation->reservation_date->format('d M Y') }}</div>
                                        <span class="small text-muted">{{ $reservation->serviceTimeLabel() }}</span>
                                    </td>
                                    <td>{{ $reservation->mealTypeLabel() }}</td>
                                    <td>{{ $reservation->location?->name ?? '—' }}</td>
                                    <td class="fw-semibold">{{ number_format($reservation->number_of_orders) }}</td>
                                    <td>
                                        <span class="badge {{ $reservation->statusEnum()->badgeClass() }}">{{ $reservation->statusEnum()->label() }}</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex flex-wrap justify-content-end gap-1">
                                            @can('view', $reservation)
                                                <a href="{{ route('canteen.show', $reservation) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                            @endcan
                                            @can('update', $reservation)
                                                <a href="{{ route('canteen.edit', $reservation) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                            @endcan
                                            @can('delete', $reservation)
                                                <form action="{{ route('canteen.destroy', $reservation) }}" method="POST" class="d-inline" onsubmit="return confirm('Permanently delete this reservation?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                                </form>
                                            @elsecan('cancel', $reservation)
                                                <form action="{{ route('canteen.destroy', $reservation) }}" method="POST" class="d-inline" onsubmit="return confirm('Cancel this reservation?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-sm btn-outline-danger">Cancel</button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
            @if($reservations->hasPages())
                <div class="cd-pager mt-3">
                    <p>Showing {{ $reservations->firstItem() }}–{{ $reservations->lastItem() }} of {{ $reservations->total() }}</p>
                    {{ $reservations->onEachSide(1)->links() }}
                </div>
            @endif
        </section>
    </main>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/resource-select.js') }}?v=2"></script>
<script src="{{ asset('js/cu-dropdowns.js') }}?v=4"></script>
<script src="{{ asset('js/canteen-ui.js') }}?v=2"></script>
@endpush
