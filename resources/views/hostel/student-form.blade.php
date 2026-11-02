@extends('layouts.public')

@section('title', 'Hostel Reservation Request')

@section('content')
@php
    $field = fn (string $name) => $errors->has($name) ? 'form-control is-invalid' : 'form-control';
@endphp
<div class="pub-card">
    <section class="pub-hero" aria-labelledby="pub-hero-title">
        <div class="pub-hero-art" aria-hidden="true">
            <span class="pub-hero-ring"></span>
            <i class="ti ti-school"></i>
        </div>
        <div class="pub-hero-copy">
            <span class="pub-hero-eyebrow">For Nebula students</span>
            <h1 id="pub-hero-title">Need a place to stay? <span>Request your hostel room.</span></h1>
            <p>Takes about 2 minutes. No login needed. We send the decision straight to your phone.</p>
        </div>
    </section>
    <ol class="pub-steps" aria-label="How it works">
        <li><span>1</span><div><strong>Fill the form</strong><small>Your details and stay dates</small></div></li>
        <li><span>2</span><div><strong>Manager reviews</strong><small>Hostel office checks rooms</small></div></li>
        <li><span>3</span><div><strong>Get an SMS</strong><small>Approved, rejected, or cancelled</small></div></li>
    </ol>
    <div class="pub-card-body">
        @if($errors->any())
            <div class="alert alert-danger" role="alert">Please correct the highlighted fields and submit again.</div>
        @endif

        <form method="POST" action="{{ route('hostel.student-form.submit', $token) }}" id="student-hostel-form" autocomplete="on">
            @csrf
            <div class="pub-honeypot" aria-hidden="true">
                <label for="website">Website</label>
                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
            </div>

            <h2 class="pub-section">Student details</h2>
            <div class="row g-3">
                <div class="col-12 col-md-7">
                    <label for="student_name" class="form-label">Student name <span class="text-danger">*</span></label>
                    <input type="text" id="student_name" name="student_name" class="{{ $field('student_name') }}" value="{{ old('student_name') }}" maxlength="150" autocomplete="name" placeholder="e.g. Kasun Madushanka" required>
                    @error('student_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-5">
                    <label for="student_id" class="form-label">Student ID <span class="text-danger">*</span></label>
                    <input type="text" id="student_id" name="student_id" class="{{ $field('student_id') }}" value="{{ old('student_id') }}" maxlength="50" autocomplete="off" placeholder="e.g. NB/IT/2026/045" required>
                    @error('student_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-6">
                    <label for="home_town" class="form-label">Home town <span class="text-danger">*</span></label>
                    <input type="text" id="home_town" name="home_town" class="{{ $field('home_town') }}" value="{{ old('home_town') }}" maxlength="120" autocomplete="address-level2" placeholder="e.g. Kurunegala" required>
                    @error('home_town')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-6">
                    <label for="phone" class="form-label">Phone number <span class="text-danger">*</span></label>
                    <input type="tel" id="phone" name="phone" class="{{ $field('phone') }}" value="{{ old('phone') }}" maxlength="20" autocomplete="tel" placeholder="e.g. 077 123 4567" required>
                    <p class="pub-hint">The decision SMS will be sent to this number.</p>
                    @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
            </div>

            <h2 class="pub-section">Stay dates</h2>
            <div class="row g-3">
                <div class="col-12 col-sm-6">
                    <label for="check_in_date" class="form-label">Check-in date <span class="text-danger">*</span></label>
                    <input type="date" id="check_in_date" name="check_in_date" class="{{ $field('check_in_date') }}" value="{{ old('check_in_date') }}" min="{{ $minDate }}" required>
                    @error('check_in_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-sm-6">
                    <label for="check_in_time" class="form-label">Check-in time <span class="text-danger">*</span></label>
                    <input type="time" id="check_in_time" name="check_in_time" class="{{ $field('check_in_time') }}" value="{{ old('check_in_time') }}" required>
                    @error('check_in_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-sm-6">
                    <label for="check_out_date" class="form-label">Check-out date <span class="text-danger">*</span></label>
                    <input type="date" id="check_out_date" name="check_out_date" class="{{ $field('check_out_date') }}" value="{{ old('check_out_date') }}" min="{{ $minDate }}" required>
                    @error('check_out_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-sm-6">
                    <label for="check_out_time" class="form-label">Check-out time <span class="text-danger">*</span></label>
                    <input type="time" id="check_out_time" name="check_out_time" class="{{ $field('check_out_time') }}" value="{{ old('check_out_time') }}" required>
                    @error('check_out_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <h2 class="pub-section">Emergency contact</h2>
            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label for="emergency_contact_name" class="form-label">Contact name <span class="text-danger">*</span></label>
                    <input type="text" id="emergency_contact_name" name="emergency_contact_name" class="{{ $field('emergency_contact_name') }}" value="{{ old('emergency_contact_name') }}" maxlength="150" placeholder="e.g. Sunil Madushanka (Father)" required>
                    @error('emergency_contact_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-6">
                    <label for="emergency_contact_phone" class="form-label">Contact phone number <span class="text-danger">*</span></label>
                    <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" class="{{ $field('emergency_contact_phone') }}" value="{{ old('emergency_contact_phone') }}" maxlength="20" placeholder="e.g. 071 765 4321" required>
                    @error('emergency_contact_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="pub-actions">
                <p>Questions? Call the logistics desk at <a href="tel:+94112954069">011 2954 069</a>.</p>
                <button type="submit" class="pub-submit"><i class="ti ti-send me-1"></i>Submit request</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/hostel-student-form.js') }}?v=1"></script>
@endpush
