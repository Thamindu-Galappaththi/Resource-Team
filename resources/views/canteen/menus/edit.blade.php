@extends('layouts.app')

@section('title', 'Edit meal plan')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/canteen-dashboard.css') }}?v={{ @filemtime(public_path('css/canteen-dashboard.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('css/canteen-pages.css') }}?v={{ @filemtime(public_path('css/canteen-pages.css')) ?: time() }}">
<style>
    .mm-edit{max-width:900px;margin:auto;background:#fff;border:1px solid #e8edf3;border-radius:16px;padding:1.5rem;box-shadow:0 4px 18px rgba(31,45,61,.04)}
    .mm-edit-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}.mm-edit-grid .wide{grid-column:1/-1}.mm-edit-grid label{font-weight:600}
    .mm-current-image{width:100%;max-height:300px;object-fit:cover;border-radius:12px;margin-bottom:1rem}
    @media(max-width:600px){.mm-edit-grid{grid-template-columns:1fr}.mm-edit-grid .wide{grid-column:auto}}
</style>
@endpush

@section('content')
<div class="container-fluid py-4 cp-page">
    <header class="cd-header"><div><div class="cd-eyebrow"><i class="ti ti-tools-kitchen-2" aria-hidden="true"></i> Canteen operations</div><h1 class="cd-title">Edit meal plan</h1><p class="cd-subtitle">Update the service date, dishes, or meal image.</p></div><div class="cd-actions"><a href="{{ route('canteen.menus.index') }}" class="cd-btn cd-btn--outline"><i class="ti ti-arrow-left" aria-hidden="true"></i>Back to meal plans</a></div></header>
    @if($errors->any())<div class="alert alert-danger" role="alert">Please check the highlighted details and try again.</div>@endif
    <section class="mm-edit">
        @if($menu->image_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($menu->image_path))<img class="mm-current-image" src="{{ url('storage/'.$menu->image_path) }}" alt="Current image for {{ $menu->name }}">@endif
        <form method="POST" action="{{ route('canteen.menus.update', $menu) }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="mm-edit-grid">
                <div class="wide"><label for="name" class="form-label">Menu name</label><input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $menu->name) }}" maxlength="120" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div><label for="service_date" class="form-label">Service date</label><input id="service_date" type="date" name="service_date" class="form-control @error('service_date') is-invalid @enderror" value="{{ old('service_date', $menu->service_date->toDateString()) }}" required>@error('service_date')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div><label for="main_dish" class="form-label">Main dish</label><input id="main_dish" name="main_dish" class="form-control @error('main_dish') is-invalid @enderror" value="{{ old('main_dish', $menu->main_dish) }}" maxlength="120" required>@error('main_dish')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                @foreach(['dish_one' => 'Dish 1', 'dish_two' => 'Dish 2', 'dish_three' => 'Dish 3', 'dish_four' => 'Dish 4'] as $field => $label)
                    <div><label for="{{ $field }}" class="form-label">{{ $label }}</label><input id="{{ $field }}" name="{{ $field }}" class="form-control @error($field) is-invalid @enderror" value="{{ old($field, $menu->{$field}) }}" maxlength="120" required>@error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                @endforeach
                <div class="wide"><label for="image" class="form-label">Replace meal image <span class="text-muted fw-normal">(optional)</span></label><input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp" class="form-control @error('image') is-invalid @enderror"><div class="form-text">JPG, PNG, or WebP, up to 5 MB. Leave empty to keep the current image.</div>@error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            </div>
            <div class="d-flex justify-content-end gap-2 border-top mt-4 pt-3"><a href="{{ route('canteen.menus.index') }}" class="btn btn-outline-secondary">Cancel</a><button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Save changes</button></div>
        </form>
    </section>
</div>
@endsection
