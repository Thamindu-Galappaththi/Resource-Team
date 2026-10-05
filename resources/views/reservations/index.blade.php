@extends('layouts.app')

@section('title', 'Existing Reservations')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Existing Reservations</h2>
            <p class="text-muted mb-0">Search, filter, and review reservation history.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('reservations.calendar') }}" class="btn btn-outline-secondary">Calendar</a>
            @can('create', \App\Models\Reservation::class)
                <a href="{{ route('reservations.create') }}" class="btn btn-primary">Create Reservation</a>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100"><div class="card-body"><div class="text-muted small">Total</div><h3>{{ $summary['total'] }}</h3></div></div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100"><div class="card-body"><div class="text-muted small">Pending approval</div><h3>{{ $summary['pending'] }}</h3></div></div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100"><div class="card-body"><div class="text-muted small">Approved / confirmed</div><h3>{{ $summary['approved'] }}</h3></div></div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 h-100"><div class="card-body"><div class="text-muted small">Rejected / cancelled</div><h3>{{ $summary['cancelled'] }}</h3></div></div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('reservations.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="ID, purpose, requester, resource">
                </div>
                <div class="col-md-2">
                    <label class="form-label">From</label>
                    <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To</label>
                    <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Location</label>
                    <select name="location_id" class="form-select">
                        <option value="">All</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" @selected(request('location_id') == $location->id)>{{ $location->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Category</label>
                    <select name="resource_category_id" class="form-select">
                        <option value="">All</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(request('resource_category_id') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Resource</label>
                    <select name="resource_id" class="form-select">
                        <option value="">All</option>
                        @foreach($resources as $resource)
                            <option value="{{ $resource->id }}" @selected(request('resource_id') == $resource->id)>{{ $resource->name_model }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        @foreach($statuses as $status)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-primary">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            @if($reservations->isEmpty())
                <div class="p-4">
                    <div class="alert alert-info mb-0">No reservations found.</div>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Reservation ID</th>
                                <th>Purpose</th>
                                <th>Resource</th>
                                <th>Requester</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reservations as $reservation)
                                <tr>
                                    <td>{{ $reservation->reference }}</td>
                                    <td>{{ $reservation->purpose }}</td>
                                    <td>{{ $reservation->resourceNames() ?: '—' }}</td>
                                    <td>{{ $reservation->requester?->name ?? 'Unknown' }}</td>
                                    <td>{{ $reservation->reservation_date?->format('d M Y') }}</td>
                                    <td>{{ substr((string) $reservation->start_time, 0, 5) }}–{{ substr((string) $reservation->end_time, 0, 5) }}</td>
                                    <td>{{ $reservation->location?->name ?? '—' }}</td>
                                    <td><span class="badge rounded-pill px-3 {{ $reservation->statusEnum()->badgeClass() }}">{{ $reservation->statusEnum()->label() }}</span></td>
                                    <td class="text-nowrap">
                                        @can('view', $reservation)
                                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#viewModal-{{ $reservation->id }}" aria-label="View reservation {{ $reservation->reference }}">View</button>
                                        @endcan
                                        @can('cancel', $reservation)
                                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal-{{ $reservation->id }}">Cancel</button>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="mt-3">{{ $reservations->links() }}</div>
</div>

@foreach($reservations as $reservation)
    @can('view', $reservation)
        <div class="modal fade" id="viewModal-{{ $reservation->id }}" tabindex="-1" aria-labelledby="viewModalTitle-{{ $reservation->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light">
                        <div>
                            <h2 class="modal-title fs-5 mb-1" id="viewModalTitle-{{ $reservation->id }}">{{ $reservation->reference }}</h2>
                            <div class="small text-muted">{{ $reservation->title }}</div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge rounded-pill px-3 {{ $reservation->statusEnum()->badgeClass() }}">{{ $reservation->statusEnum()->label() }}</span>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row g-4">
                            <div class="col-lg-7">
                                <section aria-labelledby="reservationDetails-{{ $reservation->id }}">
                                    <h3 class="fs-6 mb-3" id="reservationDetails-{{ $reservation->id }}">Reservation details</h3>
                                    <div class="row g-3">
                                        <div class="col-sm-6"><div class="small text-muted">Purpose</div><div class="fw-medium">{{ $reservation->purpose ?: '—' }}</div></div>
                                        <div class="col-sm-6"><div class="small text-muted">Requester</div><div class="fw-medium">{{ $reservation->requester?->name ?? 'Unknown' }}</div></div>
                                        <div class="col-sm-6"><div class="small text-muted">Created by</div><div class="fw-medium">{{ $reservation->createdBy?->name ?? 'Unknown' }}</div></div>
                                        <div class="col-sm-6"><div class="small text-muted">Location</div><div class="fw-medium">{{ $reservation->location?->name ?? '—' }}</div></div>
                                        <div class="col-sm-6"><div class="small text-muted">Date</div><div class="fw-medium">{{ $reservation->reservation_date?->format('d M Y') ?? '—' }}</div></div>
                                        <div class="col-sm-6"><div class="small text-muted">Time</div><div class="fw-medium">{{ substr((string) $reservation->start_time, 0, 5) }}–{{ substr((string) $reservation->end_time, 0, 5) }} (Asia/Colombo)</div></div>
                                        <div class="col-sm-6"><div class="small text-muted">Attendees</div><div class="fw-medium">{{ $reservation->attendee_count ?? '—' }}</div></div>
                                        @if($reservation->cancellation_reason)
                                            <div class="col-12"><div class="small text-muted">Cancellation reason</div><div class="fw-medium">{{ $reservation->cancellation_reason }}</div></div>
                                        @endif
                                    </div>
                                </section>

                                <section class="mt-4" aria-labelledby="bookedResources-{{ $reservation->id }}">
                                    <h3 class="fs-6 mb-3" id="bookedResources-{{ $reservation->id }}">Booked resources</h3>
                                    <div class="table-responsive border rounded">
                                        <table class="table table-sm align-middle mb-0">
                                            <thead class="table-light">
                                                <tr><th scope="col">Resource</th><th scope="col">Category</th><th scope="col">Item status</th></tr>
                                            </thead>
                                            <tbody>
                                                @forelse($reservation->items as $item)
                                                    <tr>
                                                        <td>{{ $item->resource_name_snapshot }}</td>
                                                        <td>{{ $item->resource?->type?->category?->name ?? '—' }}</td>
                                                        <td>{{ ucfirst($item->status) }}</td>
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="3" class="text-muted">No resources recorded.</td></tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </section>
                            </div>

                            <aside class="col-lg-5">
                                <section aria-labelledby="reservationHistory-{{ $reservation->id }}">
                                    <h3 class="fs-6 mb-3" id="reservationHistory-{{ $reservation->id }}">Status history</h3>
                                    @forelse($reservation->statusHistory as $history)
                                        <div class="border-start border-2 ps-3 pb-3 mb-3">
                                            <div class="fw-medium">{{ $history->from_status ? $history->from_status.' → ' : '' }}{{ $history->to_status }}</div>
                                            <div class="small text-muted">{{ $history->actor?->name ?? 'System' }} · {{ $history->created_at?->timezone(config('reservations.display_timezone'))->format('d M Y H:i') }}</div>
                                            @if($history->reason)<div class="small mt-1">{{ $history->reason }}</div>@endif
                                        </div>
                                    @empty
                                        <p class="small text-muted mb-0">No history recorded.</p>
                                    @endforelse
                                </section>

                                @can('cancel', $reservation)
                                    <section class="border-top mt-4 pt-4" aria-labelledby="cancelReservation-{{ $reservation->id }}">
                                        <h3 class="fs-6 mb-3" id="cancelReservation-{{ $reservation->id }}">Cancel reservation</h3>
                                        <form method="POST" action="{{ route('reservations.cancel', $reservation) }}">
                                            @csrf
                                            <label for="cancellationReason-{{ $reservation->id }}" class="visually-hidden">Cancellation reason</label>
                                            <textarea id="cancellationReason-{{ $reservation->id }}" name="cancellation_reason" class="form-control mb-2" rows="3" required placeholder="Reason is required"></textarea>
                                            <button type="submit" class="btn btn-danger w-100">Cancel reservation</button>
                                        </form>
                                    </section>
                                @endcan
                            </aside>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endcan
    @can('cancel', $reservation)
        <div class="modal fade" id="cancelModal-{{ $reservation->id }}" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('reservations.cancel', $reservation) }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Cancel {{ $reservation->reference }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label">Cancellation reason</label>
                        <textarea name="cancellation_reason" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button class="btn btn-danger">Cancel reservation</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endforeach
@endsection
