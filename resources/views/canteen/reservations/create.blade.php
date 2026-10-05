@extends('layouts.app')

@section('title', 'Create Canteen Reservation')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
        <div>
            <div class="small text-uppercase fw-semibold text-primary mb-1">Canteen operations</div>
            <h2 class="mb-1">New reservation</h2>
            <p class="text-muted mb-0">Enter the service details and expected order count.</p>
        </div>
        <a href="{{ route('canteen.reservations.index') }}" class="btn btn-outline-secondary"><i class="ti ti-arrow-left me-1"></i>All reservations</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger" role="alert"><strong>Please check the form.</strong> Some details need your attention.</div>
    @endif
        <div class="card-body">
            <form method="POST" action="{{ route('canteen.store') }}">
                @csrf

    <form method="POST" action="{{ route('canteen.reservations.store') }}">
        @csrf
        <section class="border-top border-bottom py-4">
            <div class="row g-3">
                <div class="col-12"><h5 class="mb-0">Reservation details</h5></div>
                <div class="col-md-6">
                    <label class="form-label" for="reservation_name">Reservation name <span class="text-danger">*</span></label>
                    <input id="reservation_name" type="text" maxlength="255" class="form-control @error('reservation_name') is-invalid @enderror" name="reservation_name" value="{{ old('reservation_name') }}" placeholder="e.g. Engineering team lunch" required autofocus>
                    @error('reservation_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="meal_type">Meal type <span class="text-danger">*</span></label>
                    <select id="meal_type" class="form-select @error('meal_type') is-invalid @enderror" name="meal_type" required>
                        <option value="">Select meal type</option>
                        @foreach($mealTypes as $mealType)
                            <option value="{{ $mealType }}" {{ old('meal_type') === $mealType ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $mealType)) }}</option>
                        @endforeach
                    </select>
                    @error('meal_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="location_id">Location</label>
                    <select id="location_id" class="form-select @error('location_id') is-invalid @enderror" name="location_id">
                        <option value="">Select location (optional)</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" {{ (string) old('location_id') === (string) $location->id ? 'selected' : '' }}>{{ $location->name }}</option>
                        @endforeach
                    </select>
                    @error('location_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="reservation_date">Service date <span class="text-danger">*</span></label>
                    <input id="reservation_date" type="date" class="form-control @error('reservation_date') is-invalid @enderror" name="reservation_date" value="{{ old('reservation_date', now()->addDay()->toDateString()) }}" min="{{ now()->toDateString() }}" required>
                    @error('reservation_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="reservation_time">Service time <span class="text-danger">*</span></label>
                    <input id="reservation_time" type="time" class="form-control @error('reservation_time') is-invalid @enderror" name="reservation_time" value="{{ old('reservation_time') }}" required>
                    @error('reservation_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        <section class="border-bottom py-4">
            <div class="row g-3">
                <div class="col-12"><h5 class="mb-0">Order requirements</h5></div>
                <div class="col-md-4">
                    <label class="form-label" for="number_of_orders">Number of orders <span class="text-danger">*</span></label>
                    <input id="number_of_orders" type="number" min="1" max="10000" class="form-control @error('number_of_orders') is-invalid @enderror" name="number_of_orders" value="{{ old('number_of_orders') }}" placeholder="0" required>
                    <div class="form-text">Orders above {{ $largeGroupThreshold }} require review.</div>
                    @error('number_of_orders')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-8">
                    <label class="form-label" for="order_details">Order details</label>
                    <textarea id="order_details" maxlength="1000" class="form-control @error('order_details') is-invalid @enderror" name="order_details" rows="3" placeholder="Menu, dietary requirements, or serving notes">{{ old('order_details') }}</textarea>
                    @error('order_details')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label" for="special_remarks">Special remarks</label>
                    <textarea id="special_remarks" maxlength="1000" class="form-control @error('special_remarks') is-invalid @enderror" name="special_remarks" rows="2" placeholder="Anything the canteen team should know">{{ old('special_remarks') }}</textarea>
                    @error('special_remarks')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </section>

        <div class="d-flex flex-column-reverse flex-sm-row justify-content-end gap-2 py-4">
            <a href="{{ route('canteen.reservations.index') }}" class="btn btn-outline-secondary">Discard</a>
            <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i>Submit reservation</button>
        </div>
    </form>
</div>
@endsection
