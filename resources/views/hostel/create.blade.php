@extends('layouts.app')

@section('title', isset($reservation) ? 'Edit Hostel Reservation' : 'Create Hostel Reservation')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/create-user.css') }}?v=15">
<link rel="stylesheet" href="{{ asset('css/hostel-create.css') }}?v=9">
<link rel="stylesheet" href="{{ asset('css/special-approval.css') }}?v=1">
@endpush

@section('content')
@php
    $editing = $reservation ?? null;
    $stay = $editing?->hostelStay;
    $selectedLocation = $locations->firstWhere('id', (int) old('location_id', $editing?->location_id));
    $guestTitle = (string) old('guest_title', $stay?->guest_title ?? '');
    $guestCount = max(1, min($maxGuests, (int) old('number_of_guests', $stay?->number_of_guests ?? 1)));
    $employeeIds = old('employee_ids', $stay?->employee_ids ?? []);
    $employeeIdError = $errors->first('employee_ids');
    $bedGroup = (string) old('bed_group', $stay?->bed_group ?? '');
@endphp
<div class="container-fluid mt-4 hc-page">
    <div class="hc-header">
        <div>
            <h1 class="hc-title">{{ $editing ? 'Edit Hostel Reservation' : 'Create Hostel Reservation' }}</h1>
            <p class="hc-subtitle mb-0">{{ $editing ? 'Update this stay request and keep it in the approval queue.' : 'Book hostel beds for your guests and send the request for hostel approval.' }}</p>
        </div>
        @if(auth()->user()?->usesSltEmployeeWorkspace())
            @if($editing)
                <a href="{{ route('hostel.mine') }}" class="btn hc-back"><i class="ti ti-arrow-left me-1"></i>Back to my hostel requests</a>
            @endif
        @else
            <a href="{{ route('hostel.index') }}" class="btn hc-back"><i class="ti ti-arrow-left me-1"></i>Hostel Reservations</a>
        @endif
    </div>

    @if($errors->any())
        <div class="alert alert-danger hc-auto-alert" role="alert">{{ $errors->first() }}</div>
    @endif
    <div id="hc-flash" class="alert hc-auto-alert d-none" role="status"></div>

    <div class="row g-4 align-items-start">
        <div class="col-12 col-lg-8">
            <form method="POST" action="{{ $editing ? route('hostel.update', $editing) : route('hostel.store') }}" id="hostel-booking-form" class="hc-card" enctype="multipart/form-data">
                @csrf
                @if($editing)
                    @method('PUT')
                @endif
                <h2 class="hc-section-title"><i class="ti ti-bed"></i> Booking details</h2>
                <div class="row g-3 pb-1">
                    <div class="col-12 col-sm-4 col-md-3">
                        <label for="guest_title" class="form-label">Title <span class="text-danger">*</span></label>
                        <x-rm-select id="guest_title" name="guest_title" class="{{ $errors->has('guest_title') ? 'is-invalid' : '' }}" placeholder="Select" :value="$guestTitle" :selected-label="$guestTitle !== '' ? $guestTitle : 'Select'" :required="true"
                            :options="collect([['value' => '', 'label' => 'Select']])->concat(collect($guestTitles)->map(fn ($title) => ['value' => $title, 'label' => $title]))->all()" />
                        @error('guest_title')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-sm-8 col-md-9">
                        <label for="guest_name" class="form-label">Guest name <span class="text-danger">*</span></label>
                        <input type="text" id="guest_name" name="guest_name" class="form-control @error('guest_name') is-invalid @enderror" value="{{ old('guest_name', $stay?->guest_name) }}" placeholder="e.g. Kasun Madushanka" required>
                        @error('guest_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="guest_phone" class="form-label">Guest phone number <span class="text-danger">*</span></label>
                        <input type="tel" id="guest_phone" name="guest_phone" class="form-control @error('guest_phone') is-invalid @enderror" value="{{ old('guest_phone', $guestPhone ?? '') }}" autocomplete="tel" placeholder="e.g. +94 77 123 4567" required>
                        @error('guest_phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="number_of_guests" class="form-label">Number of guests <span class="text-danger">*</span></label>
                        <input type="number" id="number_of_guests" name="number_of_guests" class="form-control @error('number_of_guests') is-invalid @enderror" min="1" max="{{ $maxGuests }}" step="1" value="{{ $guestCount }}" required>
                        @error('number_of_guests')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <fieldset class="hc-employee-ids" id="hc-employee-ids" data-label="Employee ID" aria-describedby="hc-employee-ids-hint">
                            <legend class="form-label mb-2">Employee IDs <span class="text-danger">*</span></legend>
                            <p id="hc-employee-ids-hint" class="hc-hint mt-0 mb-2">Enter one employee ID for each guest.</p>
                            <div class="row g-3" data-hc-employee-list>
                                @for($i = 0; $i < $guestCount; $i++)
                                    <div class="col-12 col-sm-6" data-hc-employee-row>
                                        <label for="employee_id_{{ $i }}" class="form-label">Employee ID {{ $guestCount > 1 ? '(guest '.($i + 1).')' : '' }}</label>
                                        <input type="text" id="employee_id_{{ $i }}" name="employee_ids[]" class="form-control @error('employee_ids.'.$i) is-invalid @enderror" value="{{ $employeeIds[$i] ?? '' }}" maxlength="50" autocomplete="off" placeholder="010456" required>
                                        @error('employee_ids.'.$i)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                @endfor
                            </div>
                            @if($employeeIdError)<div class="invalid-feedback d-block">{{ $employeeIdError }}</div>@endif
                        </fieldset>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label for="check_in_date" class="form-label">Check-in date <span class="text-danger">*</span></label>
                        <input type="date" id="check_in_date" name="check_in_date" class="form-control @error('check_in_date') is-invalid @enderror" value="{{ old('check_in_date', $prefillCheckIn ?? '') }}" min="{{ $minCheckIn }}" required>
                        @error('check_in_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-sm-6">
                        <label for="check_in_time" class="form-label">Check-in time <span class="text-danger">*</span></label>
                        <input type="time" id="check_in_time" name="check_in_time" class="form-control @error('check_in_time') is-invalid @enderror" value="{{ old('check_in_time', $prefillCheckInTime ?? '') }}" required>
                        @error('check_in_time')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-sm-6">
                        <label for="check_out_date" class="form-label">Check-out date <span class="text-danger">*</span></label>
                        <input type="date" id="check_out_date" name="check_out_date" class="form-control @error('check_out_date') is-invalid @enderror" value="{{ old('check_out_date', $prefillCheckOut ?? '') }}" min="{{ $minCheckIn }}" required>
                        @error('check_out_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-sm-6">
                        <label for="check_out_time" class="form-label">Check-out time <span class="text-danger">*</span></label>
                        <input type="time" id="check_out_time" name="check_out_time" class="form-control @error('check_out_time') is-invalid @enderror" value="{{ old('check_out_time', $prefillCheckOutTime ?? '') }}" required>
                        @error('check_out_time')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="location_id" class="form-label">Hostel location <span class="text-danger">*</span></label>
                        <x-rm-select id="location_id" name="location_id" class="{{ $errors->has('location_id') ? 'is-invalid' : '' }}" placeholder="Select location" :value="(string) old('location_id', $editing?->location_id ?? '')" :selected-label="$selectedLocation->name ?? 'Select location'" :required="true"
                            :options="collect([['value' => '', 'label' => 'Select location']])->concat($locations->map(fn ($location) => ['value' => (string) $location->id, 'label' => $location->name]))->all()" />
                        @error('location_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        @unless($hasRooms)
                            <p class="hc-hint mb-0">Add bed rooms under the “Hostel Room” category in Create Resources first.</p>
                        @endunless
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="bed_group" class="form-label">Bed group <span class="text-danger">*</span></label>
                        <x-rm-select id="bed_group" name="bed_group" class="{{ $errors->has('bed_group') ? 'is-invalid' : '' }}" placeholder="Select bed group" :value="$bedGroup" :selected-label="$bedGroups[$bedGroup]['label'] ?? 'Select bed group'" :required="true"
                            :options="collect([['value' => '', 'label' => 'Select bed group']])->concat(collect($bedGroups)->map(fn ($group, $key) => ['value' => $key, 'label' => $group['label']]))->values()->all()" />
                        @error('bed_group')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <p class="hc-hint mb-0">One bed is reserved for each guest.</p>
                    </div>
                    <div class="col-12">
                        <label for="special_requirements" class="form-label">Special requirements</label>
                        <textarea id="special_requirements" name="special_requirements" rows="4" class="form-control @error('special_requirements') is-invalid @enderror" placeholder="Mention any medical needs, accessibility requirements, or preference for floor level...">{{ old('special_requirements', $stay?->special_requirements) }}</textarea>
                        @error('special_requirements')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        @include('reservations.partials.special-approval', ['reservation' => $editing])
                    </div>
                </div>
                <div class="hc-actions">
                    <button type="submit" class="hc-submit" @disabled(! $hasRooms)>
                        <i class="ti ti-circle-check me-1"></i>{{ $editing ? 'Update reservation' : 'Create reservation' }}
                    </button>
                </div>
            </form>
        </div>
        <div class="col-12 col-lg-4">
            <aside class="hc-aside" aria-labelledby="room-features-heading">
                <h2 id="room-features-heading" class="hc-aside-title"><i class="ti ti-info-circle"></i> Room features</h2>
                <div class="hc-aside-body">
                    <p class="hc-aside-lead">Included with all selections:</p>
                    <ul class="hc-feature-list">
                        <li><i class="ti ti-wifi" aria-hidden="true"></i>Free high-speed Wi-Fi</li>
                        <li><i class="ti ti-snowflake" aria-hidden="true"></i>Climate control</li>
                        <li><i class="ti ti-brush" aria-hidden="true"></i>Daily housekeeping</li>
                        <li><i class="ti ti-shield-check" aria-hidden="true"></i>24/7 security access</li>
                        <li><i class="ti ti-wash-machine" aria-hidden="true"></i>Laundry facilities</li>
                    </ul>
                </div>
                <div class="hc-help">
                    <i class="ti ti-help" aria-hidden="true"></i>
                    <div>
                        <h3>Need help?</h3>
                        <p>Contact the logistics desk at <a href="tel:+94112954069">011 2954 069</a> for group booking inquiries.</p>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/resource-select.js') }}?v=2"></script>
<script src="{{ asset('js/cu-dropdowns.js') }}?v=4"></script>
<script src="{{ asset('js/hostel-create.js') }}?v=4"></script>
<script src="{{ asset('js/special-approval.js') }}?v=1"></script>
@endpush
