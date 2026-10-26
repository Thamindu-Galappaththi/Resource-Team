@extends('layouts.app')

@section('title', $title ?? 'Welcome to the resource reservation system')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/slt-dashboard.css') }}?v={{ @filemtime(public_path('css/slt-dashboard.css')) ?: time() }}">
@endpush

@section('content')
<div class="slt-hub">
    <div class="slt-hub__sky" aria-hidden="true">
        <div class="slt-hub__slides" data-slt-slides>
            @foreach(($slides ?? []) as $index => $slide)
                @php
                    $src = is_array($slide) ? ($slide['src'] ?? $slide['image'] ?? '') : $slide;
                    $caption = is_array($slide) ? ($slide['caption'] ?? '') : '';
                @endphp
                <div class="slt-hub__slide {{ $index === 0 ? 'is-active' : '' }}" style="background-image: url('{{ $src }}')" data-caption="{{ $caption }}"></div>
            @endforeach
        </div>
        <div class="slt-hub__wash"></div>
        <canvas id="slt-hub-stars" class="slt-hub__stars"></canvas>
        <p class="slt-hub__caption" data-slt-caption>{{ is_array($slides[0] ?? null) ? ($slides[0]['caption'] ?? '') : '' }}</p>
        <div class="slt-hub__dots" data-slt-dots role="tablist" aria-label="Background slideshow"></div>
        <div class="slt-hub__progress" aria-hidden="true"><span class="slt-hub__progress-bar" data-slt-progress></span></div>
    </div>

    <div class="slt-hub__inner">
        @if(session('status'))
            <div class="alert alert-success border-0 shadow-sm mb-3 rounded-3">{{ session('status') }}</div>
        @endif

        <header class="slt-hub__hero slt-hub__hero--glass">
            <p class="slt-hub__badge">
                <i class="ti ti-sparkles"></i>
                {{ $as_of }}
            </p>
            @if(!empty($user?->name))
                <p class="slt-hub__hello">Hello, {{ $user->name }}</p>
            @endif
            <h1>{{ $title }}</h1>
            <p class="slt-hub__sub">{{ $subtitle }}</p>
        </header>

        <section class="slt-hub__grid" aria-label="Reservation types">
            @foreach($cards as $card)
                <a class="slt-card slt-card--link slt-card--{{ $card['key'] }}" href="{{ $card['href'] }}" style="--slt-accent: {{ $card['accent'] ?? '#0b3d91' }}">
                    <div class="slt-card__media">
                        <img src="{{ $card['image'] }}" alt="" loading="lazy">
                        <div class="slt-card__media-shade"></div>
                        <span class="slt-card__index">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="slt-card__kicker">{{ $card['kicker'] }}</span>
                    </div>
                    <div class="slt-card__body">
                        <div class="slt-card__title-row">
                            <h2>{{ $card['title'] }}</h2>
                            <span class="slt-card__count" title="Your requests">{{ $card['count'] }}</span>
                        </div>
                        <p>{{ $card['blurb'] }}</p>
                        @if($card['pending'] > 0)
                            <p class="slt-card__pending">{{ $card['pending'] }} awaiting approval</p>
                        @endif
                        <span class="slt-card__enter">
                            Open dashboard
                            <i class="ti ti-arrow-right"></i>
                        </span>
                    </div>
                </a>
            @endforeach
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/slt-dashboard.js') }}?v={{ @filemtime(public_path('js/slt-dashboard.js')) ?: time() }}"></script>
@endpush
