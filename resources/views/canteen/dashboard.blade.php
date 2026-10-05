@extends('layouts.app')

@section('title', 'Canteen Overview')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <div class="small text-uppercase fw-semibold text-primary mb-1">Canteen operations</div>
            <h2 class="mb-1">Daily overview</h2>
            <p class="text-muted mb-0">Plan service around confirmed and pending meal demand.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('canteen.reservations.index') }}" class="btn btn-outline-secondary"><i class="ti ti-list-check me-1"></i>Reservations</a>
            @can('create', \App\Models\CanteenReservation::class)
                <a href="{{ route('canteen.reservations.create') }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i>New reservation</a>
            @endcan
        </div>
        @if(auth()->user()->hasPermission('canteen.index'))
            <a href="{{ route('canteen.index') }}" class="btn btn-primary">Existing Canteen Reservations</a>
        @endif
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-4">
            <div class="card border-0 h-100" style="border-left: 4px solid #146c94 !important;">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Orders expected today</div>
                    <div class="d-flex align-items-baseline gap-2 mt-2"><h3 class="mb-0">{{ number_format($todayTotal ?? 0) }}</h3><span class="text-muted">orders</span></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card border-0 h-100" style="border-left: 4px solid #3a7d44 !important;">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Reservations today</div>
                    <div class="d-flex align-items-baseline gap-2 mt-2"><h3 class="mb-0">{{ number_format($todayBookings ?? 0) }}</h3><span class="text-muted">bookings</span></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card border-0 h-100" style="border-left: 4px solid #b05b2a !important;">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Busiest service slot</div>
                    <h3 class="mt-2 mb-0">{{ $peakSlot ?? 'No bookings' }}</h3>
                </div>
            </div>
        </div>
    </div>

    <section class="mb-4">
        <div class="d-flex justify-content-between align-items-end mb-3">
            <div><h4 class="mb-1">Next 7 days</h4><p class="text-muted mb-0">Expected orders by service date</p></div>
            <span class="small text-muted d-none d-sm-inline">Updated from reservations</span>
        </div>
        <div class="row g-3">
            @forelse($forecast as $entry)
                <div class="col-6 col-md-4 col-xl-3">
                    <a href="{{ route('canteen.forecast', ['date' => $entry['date']]) }}" class="d-block h-100 text-decoration-none text-reset border rounded p-3 bg-white">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div><div class="fw-semibold">{{ \Carbon\Carbon::parse($entry['date'])->format('D') }}</div><div class="text-muted small">{{ \Carbon\Carbon::parse($entry['date'])->format('d M') }}</div></div>
                            <i class="ti ti-arrow-up-right text-muted" aria-hidden="true"></i>
                        </div>
                        <div class="mt-3"><span class="fs-4 fw-semibold">{{ number_format($entry['total'] ?? 0) }}</span><span class="text-muted small ms-1">orders</span></div>
                    </a>
                </div>
            @empty
                <div class="col-12">
                    <div class="border rounded bg-white p-4 text-center">
                        <i class="ti ti-calendar-off fs-2 text-muted" aria-hidden="true"></i>
                        <p class="mb-0 mt-2 text-muted">No forecast data is available yet.</p>
                    </div>
                </div>
            @endforelse
        </div>
    </section>
</div>
@endsection
