@extends('layouts.app')

@section('title', 'Create Reservation')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/create-user.css') }}?v=15">
<link rel="stylesheet" href="{{ asset('css/reservation-create.css') }}?v=7">
@endpush

@section('content')
<div class="container-fluid mt-4 rv-page">
    <div class="rv-header">
        <div>
            <h1 class="rv-title">Create Reservation</h1>
            <p class="rv-subtitle mb-0">Select a resource, date, and time. Overlapping bookings are blocked automatically.</p>
        </div>
        <a href="{{ route('reservations.index') }}" class="btn rv-back"><i class="ti ti-arrow-left me-1"></i>Existing Reservations</a>
    </div>

    <div class="alert alert-info d-flex align-items-start gap-2" role="note">
        <i class="ti ti-info-circle mt-1" aria-hidden="true"></i>
        <div>
            Hostel bookings use check-in and check-out dates instead of start and end times, so create them separately from Hostel Reservations.
            @can('createHostel', \App\Models\Reservation::class)
                <a href="{{ route('hostel.create') }}" class="alert-link">Create a hostel reservation</a>.
            @endcan
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger rv-auto-alert" role="alert">
            {{ $errors->first() }}
        </div>
    @endif
    <div id="rv-flash" class="alert rv-auto-alert d-none" role="status"></div>

    <form method="POST" action="{{ route('reservations.store') }}" id="reservationForm">
        @csrf

        <section class="rv-card">
            <h2 class="rv-card-title"><i class="ti ti-calendar-plus"></i> Reservation request</h2>

            <div class="rv-block">
                <h3 class="rv-block-title"><i class="ti ti-packages"></i> Resource</h3>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="location_id">Location <span class="text-danger">*</span></label>
                        <x-rm-select id="location_id" name="location_id" class="{{ $errors->has('location_id') ? 'is-invalid' : '' }}"
                            placeholder="Select a location" :required="true" :value="(string) ($prefillLocationId ?? '')"
                            :options="collect([['value' => '', 'label' => 'Select a location']])->concat($lookups['locations']->map(fn ($location) => ['value' => (string) $location->id, 'label' => $location->name]))->all()" />
                        @error('location_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="category_id">Resource category <span class="text-danger">*</span></label>
                        <x-rm-select id="category_id" name="category_id" class="{{ $errors->has('category_id') ? 'is-invalid' : '' }}"
                            placeholder="Select a category" :required="true" :value="(string) ($prefillCategoryId ?? '')"
                            :options="collect([['value' => '', 'label' => 'Select a category']])->concat($lookups['categories']->map(fn ($category) => ['value' => (string) $category->id, 'label' => $category->name]))->all()" />
                        @error('category_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="resource_id">Resource <span class="text-danger">*</span></label>
                        <x-rm-select id="resource_id" name="resource_id" class="{{ $errors->has('resource_id') ? 'is-invalid' : '' }}"
                            placeholder="Select a resource" :required="true" :value="(string) ($prefillResourceId ?? '')"
                            :options="collect([['value' => '', 'label' => 'Select a resource']])->concat($lookups['resources']->map(fn ($resource) => ['value' => (string) $resource['id'], 'label' => $resource['name_model'].($resource['serial_number'] ? ' ('.$resource['serial_number'].')' : '').($resource['location_name'] ? ' — '.$resource['location_name'] : '')]))->all()" />
                        @error('resource_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12" id="linkedWrap" hidden>
                        <label class="form-label">Additional resources</label>
                        <div id="linkedResources" class="rv-linked"></div>
                        <p class="rv-hint mb-0 mt-2">Selected extras are booked in the same slot and checked together.</p>
                    </div>
                </div>
            </div>

            <div class="rv-block">
                <h3 class="rv-block-title"><i class="ti ti-calendar-event"></i> Schedule</h3>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="reservation_date">Reservation date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('reservation_date') is-invalid @enderror" id="reservation_date" name="reservation_date" value="{{ $prefillDate }}" min="{{ $today }}" required>
                        @error('reservation_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="start_time">Start time <span class="text-danger">*</span></label>
                        <input type="time" class="form-control @error('start_time') is-invalid @enderror" id="start_time" name="start_time" value="{{ $prefillStart }}" required>
                        @error('start_time')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="end_time">End time <span class="text-danger">*</span></label>
                        <input type="time" class="form-control @error('end_time') is-invalid @enderror" id="end_time" name="end_time" value="{{ $prefillEnd }}" required>
                        @error('end_time')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <div id="holidayAlert" class="rv-status is-holiday" hidden></div>
                    </div>
                    <div class="col-12">
                        <div id="availabilityAlert" class="rv-status idle" role="status">
                            <i class="ti ti-info-circle"></i>
                            <span>Select a resource and time slot to check availability.</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rv-block">
                <h3 class="rv-block-title"><i class="ti ti-notes"></i> Details</h3>
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label" for="purpose">Purpose of Reservation <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('purpose') is-invalid @enderror" id="purpose" name="purpose" value="{{ old('purpose') }}" maxlength="500" placeholder="e.g. CCNA Batch 12 practical lab" required>
                        @error('purpose')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="attendee_count">Attendee count</label>
                        <input type="number" min="1" class="form-control" id="attendee_count" name="attendee_count" value="{{ old('attendee_count') }}">
                    </div>
                </div>
            </div>

            <div class="rv-actions">
                <button type="submit" class="rv-submit" id="submitReservation">Submit for approval</button>
            </div>
        </section>
    </form>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/resource-select.js') }}?v=2"></script>
<script src="{{ asset('js/cu-dropdowns.js') }}?v=4"></script>
<script>
window.RVCreate = {
    lookups: @json($lookups),
    holidays: @json($holidays),
    availabilityUrl: @json(route('reservations.availability')),
    prefillResourceId: @json($prefillResourceId ? (string) $prefillResourceId : null),
    prefillResourceIds: @json($prefillResourceIds),
    prefillLocationId: @json($prefillLocationId ? (string) $prefillLocationId : null),
    prefillCategoryId: @json($prefillCategoryId ? (string) $prefillCategoryId : null),
};
</script>
<script src="{{ asset('js/reservation-create.js') }}?v=3"></script>
@endpush
