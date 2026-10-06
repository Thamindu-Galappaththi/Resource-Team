@extends('layouts.app')

@section('title', 'Create Hostel Reservation')

@section('content')
<style>
    /* Scoped to hostel pages; follows the Resource Calendar's visual style. */
    .hostel-page {
        width: 100%;
        margin: 0 auto;
        padding: 35px 25px 50px !important;
        min-height: 100vh;
        background: transparent;
        color: #111827;
        font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }
    .hostel-page h1, .hostel-page > div > div > h2 {
        color: #0f172a !important;
        font-size: 26px;
        font-weight: 800;
        letter-spacing: -.02em;
    }
    .hostel-page .page-subtitle, .hostel-page > div > div > p,
    .hostel-page nav, .hostel-page nav a { color: #6b7280 !important; font-size: 14px; }
    .hostel-reservations > .d-flex h1,
    .hostel-reservations > .d-flex .page-subtitle,
    .hostel-page.hostel-details > .d-flex > div > h2,
    .hostel-page.hostel-details > .d-flex > div > p { color: #fff !important; }
    .hostel-page.hostel-create > .mb-4 > h1,
    .hostel-page.hostel-create > .mb-4 > nav,
    .hostel-page.hostel-create > .mb-4 > nav a,
    .hostel-page.hostel-create > .mb-4 > nav span,
    .hostel-page.hostel-create > .mb-4 > nav i { color: #fff !important; }
    .hostel-page .card, .hostel-page .summary-card {
        background: rgba(255,255,255,.82);
        border: 1px solid rgba(235,238,244,.8) !important;
        border-radius: 10px;
        box-shadow: 0 8px 25px rgba(0,0,0,.08) !important;
    }
    .hostel-reservations > .card { background: transparent; border: 0 !important; box-shadow: none !important; }
    .hostel-reservations > .card > .card-body { padding: 0 !important; }
    .hostel-page .summary-card { height: 100%; padding: 16px; transition: transform .22s ease, box-shadow .22s ease; }
    .hostel-page .summary-card:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(23,105,232,.12) !important; }
    .hostel-page .summary-label { font-size: 12px; color: #6b7280; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
    .hostel-page .summary-value { margin-top: 8px; font-size: 26px; font-weight: 900; color: #0f172a; }
    .hostel-page .filter-panel {
        background: rgba(255,255,255,.78);
        border: 1px solid rgba(235,238,244,.8);
        border-radius: 10px;
        box-shadow: 0 8px 25px rgba(0,0,0,.06);
    }
    .hostel-page .form-label, #hostel-reservation-modals .form-label { font-size: 12px; color: #374151; font-weight: 700; }
    .hostel-page .form-control, .hostel-page .form-select,
    #hostel-reservation-modals .form-control {
        min-height: 38px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background-color: #fff;
        color: #111827;
        font-size: 13px;
    }
    .hostel-page .form-control:focus, .hostel-page .form-select:focus,
    #hostel-reservation-modals .form-control:focus { border-color: #1769e8; box-shadow: 0 0 0 3px rgba(23,105,232,.14); }
    .hostel-page .btn, #hostel-reservation-modals .btn { border-radius: 8px; font-size: 13px; font-weight: 600; padding: 8px 14px; transition: background .2s ease, color .2s ease; }
    .hostel-page .btn-primary { background: #1769d1; border-color: #1769d1; color: #fff; }
    .hostel-page .btn-primary:hover { background: #155bbb; border-color: #155bbb; }
    .hostel-page .btn-outline-primary, .hostel-page .btn-outline-secondary,
    .hostel-page .btn-outline-light, .hostel-page .btn-light,
    #hostel-reservation-modals .btn-outline-secondary {
        background: rgba(255,255,255,.35); border: 1px solid #1769e8; color: #1769d1;
    }
    .hostel-page .btn-outline-primary:hover, .hostel-page .btn-outline-secondary:hover,
    .hostel-page .btn-outline-light:hover, .hostel-page .btn-light:hover,
    #hostel-reservation-modals .btn-outline-secondary:hover { background: #1769d1; color: #fff; }
    .hostel-page .status-capsules { display: flex; flex-wrap: wrap; gap: 4px; padding: 4px; width: fit-content; border: 1px solid #dbe5f6; border-radius: 9px; background: rgba(255,255,255,.78); }
    .hostel-page .status-capsules .btn { border: 0; border-radius: 7px; }
    .hostel-page .status-capsules .btn-outline-primary { background: transparent; }
    .hostel-page .status-capsules .btn-outline-primary:hover { background: transparent; color: #0f172a; }
    .hostel-page .status-capsules .btn-primary { box-shadow: 0 3px 9px rgba(23,105,232,.2); }
    .hostel-page .table-responsive { padding: 16px; background: rgba(255,255,255,.82); border: 0 !important; border-radius: 10px !important; box-shadow: 0 8px 25px rgba(0,0,0,.08); }
    .hostel-page .table { font-size: 13px; color: #111827; }
    .hostel-page .table > :not(caption) > * > * { padding: 10px 12px; background: transparent; border-bottom-color: #e5e7eb; }
    .hostel-page .table thead th { background: #f9fafb; color: #374151; font-weight: 800; white-space: nowrap; }
    .hostel-page .table tbody tr:hover { background: #f9fafb; }
    .hostel-page .table .btn { padding: 5px 10px; font-size: 12px; }
    .hostel-page .badge, #hostel-reservation-modals .badge { font-size: 12px; font-weight: 600; }
    .hostel-create .booking-card { padding: 28px; }
    .hostel-create .booking-heading { color: #0f172a; font-size: 18px; font-weight: 800; border-bottom: 1px solid #e5e7eb; padding-bottom: 18px; margin-bottom: 24px; }
    .hostel-create textarea.form-control { min-height: 120px; }
    .hostel-create .features-heading { background: #eef3fa; color: #1769d1; padding: 18px 22px; }
    .hostel-create .feature-list { list-style: none; padding: 0; margin: 0; }
    .hostel-create .feature-list li { display: flex; gap: 12px; align-items: center; margin-bottom: 16px; }
    .hostel-create .feature-list i { color: #1769d1; font-size: 21px; }
    .hostel-create .help-panel { background: rgba(238,243,250,.65); border-top: 1px solid #e5e7eb; padding: 22px; }
    .hostel-create .create-button { min-height: 44px; }
    .hostel-details .card-body { padding: 24px; }
    .hostel-details .card h5 { color: #0f172a; font-size: 18px; font-weight: 800; }
    .hostel-details .card strong { display: block; margin-bottom: 5px; color: #6b7280; font-size: 12px; font-weight: 700; }
    #hostel-reservation-modals .modal-content { border: 1px solid #e5e7eb; border-radius: 12px; background: #fff; box-shadow: 0 18px 50px rgba(15,23,42,.2); color: #111827; overflow: hidden; }
    #hostel-reservation-modals .modal-header { padding: 20px 24px; background: #eef3fa !important; border-bottom: 1px solid #e5e7eb; }
    #hostel-reservation-modals .modal-title { color: #0f172a; font-weight: 800; }
    #hostel-reservation-modals .modal-body { padding: 24px; }
    #hostel-reservation-modals .modal-footer { border-top: 1px solid #e5e7eb; padding: 16px 24px; }
    @media (max-width: 575.98px) {
        .hostel-page { padding: 24px 12px 32px !important; }
        .hostel-create .booking-card, .hostel-details .card-body { padding: 20px; }
        .hostel-details > .d-flex { flex-wrap: wrap; gap: 16px; }
        .hostel-page .status-capsules { width: 100%; }
    }
    @media (prefers-reduced-motion: reduce) {
        .hostel-page .summary-card, .hostel-page .btn { transition: none; }
        .hostel-page .summary-card:hover { transform: none; }
    }
</style>
<div class="hostel-page hostel-create py-4">
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
                                <p class="small text-muted mt-2 mb-0">The system assigns a free room of the selected category and blocks overlapping dates. Check-in {{ config('hostel.check_in_time') }}, check-out {{ config('hostel.check_out_time') }} (midnight at the end of the checkout date).</p>
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
