@extends('layouts.app')

@section('title', $title)

@push('styles')
<link rel="stylesheet" href="{{ asset('css/slt-dashboard.css') }}?v={{ @filemtime(public_path('css/slt-dashboard.css')) ?: time() }}">
@endpush

@section('content')
<div class="slt-hub slt-hub--module slt-hub--{{ $kind }}">
    <div class="slt-hub__sky" aria-hidden="true">
        <div class="slt-hub__photo"></div>
        <div class="slt-hub__wash"></div>
        <canvas id="slt-hub-stars" class="slt-hub__stars"></canvas>
    </div>

    <div class="slt-hub__inner">
        <header class="slt-hub__hero slt-hub__hero--compact slt-cal-head">
            <a class="slt-back" href="{{ $homeUrl }}">
                <i class="ti ti-arrow-left"></i>
                Back to {{ $kind }} dashboard
            </a>
            <div class="slt-cal-head__row">
                <div>
                    <h1>{{ $title }}</h1>
                    <p class="slt-hub__sub">{{ $subtitle }}</p>
                </div>
                <a class="slt-action slt-action--solid slt-action--inline" href="{{ $createUrl }}">
                    <i class="ti ti-plus"></i>
                    <span>{{ $createLabel }}</span>
                </a>
            </div>
        </header>

        <section class="slt-cal">
            <div class="slt-cal__nav">
                <a href="{{ request()->fullUrlWithQuery(['year' => $prev['year'], 'month' => $prev['month']]) }}" aria-label="Previous month">‹</a>
                <h2>{{ $label }}</h2>
                <a href="{{ request()->fullUrlWithQuery(['year' => $next['year'], 'month' => $next['month']]) }}" aria-label="Next month">›</a>
            </div>
            <div class="slt-cal__week">
                @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $day)
                    <span>{{ $day }}</span>
                @endforeach
            </div>
            <div class="slt-cal__grid">
                @foreach($cells as $cell)
                    @if($cell['empty'])
                        <div class="slt-cal__cell is-empty"></div>
                    @else
                        <div class="slt-cal__cell {{ $cell['isToday'] ? 'is-today' : '' }} {{ $cell['count'] ? 'has-items' : '' }}">
                            <span class="slt-cal__day">{{ $cell['day'] }}</span>
                            @foreach($cell['items'] as $item)
                                <a class="slt-cal__event slt-status--{{ $item['tone'] }}" href="{{ $item['href'] }}">
                                    {{ $item['title'] }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </div>
        </section>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/slt-dashboard.js') }}?v={{ @filemtime(public_path('js/slt-dashboard.js')) ?: time() }}"></script>
@endpush
