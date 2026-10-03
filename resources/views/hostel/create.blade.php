@extends('layouts.app')

@section('title', 'Create Hostel Reservation')

@section('content')
<style>
    .hostel-create { max-width: 1120px; margin: 0 auto; }
    .hostel-create .booking-card { padding: 32px; }
    .hostel-create .form-label { font-weight: 600; color: #151a20; }
    .hostel-create .form-control, .hostel-create .form-select {
        background-color: #f3f4f6; border-color: #ced1d5; min-height: 48px; color: #151a20;
    }
    .hostel-create textarea.form-control { min-height: 120px; }
    .hostel-create .booking-heading { border-bottom: 1px solid #d5d8dc; padding-bottom: 22px; margin-bottom: 28px; }
    .hostel-create .features-heading { background: #d4e2ff; color: #164f73; padding: 18px 22px; }
    .hostel-create .feature-list { list-style: none; padding: 0; margin: 0; }
    .hostel-create .feature-list li { display: flex; gap: 12px; align-items: center; margin-bottom: 16px; }
    .hostel-create .feature-list i { color: #326b8d; font-size: 21px; }
    .hostel-create .help-panel { background: #f3f4f6; border-top: 1px solid #d5d8dc; padding: 22px; }
    .hostel-create .create-button { background: #285fa4; border-color: #285fa4; min-height: 52px; font-weight: 600; }
    @media (max-width: 575px) { .hostel-create .booking-card { padding: 22px; } }
</style>
<div class="hostel-create py-4">
    <div class="mb-4">
        <h1 class="h3 text-white mb-2">Create Hostel Reservation</h1>
        <nav aria-label="Breadcrumb" class="small text-white">
            <a href="{{ route('hostel.index') }}" class="text-white">Reservations</a>
            <i class="ti ti-chevron-right mx-2" aria-hidden="true"></i>
            <span aria-current="page" class="fw-semibold">New Hostel Booking</span>
        </nav>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="row g-4 align-items-start">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body booking-card">
                    <h2 class="h4 booking-heading"><i class="ti ti-bed me-2 text-primary" aria-hidden="true"></i>Booking Details</h2>
                    <form method="POST" action="{{ route('hostel.store') }}" id="hostel-booking-form">
                        @csrf
                        <div class="row g-4">
                            <div class="col-12">
                                <label for="reservation_name" class="form-label">Reservation Name</label>
                                <input type="text" id="reservation_name" name="reservation_name" class="form-control"
                                       value="{{ old('reservation_name') }}"
                                       placeholder="e.g. Summer Internship 2026 Group" required>
                            </div>
                            <div class="col-12">
                                <label for="guest_name" class="form-label">Guest Name</label>
                                <input type="text" id="guest_name" name="guest_name" class="form-control"
                                       value="{{ old('guest_name') }}" placeholder="e.g. Guest Name" required>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="guest_phone" class="form-label">Guest Phone Number</label>
                                <input type="tel" id="guest_phone" name="guest_phone" class="form-control"
                                       value="{{ old('guest_phone') }}" autocomplete="tel" placeholder="e.g. +94 77 123 4567">
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="guest_identity_number" class="form-label">Guest ID / Passport Number</label>
                                <input type="text" id="guest_identity_number" name="guest_identity_number" class="form-control"
                                       value="{{ old('guest_identity_number') }}" autocomplete="off">
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="check_in_date" class="form-label">Check-in Date</label>
                                <input type="date" id="check_in_date" name="check_in_date" class="form-control"
                                       value="{{ old('check_in_date') }}" min="{{ $minCheckIn }}" required>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="check_out_date" class="form-label">Check-out Date</label>
                                <input type="date" id="check_out_date" name="check_out_date" class="form-control"
                                       value="{{ old('check_out_date') }}" required>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="room_type_id" class="form-label">Room Category</label>
                                <select id="room_type_id" name="room_type_id" class="form-select" required>
                                    <option value="">Select category</option>
                                    @foreach($roomTypes as $type)
                                        <option value="{{ $type->id }}" @selected(old('room_type_id') == $type->id)>{{ $type->name }}</option>
                                    @endforeach
                                </select>
                                @if($roomTypes->isEmpty())
                                    <small class="text-muted">Create a “Hostel Room” category and room types (Single, Double) under Create Resources first.</small>
                                @endif
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="number_of_guests" class="form-label">Number of Guests</label>
                                <input type="number" id="number_of_guests" name="number_of_guests" class="form-control" min="1" step="1" value="{{ old('number_of_guests', 1) }}" required>
                            </div>
                            <div class="col-12">
                                <label for="location_id" class="form-label">Hostel Location</label>
                                <select id="location_id" name="location_id" class="form-select" required>
                                    <option value="">Select location</option>
                                    @foreach($locations as $location)
                                        <option value="{{ $location->id }}" @selected(old('location_id') == $location->id)>{{ $location->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="special_requirements" class="form-label">Special Requirements</label>
                                <textarea id="special_requirements" name="special_requirements" rows="4" class="form-control"
                                          placeholder="Mention any medical needs, accessibility requirements, or preference for floor level...">{{ old('special_requirements') }}</textarea>
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-primary create-button w-100">
                                    <i class="ti ti-circle-check-filled me-2" aria-hidden="true"></i>Create Reservation
                                </button>
                                <p class="small text-muted mt-2 mb-0">The system assigns a free room of the selected category and blocks overlapping dates. Check-in {{ config('hostel.check_in_time') }}, check-out {{ config('hostel.check_out_time') }}.</p>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <aside class="card border-0 shadow-sm overflow-hidden" aria-labelledby="room-features-heading">
                <h2 id="room-features-heading" class="h6 features-heading mb-0"><i class="ti ti-info-circle me-2" aria-hidden="true"></i>Room Features</h2>
                <div class="p-4">
                    <p class="small mb-3">Included with all selections:</p>
                    <ul class="feature-list">
                        <li><i class="ti ti-wifi" aria-hidden="true"></i>Free High-speed Wi-Fi</li>
                        <li><i class="ti ti-snowflake" aria-hidden="true"></i>Climate Control</li>
                        <li><i class="ti ti-brush" aria-hidden="true"></i>Daily Housekeeping</li>
                        <li><i class="ti ti-shield-check" aria-hidden="true"></i>24/7 Security Access</li>
                        <li class="mb-0"><i class="ti ti-wash-machine" aria-hidden="true"></i>Laundry Facilities</li>
                    </ul>
                </div>
                <div class="help-panel d-flex gap-2">
                    <i class="ti ti-help fs-5" aria-hidden="true"></i>
                    <div>
                        <h3 class="h6 mb-1">Need Help?</h3>
                        <p class="small mb-0">Contact the logistics desk at ext. 404 for group booking inquiries.</p>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</div>
@endsection
