@php
    $reservation = $reservation ?? null;
    $isEdit = $reservation !== null;
@endphp
@csrf
@if($isEdit)
    @method('PUT')
@endif

<section class="mb-4">
    <h2 class="h5 mb-3">Reservation details</h2>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="reservation_name">Reservation name <span class="text-danger">*</span></label>
            <input id="reservation_name" type="text" maxlength="255" class="form-control @error('reservation_name') is-invalid @enderror" name="reservation_name" value="{{ old('reservation_name', $reservation?->reservation_name) }}" placeholder="e.g. Engineering team lunch" required autofocus>
            @error('reservation_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label" for="meal_type">Meal type <span class="text-danger">*</span></label>
            <select id="meal_type" class="form-select @error('meal_type') is-invalid @enderror" name="meal_type" required>
                <option value="">Select meal type</option>
                @foreach($mealTypes as $mealType)
                    <option value="{{ $mealType->value }}" @selected(old('meal_type', $reservation?->meal_type) === $mealType->value)>{{ $mealType->label() }}</option>
                @endforeach
            </select>
            @error('meal_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label" for="location_id">Location</label>
            <select id="location_id" class="form-select @error('location_id') is-invalid @enderror" name="location_id">
                <option value="">Select location (optional)</option>
                @foreach($locations as $location)
                    <option value="{{ $location->id }}" @selected((string) old('location_id', $reservation?->location_id) === (string) $location->id)>{{ $location->name }}</option>
                @endforeach
            </select>
            @error('location_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label" for="reservation_date">Service date <span class="text-danger">*</span></label>
            <input id="reservation_date" type="date" class="form-control @error('reservation_date') is-invalid @enderror" name="reservation_date" value="{{ old('reservation_date', $reservation?->reservation_date?->toDateString() ?? now()->addDay()->toDateString()) }}" min="{{ now()->toDateString() }}" required>
            @error('reservation_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label" for="reservation_time">Service time <span class="text-danger">*</span></label>
            <input id="reservation_time" type="time" class="form-control @error('reservation_time') is-invalid @enderror" name="reservation_time" value="{{ old('reservation_time', $reservation?->serviceTimeInput()) }}" required>
            @error('reservation_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</section>

<section>
    <h2 class="h5 mb-3">Order requirements</h2>
    <div class="row g-3">
        <div class="col-md-4">
            <label class="form-label" for="number_of_orders">Number of orders <span class="text-danger">*</span></label>
            <input id="number_of_orders" type="number" min="1" max="10000" class="form-control @error('number_of_orders') is-invalid @enderror" name="number_of_orders" value="{{ old('number_of_orders', $reservation?->number_of_orders) }}" placeholder="0" required>
            <div class="form-text">Orders above {{ $largeGroupThreshold }} require canteen review.</div>
            @error('number_of_orders')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-8">
            <label class="form-label" for="order_details">Order details</label>
            <textarea id="order_details" maxlength="1000" class="form-control @error('order_details') is-invalid @enderror" name="order_details" rows="3" placeholder="Menu, dietary requirements, or serving notes">{{ old('order_details', $reservation?->order_details) }}</textarea>
            @error('order_details')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <label class="form-label" for="special_remarks">Special remarks</label>
            <textarea id="special_remarks" maxlength="1000" class="form-control @error('special_remarks') is-invalid @enderror" name="special_remarks" rows="2" placeholder="Anything the canteen team should know">{{ old('special_remarks', $reservation?->special_remarks) }}</textarea>
            @error('special_remarks')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</section>
