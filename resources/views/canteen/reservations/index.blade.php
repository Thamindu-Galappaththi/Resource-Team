@extends('layouts.app')

@section('title', 'Canteen Reservations')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
        <div>
            <div class="small text-uppercase fw-semibold text-primary mb-1">Canteen operations</div>
            <h2 class="mb-1">Reservations</h2>
            <p class="text-muted mb-0">Review service requests and track their status.</p>
        </div>
        @can('create', \App\Models\CanteenReservation::class)
            <a href="{{ route('canteen.reservations.create') }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i>New reservation</a>
            <a href="{{ route('canteen.create') }}" class="btn btn-primary">New Reservation</a>
        @endcan
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-4">
            <div class="border-start border-4 border-primary bg-white p-3 h-100">
                <div class="text-muted small text-uppercase fw-semibold">Reservations</div>
                <div class="fs-3 fw-semibold mt-1">{{ number_format($summary['total'] ?? 0) }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="border-start border-4 border-warning bg-white p-3 h-100">
                <div class="text-muted small text-uppercase fw-semibold">Awaiting review</div>
                <div class="fs-3 fw-semibold mt-1">{{ number_format($summary['pending'] ?? 0) }}</div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="border-start border-4 border-secondary bg-white p-3 h-100">
                <div class="text-muted small text-uppercase fw-semibold">Rejected or cancelled</div>
                <div class="fs-3 fw-semibold mt-1">{{ number_format($summary['rejected_cancelled'] ?? 0) }}</div>
            </div>
        </div>
    </div>

    <section class="border-top border-bottom py-3 mb-4">
        <form method="GET" action="{{ route('canteen.reservations.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4 col-xl-3">
                    <label class="form-label" for="search">Search</label>
                    <input id="search" type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Reference, reservation, requester">
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('canteen.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Reservation or person">
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label" for="from_date">From</label>
                    <input id="from_date" type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label" for="to_date">To</label>
                    <input id="to_date" type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label" for="meal_type">Meal type</label>
                    <select id="meal_type" name="meal_type" class="form-select">
                        <option value="">All</option>
                        @foreach(\App\Enums\MealType::values() as $type)
                            <option value="{{ $type }}" {{ request('meal_type') === $type ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All</option>
                        @foreach(\App\Enums\CanteenReservationStatus::values() as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-auto d-flex gap-2">
                    <button class="btn btn-primary"><i class="ti ti-filter me-1"></i>Apply</button>
                    @if(request()->query())
                        <a href="{{ route('canteen.reservations.index') }}" class="btn btn-outline-secondary">Clear</a>
                    @endif
                </div>
        </form>
    </section>

    <section>
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h4 class="mb-0">Reservation register</h4>
            <span class="small text-muted">{{ $reservations->total() }} records</span>
        </div>
            @if($reservations->isEmpty())
                <div class="border-top border-bottom text-center px-3 py-5">
                    <i class="ti ti-calendar-off fs-1 text-muted" aria-hidden="true"></i>
                    <h5 class="mt-3 mb-1">No reservations found</h5>
                    <p class="text-muted mb-0">Try adjusting your filters or create a new reservation.</p>
                </div>
            @else
                <div class="table-responsive border-top border-bottom bg-white">
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
                                    <td><div class="fw-semibold">{{ $reservation->reservation_name }}</div><span class="small text-muted font-monospace">{{ $reservation->reservation_ref }}</span></td>
                                    <td>{{ $reservation->requestedBy?->name ?? 'Unknown' }}</td>
                                    <td><div>{{ $reservation->reservation_date->format('d M Y') }}</div><span class="small text-muted">{{ \Illuminate\Support\Facades\Date::parse($reservation->reservation_time)->format('h:i A') }}</span></td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $reservation->meal_type)) }}</td>
                                    <td>{{ $reservation->location?->name ?? '-' }}</td>
                                    <td class="fw-semibold">{{ number_format($reservation->number_of_orders) }}</td>
                                    <td>
                                        @php
                                            $statusClass = match ($reservation->status) {
                                                'confirmed' => 'bg-success-subtle text-success',
                                                'pending' => 'bg-warning-subtle text-dark',
                                                'rejected', 'cancelled' => 'bg-danger-subtle text-danger',
                                                'completed' => 'bg-secondary-subtle text-secondary',
                                                default => 'bg-light text-dark',
                                            };
                                        @endphp
                                        <span class="badge {{ $statusClass }}">{{ ucfirst($reservation->status) }}</span>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        @can('view', $reservation)
                                            <a href="{{ route('canteen.reservations.show', $reservation) }}" class="btn btn-sm btn-outline-secondary" title="View reservation" aria-label="View {{ $reservation->reservation_ref }}"><i class="ti ti-eye"></i></a>
                                        @endcan
                                        @can('update', $reservation)
                                            <a href="{{ route('canteen.reservations.edit', $reservation) }}" class="btn btn-sm btn-outline-primary" title="Edit reservation" aria-label="Edit {{ $reservation->reservation_ref }}"><i class="ti ti-pencil"></i></a>
                                        @endcan
                                        @can('cancel', $reservation)
                                            <form action="{{ route('canteen.reservations.destroy', $reservation) }}" method="POST" class="d-inline" onsubmit="return confirm('Cancel this reservation?')">
                                            <a href="{{ route('canteen.show', $reservation) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                        @endcan
                                        @can('update', $reservation)
                                            <a href="{{ route('canteen.edit', $reservation) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                        @endcan
                                        @can('cancel', $reservation)
                                            <form action="{{ route('canteen.destroy', $reservation) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-outline-danger" title="Cancel reservation" aria-label="Cancel {{ $reservation->reservation_ref }}"><i class="ti ti-x"></i></button>
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
    </section>

    <div class="mt-3">
        {{ $reservations->links() }}
    </div>
</div>
@endsection
