@extends('layouts.app')

@section('title', 'Reservation Calendar')

@section('content')
@include('reservations.partials.management-styles')
<div class="reservation-page reservation-calendar-page">
    <header class="reservation-page-header d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="mb-2">Reservation Calendar</h1>
            <p class="mb-0">Browse bookings, check resource availability, and open reservation details.</p>
        </div>
        <a href="{{ route('reservations.calendar') }}" class="btn btn-outline-secondary">Holiday calendar</a>
        @if($canCreate)
            <a href="{{ $createUrl }}" class="btn btn-primary"><i class="ti ti-calendar-plus me-1" aria-hidden="true"></i>Create Reservation</a>
        @endif
    </header>

    <form id="reservation-calendar-filters" class="card mb-3" aria-label="Filter calendar reservations">
        <div class="card-body p-3">
            <div class="row g-3 align-items-end">
                <div class="col-12 col-sm-6 col-xl-2">
                    <label class="form-label" for="calendar-location">Location</label>
                    <select class="form-select" id="calendar-location" name="location_id">
                        <option value="">All locations</option>
                        @foreach($locations as $location)<option value="{{ $location->id }}">{{ $location->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-xl-2">
                    <label class="form-label" for="calendar-category">Resource category</label>
                    <select class="form-select" id="calendar-category" name="resource_category_id">
                        <option value="">All categories</option>
                        @foreach($categories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-xl-2">
                    <label class="form-label" for="calendar-type">Resource type</label>
                    <select class="form-select" id="calendar-type" name="resource_type_id">
                        <option value="">All types</option>
                        @foreach($resourceTypes as $type)<option value="{{ $type->id }}">{{ $type->name }}{{ $type->category ? ' · '.$type->category->name : '' }}</option>@endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-xl-2">
                    <label class="form-label" for="calendar-resource">Resource</label>
                    <select class="form-select" id="calendar-resource" name="resource_id">
                        <option value="">All resources</option>
                        @foreach($resources as $resource)<option value="{{ $resource->id }}">{{ $resource->name_model }}</option>@endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-xl-2">
                    <label class="form-label" for="calendar-owner">Reservation owner</label>
                    <select class="form-select" id="calendar-owner" name="requester_id">
                        <option value="">All owners</option>
                        @foreach($owners as $owner)<option value="{{ $owner->id }}">{{ $owner->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-xl-2">
                    <label class="form-label" for="calendar-status">Reservation status</label>
                    <select class="form-select" id="calendar-status" name="status">
                        <option value="">All statuses</option>
                        @foreach($statuses as $status)<option value="{{ $status->value }}">{{ $status->label() }}</option>@endforeach
                    </select>
                </div>
            </div>
        </div>
    </form>

    <div class="calendar-info-row d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <div class="d-flex flex-wrap gap-3 small" aria-label="Calendar status legend">
            <span><i class="calendar-legend-dot booked" aria-hidden="true"></i>Approved</span>
            <span><i class="calendar-legend-dot pending" aria-hidden="true"></i>Pending approval</span>
            <span><i class="calendar-legend-dot conflict" aria-hidden="true"></i>Conflicting bookings</span>
            <span><i class="calendar-legend-dot cancelled" aria-hidden="true"></i>Inactive</span>
        </div>
        <span id="calendar-refresh-status" class="small text-muted" aria-live="polite">Calendar updates automatically.</span>
    </div>

    <div class="card calendar-card">
        <div class="card-body p-3 p-lg-4">
            <div id="reservationCalendar"
                 data-events-url="{{ $eventsUrl }}"
                 data-availability-url="{{ $availabilityUrl }}"
                 data-create-url="{{ $createUrl }}"
                 data-can-create="{{ $canCreate ? '1' : '0' }}"
                 aria-label="Resource reservation calendar"></div>
            <div id="calendar-slot-feedback" class="calendar-slot-feedback alert mt-3 mb-0" role="status" aria-live="polite" hidden></div>
        </div>
    </div>
</div>

<div class="modal fade" id="reservation-calendar-detail" tabindex="-1" aria-labelledby="reservation-calendar-detail-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title fs-5 mb-1" id="reservation-calendar-detail-title"></h2>
                    <span id="reservation-calendar-detail-status" class="badge rounded-pill"></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Resource</dt><dd class="col-sm-8" id="reservation-calendar-detail-resource"></dd>
                    <dt class="col-sm-4">Reservation owner</dt><dd class="col-sm-8" id="reservation-calendar-detail-owner"></dd>
                    <dt class="col-sm-4">Location</dt><dd class="col-sm-8" id="reservation-calendar-detail-location"></dd>
                    <dt class="col-sm-4">When</dt><dd class="col-sm-8" id="reservation-calendar-detail-time"></dd>
                    <dt class="col-sm-4">Duration</dt><dd class="col-sm-8" id="reservation-calendar-detail-duration"></dd>
                    <dt class="col-sm-4">Purpose</dt><dd class="col-sm-8" id="reservation-calendar-detail-purpose"></dd>
                </dl>
                <div id="reservation-calendar-conflict" class="alert alert-danger mt-3 mb-0" hidden>This booking overlaps another reservation for the same resource. Please review the conflict.</div>
            </div>
            <div class="modal-footer">
                <a id="reservation-calendar-detail-link" class="btn btn-primary" hidden>View reservation</a>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @vite('resources/js/reservation-calendar.js')
@endpush
