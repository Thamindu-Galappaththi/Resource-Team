@extends('layouts.app')

@section('title', 'Reservation '.$reservation->reference)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/reservation-show.css') }}?v=1">
@endpush

@section('content')
@php
    $tz = config('reservations.display_timezone');
    $statusLabel = fn (?string $value) => \App\Enums\ReservationStatus::tryFrom((string) $value)?->label() ?? $value;
    $start = substr((string) $reservation->start_time, 0, 5);
    $end = substr((string) $reservation->end_time, 0, 5);
@endphp
<div class="container-fluid mt-4 rsv-detail">
    <div class="rd-header">
        <div>
            <h1 class="rd-title">{{ $reservation->reference }}</h1>
            <p class="rd-subtitle mb-0">{{ $reservation->title ?: $reservation->purpose }}</p>
        </div>
        <div class="rd-header-actions">
            <span class="badge {{ $reservation->statusEnum()->badgeClass() }}">{{ $reservation->statusEnum()->label() }}</span>
            @unless(auth()->user()?->hasRole('slt_employee'))
                <a href="{{ route('reservations.index') }}" class="btn rd-back"><i class="ti ti-arrow-left me-1"></i>Back to list</a>
            @endunless
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success rd-auto-alert" role="status">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger rd-auto-alert" role="alert">{{ $errors->first() }}</div>
    @endif

    <div class="row g-4 align-items-start">
        <div class="col-12 col-lg-8">
            <section class="rd-card">
                <h2 class="rd-section-title"><i class="ti ti-calendar-event"></i> Reservation details</h2>
                <div class="rd-card-body">
                    <div class="rd-details">
                        <div class="rd-detail-row"><span class="rd-detail-label">Purpose</span><span class="rd-detail-value">{{ $reservation->purpose ?: '—' }}</span></div>
                        <div class="rd-detail-row"><span class="rd-detail-label">Requester</span><span class="rd-detail-value">{{ $reservation->requester?->name ?: '—' }}</span></div>
                        <div class="rd-detail-row"><span class="rd-detail-label">Created by</span><span class="rd-detail-value">{{ $reservation->createdBy?->name ?: '—' }}</span></div>
                        <div class="rd-detail-row"><span class="rd-detail-label">Location</span><span class="rd-detail-value">{{ $reservation->location?->name ?: '—' }}</span></div>
                        <div class="rd-detail-row"><span class="rd-detail-label">Date</span><span class="rd-detail-value">{{ $reservation->reservation_date?->format('d M Y') ?: '—' }}</span></div>
                        <div class="rd-detail-row"><span class="rd-detail-label">Time</span><span class="rd-detail-value">{{ $start && $end ? $start.'–'.$end : '—' }}</span></div>
                        <div class="rd-detail-row"><span class="rd-detail-label">Attendees</span><span class="rd-detail-value">{{ $reservation->attendee_count ?? '—' }}</span></div>
                        <div class="rd-detail-row"><span class="rd-detail-label">Resources</span><span class="rd-detail-value">{{ $reservation->resourceNames() ?: '—' }}</span></div>
                        @if($reservation->cancellation_reason)
                            <div class="rd-detail-row"><span class="rd-detail-label">Cancel reason</span><span class="rd-detail-value">{{ $reservation->cancellation_reason }}</span></div>
                        @endif
                    </div>
                </div>
            </section>

            <section class="rd-card">
                <h2 class="rd-section-title"><i class="ti ti-building"></i> Booked resources</h2>
                <div class="rd-card-body">
                    @if($reservation->items->isEmpty())
                        <p class="rd-empty mb-0">No resources booked.</p>
                    @else
                        <div class="table-responsive rd-table">
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
                                            <td data-label="Category">{{ $item->resource?->type?->category?->name ?? '—' }}</td>
                                            <td data-label="Item status"><span class="badge {{ $reservation->statusEnum()->badgeClass() }}">{{ $reservation->bookedItemStatusLabel($item) }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </section>
        </div>
        <div class="col-12 col-lg-4">
            <aside class="rd-card">
                <h2 class="rd-section-title"><i class="ti ti-history"></i> Status history</h2>
                <div class="rd-card-body">
                    @forelse($reservation->statusHistory as $history)
                        <div class="rd-history">
                            <div class="rd-history-title">{{ $history->from_status ? $statusLabel($history->from_status).' → ' : '' }}{{ $statusLabel($history->to_status) }}</div>
                            <div class="rd-history-meta">{{ $history->actor?->name ?? 'System' }} · {{ $history->created_at?->timezone($tz)->format('d M Y H:i') }}</div>
                            @if($history->reason)
                                <div class="rd-history-reason">{{ $history->reason }}</div>
                            @endif
                        </div>
                    @empty
                        <p class="rd-empty mb-0">No history recorded.</p>
                    @endforelse
                </div>
            </aside>
            @can('cancel', $reservation)
                <aside class="rd-card">
                    <h2 class="rd-section-title"><i class="ti ti-ban"></i> Cancel reservation</h2>
                    <div class="rd-card-body">
                        <form method="POST" action="{{ route('reservations.cancel', $reservation) }}">
                            @csrf
                            <label class="form-label" for="cancellation_reason">Cancellation reason</label>
                            <textarea id="cancellation_reason" name="cancellation_reason" class="form-control mb-3" rows="3" required placeholder="Reason is required"></textarea>
                            <button type="submit" class="rd-cancel">Cancel reservation</button>
                        </form>
                    </div>
                </aside>
            @endcan
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/reservation-show.js') }}?v=1"></script>
@endpush
