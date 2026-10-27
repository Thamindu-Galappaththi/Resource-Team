@extends('layouts.app')

@section('title', 'Edit Canteen Reservation')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/create-user.css') }}?v=15">
<link rel="stylesheet" href="{{ asset('css/canteen-dashboard.css') }}?v={{ @filemtime(public_path('css/canteen-dashboard.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('css/canteen-pages.css') }}?v={{ @filemtime(public_path('css/canteen-pages.css')) ?: time() }}">
@endpush

@section('content')
<div class="container-fluid py-4 cp-page">
    <header class="cd-header">
        <div>
            <div class="cd-eyebrow"><i class="ti ti-tools-kitchen-2" aria-hidden="true"></i> Canteen operations</div>
            <h1 class="cd-title">Edit reservation</h1>
            <p class="cd-subtitle">{{ $reservation->reservation_ref }} · {{ $reservation->reservation_name }}</p>
        </div>
        <div class="cd-actions">
            <a href="{{ route('canteen.show', $reservation) }}" class="cd-btn cd-btn--outline"><i class="ti ti-arrow-left" aria-hidden="true"></i>Back to details</a>
        </div>
    </header>

    @if($errors->any())
        <div class="alert alert-danger cp-auto-alert" role="alert"><strong>Please check the form.</strong> Some details need your attention.</div>
    @endif

    <section class="cp-surface">
        <div class="cp-panel-heading">
            <h2 class="h5 mb-1">Reservation request</h2>
            <p class="text-muted small mb-0">Update the details before the canteen team reviews this request.</p>
        </div>
        <div class="cp-form-body">
            <form method="POST" action="{{ route('canteen.update', $reservation) }}">
                @include('canteen.reservations._form', ['reservation' => $reservation])
                <div class="d-flex flex-column-reverse flex-sm-row justify-content-end gap-2 pt-4 mt-2 border-top cp-form-actions">
                    <a href="{{ route('canteen.show', $reservation) }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update reservation</button>
                </div>
            </form>
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/resource-select.js') }}?v=2"></script>
<script src="{{ asset('js/cu-dropdowns.js') }}?v=4"></script>
<script src="{{ asset('js/canteen-ui.js') }}?v=5"></script>
@endpush
