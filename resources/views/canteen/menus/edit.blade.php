@extends('layouts.app')

@section('title', 'Edit Meal Plan')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/canteen-dashboard.css') }}?v={{ @filemtime(public_path('css/canteen-dashboard.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('css/canteen-pages.css') }}?v={{ @filemtime(public_path('css/canteen-pages.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('css/meal-menus.css') }}?v=4">
@endpush

@section('content')
<div class="container-fluid py-4 cp-page mm-page">
    <header class="cd-header">
        <div>
            <div class="cd-eyebrow"><i class="ti ti-tools-kitchen-2" aria-hidden="true"></i> Canteen operations</div>
            <h1 class="cd-title">Edit Meal Plan</h1>
            <p class="cd-subtitle">Change the name or replace the photo for this menu.</p>
        </div>
        <div class="cd-actions">
            <a href="{{ route('canteen.menus.index') }}" class="cd-btn cd-btn--outline"><i class="ti ti-arrow-left" aria-hidden="true"></i> Back to Meal Plans</a>
        </div>
    </header>

    @if($errors->any())<div class="alert alert-danger cp-auto-alert" role="alert">Please check the highlighted details and try again.</div>@endif

    <form class="mm-section mm-edit-wrap mm-edit" method="POST" action="{{ route('canteen.menus.update', $menu) }}" enctype="multipart/form-data">
        @csrf @method('PUT')
        <div>
            <span class="form-label d-block">Current photo</span>
            <div class="mm-edit-photo">
                @if($menu->image_path)
                    <img src="{{ $menu->imageUrl() }}" alt="{{ $menu->name }}">
                @endif
            </div>
        </div>
        <div>
            <div class="mb-3">
                <label for="name" class="form-label">Menu name <span class="text-danger">*</span></label>
                <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $menu->name) }}" maxlength="120" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <span class="form-label d-block">Replace photo</span>
                <label class="mm-drop" data-mm-drop>
                    <input id="image" class="mm-file-input" type="file" name="image" accept="image/jpeg,image/png,image/webp">
                    <img alt="New menu preview">
                    <span class="mm-drop-copy">
                        <strong>Drop a new photo</strong>
                        <span>Leave empty to keep the current image</span>
                    </span>
                </label>
                @error('image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="mm-form-actions">
                <a href="{{ route('canteen.menus.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Save changes</button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/canteen-ui.js') }}?v=5"></script>
<script src="{{ asset('js/meal-menus.js') }}?v=2"></script>
@endpush
