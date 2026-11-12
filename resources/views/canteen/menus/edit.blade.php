@extends('layouts.app')

@section('title', 'Edit Meal Plan')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/canteen-dashboard.css') }}?v={{ @filemtime(public_path('css/canteen-dashboard.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('css/canteen-pages.css') }}?v={{ @filemtime(public_path('css/canteen-pages.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('css/meal-menus.css') }}?v=2">
@endpush

@section('content')
<div class="container-fluid py-4 cp-page">
    <header class="cd-header">
        <div>
            <div class="cd-eyebrow"><i class="ti ti-tools-kitchen-2" aria-hidden="true"></i> Canteen operations</div>
            <h1 class="cd-title">Edit Meal Plan</h1>
            <p class="cd-subtitle">Update the menu name or replace the photo.</p>
        </div>
        <div class="cd-actions">
            <a href="{{ route('canteen.menus.index') }}" class="cd-btn cd-btn--outline"><i class="ti ti-arrow-left" aria-hidden="true"></i> Back to Meal Plans</a>
        </div>
    </header>

    @if($errors->any())<div class="alert alert-danger cp-auto-alert" role="alert">Please check the highlighted details and try again.</div>@endif

    <section class="mm-card mm-edit">
        @if($menu->image_path)
            <img class="w-100 rounded-3 mb-3" style="max-height:260px;object-fit:cover" src="{{ $menu->imageUrl() }}" alt="{{ $menu->name }}">
        @endif
        <form method="POST" action="{{ route('canteen.menus.update', $menu) }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            <div class="mb-3">
                <label for="name" class="form-label">Menu name <span class="text-danger">*</span></label>
                <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $menu->name) }}" maxlength="120" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="image" class="form-label">Replace menu image</label>
                <div class="mm-file">
                    <input id="image" class="mm-file-input" type="file" name="image" accept="image/jpeg,image/png,image/webp">
                    <label for="image" class="mm-file-btn">Choose file</label>
                    <span class="mm-file-name" id="mm-file-name">No file chosen</span>
                </div>
                <div class="mm-preview-frame" id="mm-live-preview"><img alt="Selected menu preview"></div>
                <div class="form-text">Leave empty to keep the current image.</div>
                @error('image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="mm-form-actions border-top pt-3">
                <a href="{{ route('canteen.menus.index') }}" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy me-1"></i>Save changes</button>
            </div>
        </form>
    </section>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('image')?.addEventListener('change', (event) => {
        const file = event.target.files?.[0];
        const name = document.getElementById('mm-file-name');
        const frame = document.getElementById('mm-live-preview');
        if (name) name.textContent = file ? file.name : 'No file chosen';
        if (!frame) return;
        const img = frame.querySelector('img');
        if (!file) { frame.classList.remove('is-visible'); return; }
        img.src = URL.createObjectURL(file);
        frame.classList.add('is-visible');
    });
</script>
<script src="{{ asset('js/canteen-ui.js') }}?v=5"></script>
@endpush
