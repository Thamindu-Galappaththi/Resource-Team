@extends('layouts.app')

@section('title', $title)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/slt-dashboard.css') }}?v={{ @filemtime(public_path('css/slt-dashboard.css')) ?: time() }}">
@endpush

@section('content')
<div class="slt-hub slt-hub--requests slt-hub--{{ $kind }}">
    <div class="slt-hub__sky" aria-hidden="true">
        <div class="slt-hub__photo"></div>
        <div class="slt-hub__wash"></div>
        <canvas id="slt-hub-stars" class="slt-hub__stars"></canvas>
    </div>

    <div class="slt-hub__inner">
        <header class="slt-hub__hero slt-hub__hero--compact">
            <a class="slt-back" href="{{ $kind === 'hostel' ? route('hostel.home') : ($kind === 'canteen' ? route('canteen.home') : route('reservations.home')) }}">
                <i class="ti ti-arrow-left"></i>
                Back to {{ $kind }} dashboard
            </a>
            <h1>{{ $title }}</h1>
            <p class="slt-hub__sub">{{ $subtitle }}</p>
            <a class="slt-action slt-action--solid slt-action--inline" href="{{ $createUrl }}">
                <i class="ti ti-plus"></i>
                <span>{{ $createLabel }}</span>
            </a>
        </header>

        <section class="slt-requests" aria-label="{{ $title }}">
            @include('dashboards.partials.slt-requests-table')
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/slt-dashboard.js') }}?v={{ @filemtime(public_path('js/slt-dashboard.js')) ?: time() }}"></script>
@endpush
