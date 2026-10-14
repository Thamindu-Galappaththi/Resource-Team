@extends('layouts.app')

@section('title', 'Create Hostel Reservation')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/create-user.css') }}?v=15">
<link rel="stylesheet" href="{{ asset('css/hostel-create.css') }}?v=2">
@endpush

@section('content')
@php
    $selectedRoomType = $roomTypes->firstWhere('id', (int) old('room_type_id'));
    $selectedLocation = $locations->firstWhere('id', (int) old('location_id'));
@endphp
<div class="container-fluid mt-4 hc-page">
    <div class="hc-header">
        <div>
            <h1 class="hc-title">Create Hostel Reservation</h1>
            <p class="hc-subtitle mb-0">Book a room, assign the stay dates, and send it for hostel approval.</p>
        </div>
        <a href="{{ route('hostel.index') }}" class="btn hc-back"><i class="ti ti-arrow-left me-1"></i>Hostel Reservations</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger hc-auto-alert" role="alert">{{ $errors->first() }}</div>
    @endif
    <div id="hc-flash" class="alert hc-auto-alert d-none" role="status"></div>

    <div class="row g-4 align-items-start">
        <div class="col-12 col-lg-8">
            <form method="POST" action="{{ route('hostel.store') }}" id="hostel-booking-form" class="hc-card">
                @csrf
                <h2 class="hc-section-title"><i class="ti ti-bed"></i> Booking details</h2>
                <div class="row g-3 pb-1">
                    <div class="col-12">
                        <label for="reservation_name" class="form-label">Reservation name <span class="text-danger">*</span></label>
                        <input type="text" id="reservation_name" name="reservation_name" class="form-control @error('reservation_name') is-invalid @enderror" value="{{ old('reservation_name') }}" placeholder="e.g. Summer Internship 2026 Group" required>
                        @error('reservation_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="guest_name" class="form-label">Guest name <span class="text-danger">*</span></label>
                        <input type="text" id="guest_name" name="guest_name" class="form-control @error('guest_name') is-invalid @enderror" value="{{ old('guest_name') }}" placeholder="e.g. Guest Name" required>
                        @error('guest_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="guest_phone" class="form-label">Guest phone number</label>
                        <input type="tel" id="guest_phone" name="guest_phone" class="form-control @error('guest_phone') is-invalid @enderror" value="{{ old('guest_phone') }}" autocomplete="tel" placeholder="e.g. +94 77 123 4567">
                        @error('guest_phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label for="guest_identity_number" class="form-label">Guest ID / passport number</label>
                        <input type="text" id="guest_identity_number" name="guest_identity_number" class="form-control @error('guest_identity_number') is-invalid @enderror" value="{{ old('guest_identity_number') }}" autocomplete="off">
                        @error('guest_identity_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-sm-6">
                        <label for="check_in_date" class="form-label">Check-in date <span class="text-danger">*</span></label>
                        <input type="date" id="check_in_date" name="check_in_date" class="form-control @error('check_in_date') is-invalid @enderror" value="{{ old('check_in_date') }}" min="{{ $minCheckIn }}" required>
                        @error('check_in_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-sm-6">
                        <label for="check_out_date" class="form-label">Check-out date <span class="text-danger">*</span></label>
                        <input type="date" id="check_out_date" name="check_out_date" class="form-control @error('check_out_date') is-invalid @enderror" value="{{ old('check_out_date') }}" required>
                        @error('check_out_date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 col-sm-6">
                        <label for="room_type_id" class="form-label">Room category <span class="text-danger">*</span></label>
                        <x-rm-select id="room_type_id" name="room_type_id" class="{{ $errors->has('room_type_id') ? 'is-invalid' : '' }}" placeholder="Select category" :value="(string) old('room_type_id', '')" :selected-label="$selectedRoomType->name ?? 'Select category'" :required="true"
                            :options="collect([['value' => '', 'label' => 'Select category']])->concat($roomTypes->map(fn ($type) => ['value' => (string) $type->id, 'label' => $type->name]))->all()" />
                        @error('room_type_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        @if($roomTypes->isEmpty())
                            <p class="hc-hint mb-0">Create a “Hostel Room” category and room types (Single, Double) under Create Resources first.</p>
                        @endif
                    </div>
                    <div class="col-12 col-sm-6">
                        <label for="number_of_guests" class="form-label">Number of guests <span class="text-danger">*</span></label>
                        <input type="number" id="number_of_guests" name="number_of_guests" class="form-control @error('number_of_guests') is-invalid @enderror" min="1" step="1" value="{{ old('number_of_guests', 1) }}" required>
                        @error('number_of_guests')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label for="location_id" class="form-label">Hostel location <span class="text-danger">*</span></label>
                        <x-rm-select id="location_id" name="location_id" class="{{ $errors->has('location_id') ? 'is-invalid' : '' }}" placeholder="Select location" :value="(string) old('location_id', '')" :selected-label="$selectedLocation->name ?? 'Select location'" :required="true"
                            :options="collect([['value' => '', 'label' => 'Select location']])->concat($locations->map(fn ($location) => ['value' => (string) $location->id, 'label' => $location->name]))->all()" />
                        @error('location_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label for="special_requirements" class="form-label">Special requirements</label>
                        <textarea id="special_requirements" name="special_requirements" rows="4" class="form-control @error('special_requirements') is-invalid @enderror" placeholder="Mention any medical needs, accessibility requirements, or preference for floor level...">{{ old('special_requirements') }}</textarea>
                        @error('special_requirements')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="hc-actions">
                    <button type="submit" class="hc-submit" @disabled($roomTypes->isEmpty())>
                        <i class="ti ti-circle-check me-1"></i>Create reservation
                    </button>
                    <p class="hc-hint mb-0">The system assigns a free room of the selected category and blocks overlapping dates. Check-in {{ config('hostel.check_in_time') }}, check-out {{ config('hostel.check_out_time') }} (midnight at the end of the checkout date).</p>
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
                        <p>Contact the logistics desk at ext. 404 for group booking inquiries.</p>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/resource-select.js') }}?v=1"></script>
<script src="{{ asset('js/cu-dropdowns.js') }}?v=4"></script>
<script src="{{ asset('js/hostel-create.js') }}?v=1"></script>
@endpush
