@extends('layouts.app')

@section('title', 'Reservation '.$reservation->reference)

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">{{ $reservation->reference }}</h2>
            <p class="text-muted mb-0">{{ $reservation->title }}</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-light text-dark fs-6">{{ $reservation->statusEnum()->label() }}</span>
            <a href="{{ route('reservations.index') }}" class="btn btn-outline-secondary">Back to list</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h5 class="mb-3">Reservation details</h5>
                    <div class="row g-3">
                        <div class="col-md-6"><strong>Purpose</strong><div>{{ $reservation->purpose }}</div></div>
                        <div class="col-md-6"><strong>Requester</strong><div>{{ $reservation->requester?->name ?? 'Unknown' }}</div></div>
                        <div class="col-md-6"><strong>Created by</strong><div>{{ $reservation->createdBy?->name ?? 'Unknown' }}</div></div>
                        <div class="col-md-6"><strong>Location</strong><div>{{ $reservation->location?->name ?? '—' }}</div></div>
                        <div class="col-md-6"><strong>Date</strong><div>{{ $reservation->reservation_date?->format('d M Y') }}</div></div>
                        <div class="col-md-6"><strong>Time</strong><div>{{ substr((string) $reservation->start_time, 0, 5) }}–{{ substr((string) $reservation->end_time, 0, 5) }} (Asia/Colombo)</div></div>
                        <div class="col-md-6"><strong>Attendees</strong><div>{{ $reservation->attendee_count ?? '—' }}</div></div>
                        @if($reservation->cancellation_reason)
                            <div class="col-12"><strong>Cancellation reason</strong><div>{{ $reservation->cancellation_reason }}</div></div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h5 class="mb-3">Booked resources</h5>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Resource</th>
                                    <th>Category</th>
                                    <th>Item status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reservation->items as $item)
                                    <tr>
                                        <td>{{ $item->resource_name_snapshot }}</td>
                                        <td>{{ $item->resource?->type?->category?->name ?? '—' }}</td>
                                        <td>{{ ucfirst($item->status) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h5 class="mb-3">Status history</h5>
                    @forelse($reservation->statusHistory as $history)
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="fw-semibold">{{ $history->from_status ? $history->from_status.' → ' : '' }}{{ $history->to_status }}</div>
                            <div class="small text-muted">{{ $history->actor?->name ?? 'System' }} · {{ $history->created_at?->timezone(config('reservations.display_timezone'))->format('d M Y H:i') }}</div>
                            @if($history->reason)
                                <div class="small">{{ $history->reason }}</div>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted mb-0">No history recorded.</p>
                    @endforelse
                </div>
            </div>

            @can('cancel', $reservation)
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="mb-3">Cancel reservation</h5>
                        <form method="POST" action="{{ route('reservations.cancel', $reservation) }}">
                            @csrf
                            <textarea name="cancellation_reason" class="form-control mb-3" rows="3" required placeholder="Reason is required"></textarea>
                            <button class="btn btn-danger w-100">Cancel reservation</button>
                        </form>
                    </div>
                </div>
            @endcan
        </div>
    </div>
</div>
@endsection
