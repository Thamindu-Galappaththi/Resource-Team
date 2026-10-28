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

        <section class="slt-cal" data-create-label="{{ $createLabel }}">
            <div class="slt-cal__nav">
                <a href="{{ request()->fullUrlWithQuery(['year' => $prev['year'], 'month' => $prev['month']]) }}" aria-label="Previous month">‹</a>
                <h2>{{ $label }}</h2>
                <a href="{{ request()->fullUrlWithQuery(['year' => $next['year'], 'month' => $next['month']]) }}" aria-label="Next month">›</a>
            </div>
            <p class="slt-cal__hint">Your bookings have a darker outline. Click a date to create a reservation, or open the day list to see every booking.</p>
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
                        <div
                            class="slt-cal__cell {{ $cell['isToday'] ? 'is-today' : '' }} {{ $cell['count'] ? 'has-items' : '' }}"
                            data-date="{{ $cell['iso'] }}"
                            data-label="{{ $cell['label'] }}"
                            data-create="{{ $cell['createUrl'] }}"
                            data-items="{{ json_encode($cell['items'], JSON_UNESCAPED_UNICODE) }}"
                        >
                            <a class="slt-cal__day" href="{{ $cell['createUrl'] }}" title="Create reservation for this date">{{ $cell['day'] }}</a>
                            @foreach($cell['preview'] as $item)
                                @if(!empty($item['mine']) && !empty($item['href']))
                                    <a class="slt-cal__event is-mine slt-status--{{ $item['tone'] }}" href="{{ $item['href'] }}">{{ $item['title'] }}</a>
                                @else
                                    <span class="slt-cal__event slt-status--{{ $item['tone'] }}">{{ $item['title'] }}</span>
                                @endif
                            @endforeach
                            @if($cell['count'] > 2)
                                <button type="button" class="slt-cal__more" data-slt-more>+{{ $cell['count'] - 2 }} more</button>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        </section>
    </div>
</div>

<div class="slt-cal-pop" id="slt-cal-pop" hidden>
    <div class="slt-cal-pop__card" role="dialog" aria-modal="true" aria-labelledby="slt-cal-pop-title">
        <header class="slt-cal-pop__head">
            <h2 id="slt-cal-pop-title"></h2>
            <button type="button" class="slt-cal-pop__close" data-slt-pop-close aria-label="Close"><i class="ti ti-x"></i></button>
        </header>
        <div class="slt-cal-pop__list" id="slt-cal-pop-list"></div>
        <footer class="slt-cal-pop__foot">
            <a class="slt-action slt-action--solid slt-action--inline" id="slt-cal-pop-create" href="#">
                <i class="ti ti-plus"></i>
                <span>{{ $createLabel }}</span>
            </a>
        </footer>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/slt-dashboard.js') }}?v={{ @filemtime(public_path('js/slt-dashboard.js')) ?: time() }}"></script>
@endpush
