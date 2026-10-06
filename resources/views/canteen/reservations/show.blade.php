@extends('layouts.app')

@section('title', 'Canteen Reservation '.$reservation->reservation_ref)

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 text-white mb-2">{{ $reservation->reservation_ref }}</h1>
            <p class="text-white-50 mb-0">{{ $reservation->reservation_name }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="badge {{ $reservation->statusEnum()->badgeClass() }}">{{ $reservation->statusEnum()->label() }}</span>
            <a href="{{ route('canteen.index') }}" class="btn btn-outline-light">Back to list</a>
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
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h5 mb-3">Reservation details</h2>
                    <div class="row g-3">
                        <div class="col-md-6"><div class="small text-muted">Reservation name</div><div class="fw-medium">{{ $reservation->reservation_name }}</div></div>
                        <div class="col-md-6"><div class="small text-muted">Requester</div><div class="fw-medium">{{ $reservation->requestedBy?->name ?? 'Unknown' }}</div></div>
                        <div class="col-md-6"><div class="small text-muted">Meal type</div><div class="fw-medium">{{ $reservation->mealTypeLabel() }}</div></div>
                        <div class="col-md-6"><div class="small text-muted">Location</div><div class="fw-medium">{{ $reservation->location?->name ?? '—' }}</div></div>
                        <div class="col-md-6"><div class="small text-muted">Date & time</div><div class="fw-medium">{{ $reservation->reservation_date->format('d M Y') }} at {{ $reservation->serviceTimeLabel() }}</div></div>
                        <div class="col-md-6"><div class="small text-muted">Orders</div><div class="fw-medium">{{ number_format($reservation->number_of_orders) }}</div></div>
                        <div class="col-12"><div class="small text-muted">Order details</div><div class="fw-medium">{{ $reservation->order_details ?: '—' }}</div></div>
                        <div class="col-12"><div class="small text-muted">Special remarks</div><div class="fw-medium">{{ $reservation->special_remarks ?: '—' }}</div></div>
                        @if($reservation->approvedBy)
                            <div class="col-md-6"><div class="small text-muted">Reviewed by</div><div class="fw-medium">{{ $reservation->approvedBy->name }}</div></div>
                        @endif
                        @if($reservation->approval_comments)
                            <div class="col-12"><div class="small text-muted">Approval comment</div><div class="fw-medium">{{ $reservation->approval_comments }}</div></div>
                        @endif
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-4">
                        @can('update', $reservation)
                            @if($reservation->canBeEdited() || auth()->user()?->hasRole('developer', 'super_admin', 'admin', 'coordinator'))
                                <a href="{{ route('canteen.edit', $reservation) }}" class="btn btn-outline-primary">Edit</a>
                            @endif
                        @endcan
                        @can('cancel', $reservation)
                            <form action="{{ route('canteen.destroy', $reservation) }}" method="POST" onsubmit="return confirm('Cancel this reservation?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline-danger">Cancel reservation</button>
                            </form>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            @can('manageStatus', $reservation)
                @if($reservation->status === \App\Enums\CanteenReservationStatus::PENDING->value)
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h2 class="h5 mb-3">Review request</h2>
                            <p class="text-muted small">Large-group orders stay pending until the canteen team confirms them.</p>
                            <form method="POST" action="{{ route('canteen.status', $reservation) }}" class="mb-3">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="confirmed">
                                <label class="form-label" for="approve_comments">Comment (optional)</label>
                                <textarea id="approve_comments" name="approval_comments" class="form-control mb-3" rows="2" maxlength="1000">{{ old('approval_comments') }}</textarea>
                                <button type="submit" class="btn btn-success w-100">Approve reservation</button>
                            </form>
                            <form method="POST" action="{{ route('canteen.status', $reservation) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="rejected">
                                <label class="form-label" for="reject_comments">Reason for rejection</label>
                                <textarea id="reject_comments" name="approval_comments" class="form-control mb-3 @error('approval_comments') is-invalid @enderror" rows="2" maxlength="1000" required>{{ old('approval_comments') }}</textarea>
                                @error('approval_comments')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                <button type="submit" class="btn btn-outline-danger w-100">Reject reservation</button>
                            </form>
                        </div>
                    </div>
                @endif
            @endcan
        </div>
    </div>
</div>
@endsection
