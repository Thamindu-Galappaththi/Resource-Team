@extends('layouts.app')

@section('title', 'Edit Canteen Reservation')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
        <div>
            <h1 class="h3 text-white mb-2">Edit Canteen Reservation</h1>
            <p class="text-white-50 mb-0">{{ $reservation->reservation_ref }} · {{ $reservation->reservation_name }}</p>
        </div>
        <a href="{{ route('canteen.show', $reservation) }}" class="btn btn-outline-light"><i class="ti ti-arrow-left me-1"></i>Back to details</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger" role="alert"><strong>Please check the form.</strong> Some details need your attention.</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('canteen.update', $reservation) }}">
                @include('canteen.reservations._form', ['reservation' => $reservation])
                <div class="d-flex flex-column-reverse flex-sm-row justify-content-end gap-2 pt-4 mt-2 border-top">
                    <a href="{{ route('canteen.show', $reservation) }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update reservation</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
