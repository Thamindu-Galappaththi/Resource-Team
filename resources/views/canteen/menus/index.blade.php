@extends('layouts.app')

@section('title', 'Meal Plans')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/canteen-dashboard.css') }}?v={{ @filemtime(public_path('css/canteen-dashboard.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('css/canteen-pages.css') }}?v={{ @filemtime(public_path('css/canteen-pages.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('css/meal-menus.css') }}?v=4">
@endpush

@section('content')
@php
    $samples = [
        ['file' => 'rice-and-curry.jpg', 'label' => 'Rice and curry'],
        ['file' => 'string-hoppers.jpg', 'label' => 'String hoppers'],
        ['file' => 'short-eats.jpg', 'label' => 'Short eats'],
        ['file' => 'fried-rice.jpg', 'label' => 'Fried rice'],
    ];
@endphp
<div class="container-fluid py-4 cp-page mm-page">
    <header class="cd-header">
        <div>
            <div class="cd-eyebrow"><i class="ti ti-tools-kitchen-2" aria-hidden="true"></i> Canteen operations</div>
            <h1 class="cd-title">Meal Plans</h1>
            <p class="cd-subtitle">Add a menu photo and name. People pick it when they create a canteen reservation.</p>
        </div>
    </header>

    <div id="cp-flash" class="alert cp-auto-alert d-none" role="status"></div>
    @if(session('success'))<div class="alert alert-success cp-auto-alert" role="status">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger cp-auto-alert" role="alert">Please check the menu name and photo, then try again.</div>@endif

    <form id="mm-add-form" class="mm-add" method="POST" action="{{ route('canteen.menus.store') }}" enctype="multipart/form-data" data-action-lock="off">
        @csrf
        <div>
            <h2>Add a meal menu</h2>
            <p class="mm-muted mb-3">Give it a clear name, then drop a photo or tap a sample.</p>
            <div class="mb-3">
                <label for="name" class="form-label">Menu name <span class="text-danger">*</span></label>
                <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="e.g. Rice and curry" maxlength="120" required>
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <button type="submit" id="mm-publish" class="cd-btn cd-btn--solid w-100 justify-content-center"><i class="ti ti-plus" aria-hidden="true"></i> Publish menu</button>
        </div>
        <div>
            <span class="form-label d-block">Menu image <span class="text-danger">*</span></span>
            <label class="mm-drop" data-mm-drop>
                <input id="image" class="mm-file-input" type="file" name="image" accept="image/jpeg,image/png,image/webp" required>
                <img alt="Selected menu preview">
                <span class="mm-drop-copy">
                    <strong>Drop a photo here</strong>
                    <span>or click to browse · JPG, PNG, WebP · max 5 MB</span>
                </span>
            </label>
            <div class="mm-samples" aria-label="Sample menu photos">
                @foreach($samples as $sample)
                    <button type="button" class="mm-sample" data-mm-sample="{{ asset('images/sample-menus/'.$sample['file']) }}" data-mm-name="{{ $sample['file'] }}" title="Use {{ $sample['label'] }}">
                        <img src="{{ asset('images/sample-menus/'.$sample['file']) }}" alt="{{ $sample['label'] }}">
                    </button>
                @endforeach
            </div>
            @error('image')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
    </form>

    <section class="mm-section">
        <div class="mm-section-head">
            <div>
                <h2>Published menus</h2>
                <p class="mm-muted mb-0" id="mm-count">{{ $menus->total() }} {{ \Illuminate\Support\Str::plural('menu', $menus->total()) }} available for reservations</p>
            </div>
        </div>
        <div class="mm-empty{{ $menus->isEmpty() ? '' : ' d-none' }}" id="mm-empty">
            <i class="ti ti-photo fs-1 text-muted" aria-hidden="true"></i>
            <h3 class="h6 mt-3">No menus yet</h3>
            <p class="mm-muted mb-0">Publish the first menu with a name and photo above.</p>
        </div>
        <div class="mm-gallery{{ $menus->isEmpty() ? ' d-none' : '' }}" id="mm-gallery">
            @foreach($menus as $menu)
                @include('canteen.menus.partials.tile', ['menu' => $menu])
            @endforeach
        </div>
        @if($menus->hasPages())
            <div class="px-3 pb-3">{{ $menus->links() }}</div>
        @endif
    </section>
</div>
@include('canteen.partials.confirm-modal')
@endsection

@push('scripts')
<script src="{{ asset('js/canteen-ui.js') }}?v=5"></script>
<script src="{{ asset('js/meal-menus.js') }}?v=2"></script>
@endpush
