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

    <div class="row g-4 align-items-start">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body booking-card">
                    <h2 class="h4 booking-heading"><i class="ti ti-bed me-2 text-primary" aria-hidden="true"></i>Booking Details</h2>
                    {{-- Static BRD preview. No submission endpoint is connected. --}}
                    <form id="hostel-booking-form" novalidate onsubmit="return false;">
                        <div id="booking-feedback" class="alert d-none" role="status" aria-live="polite"></div>
                        <div class="row g-4">
                            <div class="col-12">
                                <label for="reservation-name" class="form-label">Reservation Name</label>
                                <input type="text" id="reservation-name" name="reservation_name" class="form-control"
                                       placeholder="e.g. Summer Internship 2026 Group" required>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="check-in-date" class="form-label">Check-in Date</label>
                                <input type="date" id="check-in-date" name="check_in_date" class="form-control" required>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="check-out-date" class="form-label">Check-out Date</label>
                                <input type="date" id="check-out-date" name="check_out_date" class="form-control" required>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="room-category" class="form-label">Room Category</label>
                                <select id="room-category" name="room_category" class="form-select" required>
                                    <option value="single">Single</option>
                                </select>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label for="number-of-guests" class="form-label">Number of Guests</label>
                                <input type="number" id="number-of-guests" name="number_of_guests" class="form-control" min="1" step="1" value="1" required>
                            </div>
                            <div class="col-12">
                                <label for="hostel-location" class="form-label">Hostel Location</label>
                                <select id="hostel-location" name="hostel_location" class="form-select" required>
                                    <option value="nebula-central-residence">Nebula Central Residence</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="special-requirements" class="form-label">Special Requirements</label>
                                <textarea id="special-requirements" name="special_requirements" rows="4" class="form-control"
                                          placeholder="Mention any medical needs, accessibility requirements, or preference for floor level..."></textarea>
                            </div>
                            <div class="col-12 mt-4">
                                <button type="button" id="create-hostel-reservation" class="btn btn-primary create-button w-100" aria-describedby="preview-note">
                                    <i class="ti ti-circle-check-filled me-2" aria-hidden="true"></i>Create Reservation
                                </button>
                                <p id="preview-note" class="small text-muted mt-2 mb-0">Preview only. Room details are sample values from the BRD. This form checks details but does not save bookings or check availability.</p>
                                <noscript><p class="text-danger mt-2">Enable JavaScript to check booking details.</p></noscript>
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

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('hostel-booking-form');
        const name = document.getElementById('reservation-name');
        const checkIn = document.getElementById('check-in-date');
        const checkOut = document.getElementById('check-out-date');
        const feedback = document.getElementById('booking-feedback');
        const inputs = Array.from(form.querySelectorAll('[required]'));

        function validateDates() {
            checkOut.setCustomValidity('');
            if (checkIn.value) checkOut.min = checkIn.value;
            else checkOut.removeAttribute('min');
            if (checkIn.value && checkOut.value && checkOut.value <= checkIn.value) {
                checkOut.setCustomValidity('Check-out must be after check-in.');
            }
        }

        function checkDetails(event) {
            event.preventDefault();
            name.setCustomValidity(name.value.trim() ? '' : 'Enter a reservation name.');
            validateDates();
            let firstInvalid = null;
            inputs.forEach(function (input) {
                const valid = input.checkValidity();
                input.classList.toggle('is-invalid', !valid);
                input.setAttribute('aria-invalid', String(!valid));
                if (!valid && !firstInvalid) firstInvalid = input;
            });
            if (firstInvalid) {
                feedback.className = 'alert alert-danger';
                feedback.textContent = 'Please correct the highlighted booking details.';
                firstInvalid.focus();
                firstInvalid.reportValidity();
                return;
            }
            feedback.className = 'alert alert-info';
            feedback.textContent = 'Booking details are valid. This is a preview: availability has not been checked and no reservation has been saved or submitted for approval.';
        }

        form.addEventListener('submit', checkDetails);
        document.getElementById('create-hostel-reservation').addEventListener('click', checkDetails);
        inputs.forEach(function (input) {
            input.addEventListener('input', function () {
                input.classList.remove('is-invalid');
                input.removeAttribute('aria-invalid');
                input.setCustomValidity('');
                feedback.classList.add('d-none');
                feedback.textContent = '';
                validateDates();
            });
        });
    });
</script>
@endsection
