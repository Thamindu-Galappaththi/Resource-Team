@extends('layouts.app')

@section('title', 'Hostel Reservations')

@section('content')
<style>
.hostel-reservations {
    max-width: 1200px;
    margin: 0 auto;
}

.hostel-reservations .summary-card {
    border: 1px solid #e8ebef;
    border-radius: 10px;
    padding: 20px;
    height: 100%;
}

.hostel-reservations .summary-label {
    font-size: 12px;
    text-transform: uppercase;
    color: #343a40;
}

.hostel-reservations .summary-value {
    font-size: 28px;
    font-weight: 600;
    color: #172431;
}

.hostel-reservations .check-ins {
    border-top: 3px solid #13aacb;
}

.hostel-reservations .filter-panel {
    background: #f7f8fa;
    border: 1px solid #edf0f3;
    border-radius: 8px;
}

.hostel-reservations .filter-panel .form-label {
    font-size: 11px;
    text-transform: uppercase;
    font-weight: 600;
}

.hostel-reservations .table thead th {
    background: #f7f8fa;
    font-size: 12px;
    text-transform: uppercase;
    white-space: nowrap;
}

.hostel-reservations .table td,
.hostel-reservations .table th {
    padding: 16px;
}
</style>
<div class="hostel-reservations py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 text-white mb-2">Hostel Reservations</h1>
            <p class="text-white mb-0">Manage student and guest accommodation logistics across the Nebula campus.</p>
        </div>
        @can('createHostel', \App\Models\Reservation::class)
        <a href="{{ route('hostel.create') }}" class="btn btn-primary">
            <i class="ti ti-circle-plus me-2" aria-hidden="true"></i>New Reservation
        </a>
        @endcan
    </div>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="summary-card">
                        <div class="summary-label mb-1">Total Bookings</div>
                        <div class="summary-value">{{ $summary['total'] }}</div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="summary-card check-ins">
                        <div class="summary-label mb-1">Check-ins Today</div>
                        <div class="summary-value">{{ $summary['check_ins_today'] }}</div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="summary-card">
                        <div class="summary-label mb-1">Available Rooms</div>
                        <div class="summary-value">{{ $summary['available_rooms'] }}</div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="summary-card">
                        <div class="summary-label mb-1">Pending Requests</div>
                        <div class="summary-value">{{ $summary['pending'] }}</div>
                    </div>
                </div>
            </div>

            <form method="GET" action="{{ route('hostel.index') }}" class="filter-panel p-3 mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" id="search" name="search" class="form-control"
                            value="{{ request('search') }}" placeholder="ID or guest">
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="check_in_from" class="form-label">Check-in From</label>
                        <input type="date" id="check_in_from" name="check_in_from" class="form-control"
                            value="{{ request('check_in_from') }}">
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="check_in_to" class="form-label">Check-in To</label>
                        <input type="date" id="check_in_to" name="check_in_to" class="form-control"
                            value="{{ request('check_in_to') }}">
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="room_type_id" class="form-label">Room Category</label>
                        <select id="room_type_id" name="room_type_id" class="form-select">
                            <option value="">All Categories</option>
                            @foreach($roomTypes as $type)
                            <option value="{{ $type->id }}" @selected(request('room_type_id')==$type->
                                id)>{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="status" class="form-label">Reservation Status</label>
                        <select id="status" name="status" class="form-select">
                            <option value="">All Statuses</option>
                            @foreach($statuses as $status)
                            <option value="{{ $status->value }}" @selected(request('status')===$status->
                                value)>{{ $status->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <a href="{{ route('hostel.index') }}" class="btn btn-light">Reset</a>
                        <button class="btn btn-primary">Apply Filters</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive border rounded">
                <table class="table align-middle mb-0">
                    <caption class="visually-hidden">Hostel reservations</caption>
                    <thead>
                        <tr>
                            <th scope="col">Reservation ID</th>
                            <th scope="col">Guest Name</th>
                            <th scope="col">Room Category</th>
                            <th scope="col">Check-in</th>
                            <th scope="col">Check-out</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reservations as $reservation)
                        <tr>
                            <td>{{ $reservation->reference }}</td>
                            <td>{{ $reservation->hostelStay?->guest_name ?? '—' }}</td>
                            <td>{{ $reservation->hostelStay?->roomType?->name ?? '—' }}</td>
                            <td>{{ $reservation->hostelStay?->check_in_at?->timezone(config('reservations.display_timezone'))->format('d M Y') }}
                            </td>
                            <td>{{ $reservation->hostelStay?->check_out_at?->timezone(config('reservations.display_timezone'))->format('d M Y') }}
                            </td>
                            <td><span class="badge bg-light text-dark">{{ $reservation->statusEnum()->label() }}</span>
                            </td>
                            <td class="text-end text-nowrap">
                                @can('viewHostel', $reservation)
                                <a href="{{ route('hostel.show', $reservation) }}"
                                    class="btn btn-sm btn-outline-secondary">View</a>
                                @endcan
                                @can('cancelHostel', $reservation)
                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                                    data-bs-target="#cancelModal-{{ $reservation->id }}">Cancel</button>
                                @endcan
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="ti ti-bed d-block fs-7 mb-2" aria-hidden="true"></i>
                                No reservations found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $reservations->links() }}</div>
        </div>
    </div>
</div>

@foreach($reservations as $reservation)
@can('cancelHostel', $reservation)
<div class="modal fade" id="cancelModal-{{ $reservation->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('hostel.cancel', $reservation) }}" class="modal-content">
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