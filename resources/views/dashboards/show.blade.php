@extends('layouts.app')

@section('title', $title ?? 'Dashboard')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-role.css') }}?v={{ @filemtime(public_path('css/dashboard-role.css')) ?: time() }}">
@endpush

@section('content')
<div class="rs-dash">
    @if(session('status'))
        <div class="alert alert-success border-0 shadow-sm">{{ session('status') }}</div>
    @endif

    <header class="rs-dash-hero">
        <div class="rs-dash-hero__copy">
            <p class="rs-dash-kicker">{{ $role->name ?? 'Workspace' }} · {{ $as_of }}</p>
            <h1>{{ $title }}</h1>
            <p class="rs-dash-lead">{{ $subtitle }}</p>
        </div>
        @if(!empty($actions))
            <div class="rs-dash-actions">
                @foreach($actions as $action)
                    <a class="rs-dash-action {{ $action['variant'] === 'primary' ? 'is-primary' : '' }}" href="{{ $action['href'] }}">
                        <i class="{{ $action['icon'] }}"></i>
                        <span>{{ $action['label'] }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </header>

    @if(!empty($kpis))
        <section class="rs-dash-kpis" aria-label="Key figures">
            @foreach($kpis as $kpi)
                @php $tag = !empty($kpi['href']) ? 'a' : 'div'; @endphp
                <{{ $tag }} class="rs-dash-kpi tone-{{ $kpi['tone'] }}" @if(!empty($kpi['href'])) href="{{ $kpi['href'] }}" @endif>
                    <span class="rs-dash-kpi__icon" aria-hidden="true"><i class="{{ $kpi['icon'] }}"></i></span>
                    <span class="rs-dash-kpi__label">{{ $kpi['label'] }}</span>
                    <span class="rs-dash-kpi__value">{{ $kpi['value'] }}</span>
                    <span class="rs-dash-kpi__hint">{{ $kpi['hint'] }}</span>
                </{{ $tag }}>
            @endforeach
        </section>
    @endif

    <div class="rs-dash-grid">
        <section class="rs-dash-panel">
            <div class="rs-dash-panel__head">
                <h2>{{ $chart['title'] ?? 'This week' }}</h2>
            </div>
            <div class="rs-chart" role="img" aria-label="{{ $chart['title'] ?? 'Weekly volume' }}">
                @forelse($chart['points'] ?? [] as $point)
                    <div class="rs-chart__col">
                        <span class="rs-chart__n">{{ $point['value'] }}</span>
                        <div class="rs-chart__track">
                            <div class="rs-chart__bar" style="height: {{ $point['pct'] }}%"></div>
                        </div>
                        <span class="rs-chart__d">{{ $point['label'] }}</span>
                    </div>
                @empty
                    <p class="rs-dash-empty mb-0">No volume to chart yet.</p>
                @endforelse
            </div>
        </section>

        <section class="rs-dash-panel">
            <div class="rs-dash-panel__head">
                <h2>{{ $breakdown['title'] ?? 'Breakdown' }}</h2>
            </div>
            @forelse($breakdown['items'] ?? [] as $item)
                <div class="rs-mix">
                    <div class="rs-mix__row">
                        <span>{{ $item['label'] }}</span>
                        <strong>{{ $item['value'] }}</strong>
                    </div>
                    <div class="rs-mix__track">
                        <div class="rs-mix__fill tone-{{ $item['tone'] }}" style="width: {{ $item['pct'] }}%"></div>
                    </div>
                </div>
            @empty
                <p class="rs-dash-empty mb-0">No status data yet.</p>
            @endforelse
        </section>
    </div>

    <div class="rs-dash-grid rs-dash-grid--lists">
        @include('dashboards.partials.list', ['list' => $queue])
        @include('dashboards.partials.list', ['list' => $upcoming])
    </div>

    @if(! empty($spotlight['items'] ?? []))
        <section class="rs-dash-panel">
            <div class="rs-dash-panel__head">
                <h2>{{ $spotlight['title'] }}</h2>
            </div>
            <ul class="rs-spot">
                @foreach($spotlight['items'] as $item)
                    <li>
                        <span>{{ $item['label'] }}</span>
                        <strong>{{ $item['value'] }}</strong>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</div>
@endsection
