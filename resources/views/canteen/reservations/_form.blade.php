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
            <input id="reservation_name" type="text" maxlength="255" class="form-control @error('reservation_name') is-invalid @enderror" name="reservation_name" value="{{ old('reservation_name', $reservation?->reservation_name) }}" placeholder="e.g. Engineering team lunch" required>
            @error('reservation_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        @php
            $selectedMealMenu = collect($mealMenus)->first(fn ($menu) => $menu->name === old('meal_type', $reservation?->meal_type));
            $selectedLocation = $locations->firstWhere('id', (int) old('location_id', $reservation?->location_id));
        @endphp
        <div class="col-md-6">
            <label class="form-label" for="meal_type">Meal type <span class="text-danger">*</span></label>
            <x-rm-select id="meal_type" name="meal_type" class="{{ $errors->has('meal_type') ? 'is-invalid' : '' }}" placeholder="Select meal menu" :value="(string) old('meal_type', $reservation?->meal_type)" :selected-label="$selectedMealMenu?->name ?? 'Select meal menu'" :required="true"
                :options="collect([['value' => '', 'label' => 'Select meal menu']])->concat(collect($mealMenus)->map(fn ($menu) => ['value' => $menu->name, 'label' => $menu->name]))->all()" />
            @if($mealMenus->isEmpty())<div class="form-text">No meal menus are available yet. Create a menu first.</div>@endif
            @error('meal_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-12">
            <section id="mealMenuImagePreview" class="p-3 border rounded-3 bg-light text-center" aria-label="Selected meal image" @if(!$selectedMealMenu?->image_path) hidden @endif>
                <img id="mealMenuImage" src="{{ $selectedMealMenu?->image_path ? url('storage/'.$selectedMealMenu->image_path) : '' }}" alt="{{ $selectedMealMenu ? 'Meal image for '.$selectedMealMenu->name : '' }}" class="img-fluid rounded-3" style="display:block;max-width:min(100%, 480px);width:auto;height:auto;max-height:240px;object-fit:contain;margin:0 auto;">
            </section>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="location_id">Location</label>
            <x-rm-select id="location_id" name="location_id" class="{{ $errors->has('location_id') ? 'is-invalid' : '' }}" placeholder="Select location (optional)" :value="(string) old('location_id', $reservation?->location_id)" :selected-label="$selectedLocation?->name ?? 'Select location (optional)'"
                :options="collect([['value' => '', 'label' => 'Select location (optional)']])->concat($locations->map(fn ($location) => ['value' => (string) $location->id, 'label' => $location->name]))->all()" />
            @error('location_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label" for="reservation_date">Service date <span class="text-danger">*</span></label>
            <input id="reservation_date" type="date" class="form-control @error('reservation_date') is-invalid @enderror" name="reservation_date" value="{{ old('reservation_date', $reservation?->reservation_date?->toDateString() ?? $prefillDate ?? now()->addDay()->toDateString()) }}" min="{{ now()->toDateString() }}" required>
            @error('reservation_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
            <label class="form-label" for="reservation_time">Service time <span class="text-danger">*</span></label>
            <input id="reservation_time" type="time" class="form-control @error('reservation_time') is-invalid @enderror" name="reservation_time" value="{{ old('reservation_time', $reservation?->serviceTimeInput()) }}" required>
            @error('reservation_time')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</section>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const mealInput = document.getElementById('meal_type');
        const preview = document.getElementById('mealMenuImagePreview');
        const image = document.getElementById('mealMenuImage');
        const menus = @json($mealMenus->mapWithKeys(fn ($menu) => [$menu->name => [
            'url' => $menu->image_path ? url('storage/'.$menu->image_path) : null,
            'name' => $menu->name,
        ]])->all());

        const updateMealImage = () => {
            const menu = menus[mealInput?.value];
            if (!menu?.url) {
                preview.hidden = true;
                image.removeAttribute('src');
                return;
            }
            image.src = menu.url;
            image.alt = `Meal image for ${menu.name}`;
            preview.hidden = false;
        };

        mealInput?.addEventListener('change', updateMealImage);
        updateMealImage();
    });
</script>
@endpush

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
