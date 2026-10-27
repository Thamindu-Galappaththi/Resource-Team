@extends('layouts.app')

@section('title', 'Hostel '.$reservation->reference)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/hostel-show.css') }}?v=1">
@endpush

@section('content')
@php
    $tz = config('reservations.display_timezone');
    $statusLabel = fn (?string $value) => \App\Enums\ReservationStatus::tryFrom((string) $value)?->label() ?? $value;
@endphp
<div class="container-fluid mt-4 hs-page">
    <div class="hs-header">
        <div>
            <h1 class="hs-title">{{ $reservation->reference }}</h1>
            <p class="hs-subtitle mb-0">{{ $reservation->title }}</p>
        </div>
        <div class="hs-header-actions">
            <span class="badge {{ $reservation->statusEnum()->badgeClass() }}">{{ $reservation->statusEnum()->label() }}</span>
            @unless(auth()->user()?->hasRole('slt_employee'))
                <a href="{{ route('hostel.index') }}" class="btn hs-back"><i class="ti ti-arrow-left me-1"></i>Back to list</a>
            @endunless
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success hs-auto-alert" role="status">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger hs-auto-alert" role="alert">{{ $errors->first() }}</div>
    @endif

    <div class="row g-4 align-items-start">
        <div class="col-12 col-lg-8">
            <section class="hs-card">
                <h2 class="hs-section-title"><i class="ti ti-bed"></i> Stay details</h2>
                <div class="hs-card-body">
                    <div class="hs-details">
                        <div class="hs-detail-row"><span class="hs-detail-label">Guest name</span><span class="hs-detail-value">{{ $reservation->hostelStay?->guest_name ?: '—' }}</span></div>
                        <div class="hs-detail-row"><span class="hs-detail-label">Room</span><span class="hs-detail-value">{{ $reservation->resourceNames() ?: '—' }}</span></div>
                        <div class="hs-detail-row"><span class="hs-detail-label">Room category</span><span class="hs-detail-value">{{ $reservation->hostelStay?->roomType?->name ?: '—' }}</span></div>
                        <div class="hs-detail-row"><span class="hs-detail-label">Location</span><span class="hs-detail-value">{{ $reservation->location?->name ?: '—' }}</span></div>
                        <div class="hs-detail-row"><span class="hs-detail-label">Check-in</span><span class="hs-detail-value">{{ $reservation->hostelStay?->check_in_at?->timezone($tz)->format('d M Y H:i') ?: '—' }}</span></div>
                        <div class="hs-detail-row"><span class="hs-detail-label">Check-out</span><span class="hs-detail-value">{{ $reservation->hostelStay?->check_out_at?->timezone($tz)->format('d M Y H:i') ?: '—' }}</span></div>
                        <div class="hs-detail-row"><span class="hs-detail-label">Guests</span><span class="hs-detail-value">{{ $reservation->hostelStay?->number_of_guests ?: '—' }}</span></div>
                        <div class="hs-detail-row"><span class="hs-detail-label">Requester</span><span class="hs-detail-value">{{ $reservation->requester?->name ?: '—' }}</span></div>
                        <div class="hs-detail-row"><span class="hs-detail-label">Created by</span><span class="hs-detail-value">{{ $reservation->createdBy?->name ?: '—' }}</span></div>
                        <div class="hs-detail-row"><span class="hs-detail-label">Requirements</span><span class="hs-detail-value">{{ $reservation->hostelStay?->special_requirements ?: '—' }}</span></div>
                        @if($reservation->cancellation_reason)
                            <div class="hs-detail-row"><span class="hs-detail-label">Cancel reason</span><span class="hs-detail-value">{{ $reservation->cancellation_reason }}</span></div>
                        @endif
                    </div>
                </div>
            </section>

            @if($reservation->items->isNotEmpty())
                <section class="hs-card">
                    <h2 class="hs-section-title"><i class="ti ti-door"></i> Booked room</h2>
                    <div class="hs-card-body">
                        <div class="table-responsive hs-table">
                            <table class="table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Resource</th>
                                        <th>Category</th>
                                        <th>Item status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reservation->items as $item)
                                        <tr>
                                            <td data-label="Resource">{{ $item->resource_name_snapshot ?: '—' }}</td>
                                            <td data-label="Category">{{ $item->resource?->type?->category?->name ?? $reservation->hostelStay?->roomType?->name ?? '—' }}</td>
                                            <td data-label="Item status"><span class="badge {{ $reservation->statusEnum()->badgeClass() }}">{{ $reservation->bookedItemStatusLabel($item) }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>
            @endif
        </div>
        <div class="col-12 col-lg-4">
            <aside class="hs-card">
                <h2 class="hs-section-title"><i class="ti ti-history"></i> Status history</h2>
                <div class="hs-card-body">
                    @forelse($reservation->statusHistory as $history)
                        <div class="hs-history">
                            <div class="hs-history-title">{{ $history->from_status ? $statusLabel($history->from_status).' → ' : '' }}{{ $statusLabel($history->to_status) }}</div>
                            <div class="hs-history-meta">{{ $history->actor?->name ?? 'System' }} · {{ $history->created_at?->timezone($tz)->format('d M Y H:i') }}</div>
                            @if($history->reason)
                                <div class="hs-history-reason">{{ $history->reason }}</div>
                            @endif
                        </div>
                    @empty
                        <p class="hs-empty mb-0">No history recorded.</p>
                    @endforelse
                </div>
            </aside>
            @can('cancelHostel', $reservation)
                <aside class="hs-card">
                    <h2 class="hs-section-title"><i class="ti ti-ban"></i> Cancel reservation</h2>
                    <div class="hs-card-body">
                        <form method="POST" action="{{ route('hostel.cancel', $reservation) }}">
                            @csrf
                            <label class="form-label" for="cancellation_reason">Cancellation reason</label>
                            <textarea id="cancellation_reason" name="cancellation_reason" class="form-control mb-3" rows="3" required placeholder="Reason is required"></textarea>
                            <button type="submit" class="hs-cancel">Cancel reservation</button>
                        </form>
                    </div>
                </aside>
            @endcan
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/hostel-show.js') }}?v=1"></script>
@endpush
