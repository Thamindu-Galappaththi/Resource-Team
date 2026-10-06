@extends('layouts.app')

@section('title', 'Existing Canteen Reservations')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
        <div>
            <h1 class="h3 text-white mb-2">Existing Canteen Reservations</h1>
            <p class="text-white-50 mb-0">Review service requests and track their status.</p>
        </div>
        @can('create', \App\Models\CanteenReservation::class)
            <a href="{{ route('canteen.create') }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i>New reservation</a>
        @endcan
    </div>

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

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-4">
            <div class="card border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Reservations</div>
                    <div class="fs-3 fw-semibold mt-1">{{ number_format($summary['total'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Awaiting review</div>
                    <div class="fs-3 fw-semibold mt-1">{{ number_format($summary['pending'] ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Rejected or cancelled</div>
                    <div class="fs-3 fw-semibold mt-1">{{ number_format($summary['rejected_cancelled'] ?? 0) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('canteen.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4 col-xl-3">
                    <label class="form-label" for="search">Search</label>
                    <input id="search" type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Reference, reservation, requester">
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
                        @foreach(\App\Enums\MealType::cases() as $type)
                            <option value="{{ $type->value }}" @selected(request('meal_type') === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All</option>
                        @foreach(\App\Enums\CanteenReservationStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-auto d-flex gap-2">
                    <button class="btn btn-primary"><i class="ti ti-filter me-1"></i>Apply</button>
                    @if(request()->query())
                        <a href="{{ route('canteen.index') }}" class="btn btn-outline-secondary">Clear</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">Reservation register</h2>
                <span class="small text-muted">{{ $reservations->total() }} records</span>
            </div>
            @if($reservations->isEmpty())
                <div class="text-center px-3 py-5">
                    <i class="ti ti-calendar-off fs-1 text-muted" aria-hidden="true"></i>
                    <h5 class="mt-3 mb-1">No reservations found</h5>
                    <p class="text-muted mb-0">Try adjusting your filters or create a new reservation.</p>
                </div>
            @else
                <div class="table-responsive">
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
                                                @if($reservation->canBeEdited() || auth()->user()?->hasRole('developer', 'super_admin', 'admin', 'coordinator'))
                                                    <a href="{{ route('canteen.edit', $reservation) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                                @endif
                                            @endcan
                                            @can('manageStatus', $reservation)
                                                @if($reservation->status === \App\Enums\CanteenReservationStatus::PENDING->value)
                                                    <form method="POST" action="{{ route('canteen.status', $reservation) }}" class="d-inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="status" value="confirmed">
                                                        <button class="btn btn-sm btn-success" type="submit">Approve</button>
                                                    </form>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectCanteen-{{ $reservation->id }}">Reject</button>
                                                @endif
                                            @endcan
                                            @can('cancel', $reservation)
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
            <div class="mt-3">{{ $reservations->links() }}</div>
        </div>
    </div>
</div>

@foreach($reservations as $reservation)
    @can('manageStatus', $reservation)
        @if($reservation->status === \App\Enums\CanteenReservationStatus::PENDING->value)
            <div class="modal fade" id="rejectCanteen-{{ $reservation->id }}" tabindex="-1" aria-labelledby="rejectCanteenTitle-{{ $reservation->id }}" aria-hidden="true">
                <div class="modal-dialog">
                    <form method="POST" action="{{ route('canteen.status', $reservation) }}" class="modal-content">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="rejected">
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="rejectCanteenTitle-{{ $reservation->id }}">Reject {{ $reservation->reservation_ref }}?</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <label class="form-label" for="rejectReason-{{ $reservation->id }}">Reason for rejection</label>
                            <textarea id="rejectReason-{{ $reservation->id }}" name="approval_comments" class="form-control" rows="3" maxlength="1000" required></textarea>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Keep pending</button>
                            <button type="submit" class="btn btn-danger">Confirm rejection</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endcan
@endforeach
@endsection
