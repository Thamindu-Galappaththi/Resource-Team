@extends('layouts.app')

@section('title', 'Canteen Forecast')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 text-white mb-2">Forecast for {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</h1>
            <p class="text-white-50 mb-0">Pending and confirmed demand for the selected date.</p>
        </div>
        <a href="{{ route('canteen.dashboard') }}" class="btn btn-outline-light">Back to dashboard</a>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            @if($records->isEmpty())
                <div class="text-center py-5">
                    <i class="ti ti-calendar-off fs-1 text-muted" aria-hidden="true"></i>
                    <p class="mb-0 mt-2 text-muted">No canteen reservations are scheduled for this day.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Reservation</th>
                                <th>Requester</th>
                                <th>Meal</th>
                                <th>Time</th>
                                <th>Location</th>
                                <th>Orders</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($records as $reservation)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $reservation->reservation_name }}</div>
                                        <span class="small text-muted font-monospace">{{ $reservation->reservation_ref }}</span>
                                    </td>
                                    <td>{{ $reservation->requestedBy?->name ?? 'Unknown' }}</td>
                                    <td>{{ $reservation->mealTypeLabel() }}</td>
                                    <td>{{ $reservation->serviceTimeLabel() }}</td>
                                    <td>{{ $reservation->location?->name ?? '—' }}</td>
                                    <td class="fw-semibold">{{ number_format($reservation->number_of_orders) }}</td>
                                    <td><span class="badge {{ $reservation->statusEnum()->badgeClass() }}">{{ $reservation->statusEnum()->label() }}</span></td>
                                    <td class="text-end">
                                        @can('view', $reservation)
                                            <a href="{{ route('canteen.show', $reservation) }}" class="btn btn-sm btn-outline-secondary">View</a>
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
</div>
@endsection
