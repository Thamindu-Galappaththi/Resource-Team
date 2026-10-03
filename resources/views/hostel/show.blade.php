@extends('layouts.app')

@section('title', 'Hostel '.$reservation->reference)

@section('content')
<div class="container-fluid py-4" style="max-width: 1100px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1 text-white">{{ $reservation->reference }}</h2>
            <p class="text-white mb-0">{{ $reservation->title }}</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge rounded-pill px-3 {{ $reservation->statusEnum()->badgeClass() }}">{{ $reservation->statusEnum()->label() }}</span>
            <a href="{{ route('hostel.index') }}" class="btn btn-outline-light">Back to list</a>
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
                    <h5 class="mb-3">Stay details</h5>
                    <div class="row g-3">
                        <div class="col-md-6"><strong>Guest name</strong><div>{{ $reservation->hostelStay?->guest_name }}</div></div>
                        <div class="col-md-6"><strong>Room</strong><div>{{ $reservation->resourceNames() }}</div></div>
                        <div class="col-md-6"><strong>Room category</strong><div>{{ $reservation->hostelStay?->roomType?->name }}</div></div>
                        <div class="col-md-6"><strong>Location</strong><div>{{ $reservation->location?->name }}</div></div>
                        <div class="col-md-6"><strong>Check-in</strong><div>{{ $reservation->hostelStay?->check_in_at?->timezone(config('reservations.display_timezone'))->format('d M Y H:i') }}</div></div>
                        <div class="col-md-6"><strong>Check-out</strong><div>{{ $reservation->hostelStay?->check_out_at?->timezone(config('reservations.display_timezone'))->format('d M Y H:i') }}</div></div>
                        <div class="col-md-6"><strong>Guests</strong><div>{{ $reservation->hostelStay?->number_of_guests }}</div></div>
                        <div class="col-md-6"><strong>Requester</strong><div>{{ $reservation->requester?->name }}</div></div>
                        <div class="col-12"><strong>Special requirements</strong><div>{{ $reservation->hostelStay?->special_requirements ?: '—' }}</div></div>
                        @if($reservation->cancellation_reason)
                            <div class="col-12"><strong>Cancellation reason</strong><div>{{ $reservation->cancellation_reason }}</div></div>
                        @endif
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
                        </div>
                    @empty
                        <p class="text-muted mb-0">No history recorded.</p>
                    @endforelse
                </div>
            </div>
            @can('cancelHostel', $reservation)
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="mb-3">Cancel reservation</h5>
                        <form method="POST" action="{{ route('hostel.cancel', $reservation) }}">
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
