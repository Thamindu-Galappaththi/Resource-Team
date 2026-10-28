@extends('layouts.app')

@section('title', $title)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/slt-dashboard.css') }}?v={{ @filemtime(public_path('css/slt-dashboard.css')) ?: time() }}">
@endpush

@section('content')
<div class="slt-hub slt-hub--module slt-hub--{{ $kind }}">
    <div class="slt-hub__sky" aria-hidden="true">
        <div class="slt-hub__photo" style="background-image: url('{{ $image }}')"></div>
        <div class="slt-hub__wash"></div>
        <canvas id="slt-hub-stars" class="slt-hub__stars"></canvas>
    </div>

    <div class="slt-hub__inner">
        <header class="slt-hub__hero slt-hub__hero--compact">
            <a class="slt-back" href="{{ route('dashboard') }}">
                <i class="ti ti-arrow-left"></i>
                All reservation types
            </a>
            <p class="slt-hub__badge">{{ $as_of }}</p>
            <h1>{{ $title }}</h1>
            <p class="slt-hub__sub">{{ $subtitle }}</p>
            <div class="slt-module-stats">
                <span>{{ $count }} {{ $count === 1 ? 'request' : 'requests' }}</span>
                @if($pending > 0)
                    <span>{{ $pending }} awaiting approval</span>
                @endif
            </div>
        </header>

        <section class="slt-module-actions" aria-label="{{ $title }} actions">
            @foreach($actions as $action)
                <a class="slt-tile slt-tile--{{ $action['key'] ?? $action['variant'] }}" href="{{ $action['href'] }}">
                    <span class="slt-tile__icon"><i class="{{ $action['icon'] }}"></i></span>
                    <span class="slt-tile__copy">
                        <strong>{{ $action['label'] }}</strong>
                        <em>
                            @if(str_contains($action['label'], 'Calendar'))
                                See dates and upcoming bookings
                            @elseif(str_contains($action['label'], 'Create'))
                                Submit a new request
                            @else
                                Track the bookings you already sent
                            @endif
                        </em>
                    </span>
                    <i class="ti ti-arrow-right slt-tile__go"></i>
                </a>
            @endforeach
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/slt-dashboard.js') }}?v={{ @filemtime(public_path('js/slt-dashboard.js')) ?: time() }}"></script>
@endpush
