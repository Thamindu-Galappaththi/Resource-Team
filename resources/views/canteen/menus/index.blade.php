@extends('layouts.app')

@section('title', 'Meal Plans')

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
            <h1 class="cd-title">Meal Plans</h1>
            <p class="cd-subtitle">Publish a menu name and photo. Reservations can then pick that menu and see the image.</p>
        </div>
    </header>

    @if(session('success'))<div class="alert alert-success cp-auto-alert" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger cp-auto-alert" role="alert">Please check the highlighted menu details and try again.</div>@endif

    <div class="mm-layout">
        <section class="mm-card">
            <div class="mm-card-head">
                <h2>Add a meal menu</h2>
                <p class="mm-muted">Only a name and a photo are needed.</p>
            </div>
            <form class="mm-form" method="POST" action="{{ route('canteen.menus.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="name" class="form-label">Menu name <span class="text-danger">*</span></label>
                    <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Rice and curry" maxlength="120" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="image" class="form-label">Menu image <span class="text-danger">*</span></label>
                    <div class="mm-file">
                        <input id="image" class="mm-file-input" type="file" name="image" accept="image/jpeg,image/png,image/webp" required>
                        <label for="image" class="mm-file-btn">Choose file</label>
                        <span class="mm-file-name" id="mm-file-name">No file chosen</span>
                    </div>
                    <div class="mm-preview-frame" id="mm-live-preview"><img alt="Selected menu preview"></div>
                    <div class="form-text">JPG, PNG, or WebP, up to 5 MB.</div>
                    @error('image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    <div class="mm-samples">
                        <strong>Sample photos ready to upload</strong>
                        <span class="mm-muted">Use these from <code>public/images/sample-menus</code>: rice-and-curry.jpg, string-hoppers.jpg, short-eats.jpg, fried-rice.jpg</span>
                    </div>
                </div>
                <button type="submit" class="cd-btn cd-btn--solid w-100 justify-content-center"><i class="ti ti-plus" aria-hidden="true"></i> Save meal menu</button>
            </form>
        </section>

        <section class="mm-card">
            <div class="mm-card-head d-flex justify-content-between align-items-start gap-2">
                <div>
                    <h2>Published menus</h2>
                    <p class="mm-muted mb-0">{{ $menus->total() }} {{ \Illuminate\Support\Str::plural('menu', $menus->total()) }}</p>
                </div>
            </div>
            @if($menus->isEmpty())
                <div class="text-center py-5 px-3">
                    <i class="ti ti-photo fs-1 text-muted" aria-hidden="true"></i>
                    <h3 class="h6 mt-3">No menus yet</h3>
                    <p class="mm-muted mb-0">Add a name and image to publish the first menu.</p>
                </div>
            @else
                <div class="mm-gallery">
                    @foreach($menus as $menu)
                        <article class="mm-tile">
                            @if($menu->image_path)
                                <img src="{{ $menu->imageUrl() }}" alt="{{ $menu->name }}">
                            @else
                                <div class="mm-tile-placeholder"><i class="ti ti-photo" aria-hidden="true"></i></div>
                            @endif
                            <div class="mm-tile-body">
                                <h3 class="text-break">{{ $menu->name }}</h3>
                                <div class="mm-tile-actions">
                                    <a href="{{ route('canteen.menus.edit', $menu) }}" class="btn btn-sm btn-outline-primary"><i class="ti ti-pencil me-1"></i>Edit</a>
                                    <form method="POST" action="{{ route('canteen.menus.destroy', $menu) }}" onsubmit="return confirm('Remove this menu?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="Remove {{ $menu->name }}"><i class="ti ti-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
                @if($menus->hasPages())
                    <div class="px-3 pb-3">{{ $menus->links() }}</div>
                @endif
            @endif
        </section>
    </div>
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
