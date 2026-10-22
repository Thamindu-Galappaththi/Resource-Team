@extends('layouts.app')

@section('title', 'Canteen Forecast')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/canteen-dashboard.css') }}?v={{ @filemtime(public_path('css/canteen-dashboard.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('css/canteen-pages.css') }}?v={{ @filemtime(public_path('css/canteen-pages.css')) ?: time() }}">
@endpush

@section('content')
<div class="container-fluid py-4 cp-page">
    <header class="cd-header">
        <div>
            <div class="cd-eyebrow"><i class="ti ti-calendar-time" aria-hidden="true"></i> Canteen operations</div>
            <h1 class="cd-title">Forecast for {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</h1>
            <p class="cd-subtitle">Pending and approved demand for the selected date.</p>
        </div>
        <div class="cd-actions">
            <a href="{{ route('canteen.dashboard') }}" class="cd-btn cd-btn--outline"><i class="ti ti-arrow-left" aria-hidden="true"></i>Back to dashboard</a>
        </div>
    </header>

    <section class="cp-surface">
        <div class="cp-panel-heading">
            <h2 class="h5 mb-1">Meal demand</h2>
            <p class="text-muted small mb-0">{{ $records->total() }} {{ \Illuminate\Support\Str::plural('reservation', $records->total()) }} scheduled for kitchen planning.</p>
        </div>
        <div class="cp-register">
            @if($records->isEmpty())
                <div class="cp-empty-state">
                    <i class="ti ti-calendar-off fs-1 text-muted" aria-hidden="true"></i>
                    <h3>No reservations for this day</h3>
                    <p class="text-muted mb-0">No pending or approved canteen orders are scheduled for this date.</p>
                </div>
            @else
                <div class="cp-table-scroll">
                    <table class="table cp-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Reservation</th>
                                <th>Requester</th>
                                <th>Meal</th>
                                <th>Time</th>
                                <th>Location</th>
                                <th>Orders</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($records as $reservation)
                                <tr>
                                    <td data-label="Reservation">
                                        <div class="fw-semibold">{{ $reservation->reservation_name }}</div>
                                        <span class="small text-muted font-monospace">{{ $reservation->reservation_ref }}</span>
                                    </td>
                                    <td data-label="Requester">{{ $reservation->requestedBy?->name ?? 'Unknown' }}</td>
                                    <td data-label="Meal">{{ $reservation->mealTypeLabel() }}</td>
                                    <td data-label="Time">{{ $reservation->serviceTimeLabel() }}</td>
                                    <td data-label="Location">{{ $reservation->location?->name ?? '—' }}</td>
                                    <td data-label="Orders" class="fw-semibold">{{ number_format($reservation->number_of_orders) }}</td>
                                    <td data-label="Status"><span class="badge {{ $reservation->statusEnum()->badgeClass() }}">{{ $reservation->statusEnum()->label() }}</span></td>
                                    <td class="text-end cp-actions" data-label="Actions">
                                        @can('view', $reservation)
                                            <a href="{{ route('canteen.show', $reservation) }}" class="btn btn-sm btn-outline-secondary cp-icon-btn" title="View" aria-label="View {{ $reservation->reservation_ref }}"><i class="ti ti-eye"></i></a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @include('canteen.partials.pager', ['paginator' => $records])
            @endif
        </div>
    </section>
</div>
@endsection
