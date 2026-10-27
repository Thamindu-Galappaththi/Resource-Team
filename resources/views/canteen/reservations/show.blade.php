@extends('layouts.app')

@section('title', 'Canteen Reservation '.$reservation->reservation_ref)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/canteen-dashboard.css') }}?v={{ @filemtime(public_path('css/canteen-dashboard.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('css/canteen-pages.css') }}?v={{ @filemtime(public_path('css/canteen-pages.css')) ?: time() }}">
@endpush

@section('content')
<div class="container-fluid py-4 cp-page">
    <header class="cd-header">
        <div>
            <div class="cd-eyebrow"><i class="ti ti-tools-kitchen-2" aria-hidden="true"></i> Canteen operations</div>
            <h1 class="cd-title">{{ $reservation->reservation_ref }}</h1>
            <p class="cd-subtitle">{{ $reservation->reservation_name }}</p>
        </div>
        <div class="cd-actions">
            <span class="cd-status cd-status--{{ $reservation->statusEnum()->tone() }}">{{ $reservation->statusEnum()->label() }}</span>
            @unless(auth()->user()?->hasRole('slt_employee'))
                <a href="{{ route('canteen.index') }}" class="cd-btn cd-btn--outline"><i class="ti ti-arrow-left" aria-hidden="true"></i>Back to list</a>
            @endunless
        </div>
    </header>

    @if(session('success'))
        <div class="alert alert-success cp-auto-alert" role="status">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger cp-auto-alert" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="cp-surface">
        <div class="cp-panel-heading d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h2 class="h5 mb-1">Reservation details</h2>
                <p class="text-muted small mb-0">Meal request, service slot, and review notes.</p>
            </div>
            <div class="cp-action-group">
                @can('update', $reservation)
                    <a href="{{ route('canteen.edit', $reservation) }}" class="btn btn-sm btn-outline-secondary cp-icon-btn" title="Edit" aria-label="Edit reservation"><i class="ti ti-pencil"></i></a>
                @endcan
                @can('delete', $reservation)
                    <button type="button" class="btn btn-sm btn-outline-danger cp-icon-btn" title="Delete" aria-label="Delete reservation"
                        data-cp-confirm
                        data-cp-action="{{ route('canteen.destroy', $reservation) }}"
                        data-cp-title="Delete reservation?"
                        data-cp-sub="{{ $reservation->reservation_ref }} · {{ $reservation->reservation_name }}"
                        data-cp-copy="This will permanently remove the reservation."
                        data-cp-submit="Delete reservation"
                        data-cp-icon="ti ti-trash"><i class="ti ti-trash"></i></button>
                @elsecan('cancel', $reservation)
                    <button type="button" class="btn btn-sm btn-outline-danger cp-icon-btn" title="Cancel" aria-label="Cancel reservation"
                        data-cp-confirm
                        data-cp-action="{{ route('canteen.destroy', $reservation) }}"
                        data-cp-title="Cancel reservation?"
                        data-cp-sub="{{ $reservation->reservation_ref }} · {{ $reservation->reservation_name }}"
                        data-cp-copy="This will cancel the meal request and notify the canteen team."
                        data-cp-submit="Cancel reservation"
                        data-cp-icon="ti ti-ban"><i class="ti ti-ban"></i></button>
                @endcan
            </div>
        </div>

        <div class="cp-form-body">
            <div class="cp-details">
                <div class="cp-detail-row"><span class="cp-detail-label">Reservation name</span><span class="cp-detail-value">{{ $reservation->reservation_name }}</span></div>
                <div class="cp-detail-row"><span class="cp-detail-label">Requester</span><span class="cp-detail-value">{{ $reservation->requestedBy?->name ?? 'Unknown' }}</span></div>
                <div class="cp-detail-row"><span class="cp-detail-label">Meal type</span><span class="cp-detail-value">{{ $reservation->mealTypeLabel() }}</span></div>
                <div class="cp-detail-row"><span class="cp-detail-label">Location</span><span class="cp-detail-value">{{ $reservation->location?->name ?? '—' }}</span></div>
                <div class="cp-detail-row"><span class="cp-detail-label">Date & time</span><span class="cp-detail-value">{{ $reservation->reservation_date->format('d M Y') }} at {{ $reservation->serviceTimeLabel() }}</span></div>
                <div class="cp-detail-row"><span class="cp-detail-label">Orders</span><span class="cp-detail-value">{{ number_format($reservation->number_of_orders) }}</span></div>
                <div class="cp-detail-row"><span class="cp-detail-label">Order details</span><span class="cp-detail-value">{{ $reservation->order_details ?: '—' }}</span></div>
                <div class="cp-detail-row"><span class="cp-detail-label">Special remarks</span><span class="cp-detail-value">{{ $reservation->special_remarks ?: '—' }}</span></div>
                @if($reservation->approvedBy)
                    <div class="cp-detail-row"><span class="cp-detail-label">Reviewed by</span><span class="cp-detail-value">{{ $reservation->approvedBy->name }}</span></div>
                @endif
                @if($reservation->approval_comments)
                    <div class="cp-detail-row"><span class="cp-detail-label">Approval comment</span><span class="cp-detail-value">{{ $reservation->approval_comments }}</span></div>
                @endif
            </div>
        </div>
    </section>
</div>

@include('canteen.partials.confirm-modal')
@endsection

@push('scripts')
<script src="{{ asset('js/canteen-ui.js') }}?v=5"></script>
@endpush
