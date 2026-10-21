@extends('layouts.app')

@section('title', $title ?? 'Dashboard')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-role.css') }}?v={{ @filemtime(public_path('css/dashboard-role.css')) ?: time() }}">
@endpush

@section('content')
<div class="rd-page">

    @if(session('status'))
        <div class="alert alert-success border-0 shadow-sm mb-3 rounded-3">{{ session('status') }}</div>
    @endif

    {{-- ── HERO ─────────────────────────────────────────────── --}}
    <header class="rd-hero">
        <div class="rd-hero__copy">
            <p class="rd-hero__badge">
                <i class="ti ti-layout-dashboard"></i>
                {{ $role->name ?? 'Workspace' }} &nbsp;·&nbsp; {{ $as_of }}
            </p>
            <h1>{{ $title }}</h1>
            <p class="rd-hero__sub">{{ $subtitle }}</p>
        </div>

        @if(!empty($actions))
            <nav class="rd-hero__actions" aria-label="Quick actions">
                @foreach($actions as $action)
                    <a class="rd-btn {{ $action['variant'] === 'primary' ? 'rd-btn--solid' : 'rd-btn--outline' }}"
                       href="{{ $action['href'] }}">
                        <i class="{{ $action['icon'] }}"></i>
                        <span>{{ $action['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        @endif
    </header>

    {{-- ── KPI CARDS ──────────────────────────────────────── --}}
    @if(!empty($kpis))
        <section class="rd-kpis" aria-label="Key figures">
            @foreach($kpis as $kpi)
                @php $tag = !empty($kpi['href']) ? 'a' : 'div'; @endphp
                <{{ $tag }} class="rd-kpi tone-{{ $kpi['tone'] }}"
                    @if(!empty($kpi['href'])) href="{{ $kpi['href'] }}" @endif>
                    <div class="rd-kpi__top">
                        <span class="rd-kpi__label">{{ $kpi['label'] }}</span>
                        <span class="rd-kpi__icon" aria-hidden="true">
                            <i class="{{ $kpi['icon'] }}"></i>
                        </span>
                    </div>
                    <span class="rd-kpi__value">{{ $kpi['value'] }}</span>
                    <span class="rd-kpi__hint">{{ $kpi['hint'] }}</span>
                </{{ $tag }}>
            @endforeach
        </section>
    @endif

    {{-- ── CHART + BREAKDOWN ──────────────────────────────── --}}
    <div class="rd-grid">
        {{-- Bar chart --}}
        <section class="rd-panel rd-panel--chart">
            <div class="rd-panel__head">
                <h2>{{ $chart['title'] ?? 'This week' }}</h2>
                <span class="rd-panel__badge">Last 7 days</span>
            </div>
            <div class="rd-panel__body">
                <div class="rd-chart-frame">
                    <canvas id="rd-bar-chart" aria-label="{{ $chart['title'] ?? 'Weekly volume' }}"></canvas>
                </div>
            </div>
        </section>

        {{-- Doughnut / breakdown --}}
        <section class="rd-panel">
            <div class="rd-panel__head">
                <h2>{{ $breakdown['title'] ?? 'Breakdown' }}</h2>
            </div>
            <div class="rd-panel__body">
                @if(empty($breakdown['items']))
                    <div class="rd-empty">
                        <i class="ti ti-chart-donut-3"></i>
                        <p>No status data yet.</p>
                    </div>
                @else
                    <div class="rd-donut-wrap">
                        <div class="rd-donut-chart">
                            <canvas id="rd-mix-chart" aria-label="{{ $breakdown['title'] ?? 'Breakdown' }}"></canvas>
                            <div class="rd-donut-center">
                                <strong>{{ number_format(collect($breakdown['items'])->sum('value')) }}</strong>
                                <span>Total</span>
                            </div>
                        </div>
                        <ul class="rd-legend" role="list">
                            @foreach($breakdown['items'] as $item)
                                <li>
                                    <span class="rd-legend__dot tone-{{ $item['tone'] }}" aria-hidden="true"></span>
                                    <span class="rd-legend__label">{{ $item['label'] }}</span>
                                    <span class="rd-legend__value">{{ number_format($item['value']) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </section>
    </div>

    {{-- ── LISTS ──────────────────────────────────────────── --}}
    <div class="rd-grid rd-grid--lists">
        @include('dashboards.partials.list', ['list' => $queue])
        @include('dashboards.partials.list', ['list' => $upcoming])
    </div>

    {{-- ── SPOTLIGHT ──────────────────────────────────────── --}}
    @if(!empty($spotlight['items'] ?? []))
        <section class="rd-panel" style="margin-bottom: 0;">
            <div class="rd-panel__head">
                <h2>{{ $spotlight['title'] }}</h2>
            </div>
            <div class="rd-panel__body">
                <ul class="rd-spot">
                    @foreach($spotlight['items'] as $item)
                        <li>
                            <span>{{ $item['label'] }}</span>
                            <strong>{{ $item['value'] }}</strong>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script id="rd-dash-data" type="application/json">@php
    echo json_encode(['chart' => $chart ?? ['points'=>[],'series'=>[]], 'breakdown' => $breakdown['items'] ?? []], JSON_HEX_TAG|JSON_UNESCAPED_UNICODE);
@endphp</script>
<script src="{{ asset('js/dashboard-charts.js') }}?v=6"></script>
@endpush
