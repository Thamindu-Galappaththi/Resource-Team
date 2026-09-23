@extends('layouts.app')

@section('title', 'Canteen Reservations')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Canteen Reservations</h2>
            <p class="text-muted mb-0">Review and manage bookings.</p>
        </div>
        @can('create', \App\Models\CanteenReservation::class)
            <a href="{{ route('canteen.reservations.create') }}" class="btn btn-primary">New Reservation</a>
        @endcan
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Total</div>
                    <h3>{{ $summary['total'] ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Pending</div>
                    <h3>{{ $summary['pending'] ?? 0 }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Rejected / Cancelled</div>
                    <h3>{{ $summary['rejected_cancelled'] ?? 0 }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('canteen.reservations.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Reservation or person">
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
                    <label class="form-label">Meal Type</label>
                    <select name="meal_type" class="form-select">
                        <option value="">All</option>
                        @foreach(\App\Enums\MealType::values() as $type)
                            <option value="{{ $type }}" {{ request('meal_type') === $type ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        @foreach(\App\Enums\CanteenReservationStatus::values() as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 d-grid">
                    <button class="btn btn-primary">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            @if($reservations->isEmpty())
                <div class="p-4">
                    <div class="alert alert-info mb-0">No reservations found for the current filters.</div>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ref</th>
                                <th>Reservation</th>
                                <th>Requester</th>
                                <th>Meal</th>
                                <th>Date</th>
                                <th>Orders</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reservations as $reservation)
                                <tr>
                                    <td>{{ $reservation->reservation_ref }}</td>
                                    <td>{{ $reservation->reservation_name }}</td>
                                    <td>{{ $reservation->requestedBy?->name ?? 'Unknown' }}</td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $reservation->meal_type)) }}</td>
                                    <td>{{ $reservation->reservation_date->format('d M Y') }}</td>
                                    <td>{{ $reservation->number_of_orders }}</td>
                                    <td><span class="badge bg-light text-dark">{{ $reservation->status }}</span></td>
                                    <td>
                                        @can('view', $reservation)
                                            <a href="{{ route('canteen.reservations.show', $reservation) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                        @endcan
                                        @can('update', $reservation)
                                            <a href="{{ route('canteen.reservations.edit', $reservation) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                        @endcan
                                        @can('cancel', $reservation)
                                            <form action="{{ route('canteen.reservations.destroy', $reservation) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger">Cancel</button>
                                            </form>
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

    <div class="mt-3">
        {{ $reservations->links() }}
    </div>
</div>
@endsection
