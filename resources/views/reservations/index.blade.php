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
                                    <td><span class="badge bg-light text-dark">{{ $reservation->statusEnum()->label() }}</span></td>
                                    <td class="text-nowrap">
                                        @can('view', $reservation)
                                            <a href="{{ route('reservations.show', $reservation) }}" class="btn btn-sm btn-outline-secondary">View</a>
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
