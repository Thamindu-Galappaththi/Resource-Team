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
        @php
            $selectedMealType = collect($mealTypes)->first(fn ($type) => $type->value === old('meal_type', $reservation?->meal_type));
            $selectedLocation = $locations->firstWhere('id', (int) old('location_id', $reservation?->location_id));
        @endphp
        <div class="col-md-6">
            <label class="form-label" for="meal_type">Meal type <span class="text-danger">*</span></label>
            <x-rm-select id="meal_type" name="meal_type" class="{{ $errors->has('meal_type') ? 'is-invalid' : '' }}" placeholder="Select meal type" :value="(string) old('meal_type', $reservation?->meal_type)" :selected-label="$selectedMealType?->label() ?? 'Select meal type'" :required="true"
                :options="collect([['value' => '', 'label' => 'Select meal type']])->concat(collect($mealTypes)->map(fn ($type) => ['value' => $type->value, 'label' => $type->label()]))->all()" />
            @error('meal_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label" for="location_id">Location</label>
            <x-rm-select id="location_id" name="location_id" class="{{ $errors->has('location_id') ? 'is-invalid' : '' }}" placeholder="Select location (optional)" :value="(string) old('location_id', $reservation?->location_id)" :selected-label="$selectedLocation?->name ?? 'Select location (optional)'"
                :options="collect([['value' => '', 'label' => 'Select location (optional)']])->concat($locations->map(fn ($location) => ['value' => (string) $location->id, 'label' => $location->name]))->all()" />
            @error('location_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
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
            <div class="form-text">Every reservation starts pending and requires canteen review.</div>
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
