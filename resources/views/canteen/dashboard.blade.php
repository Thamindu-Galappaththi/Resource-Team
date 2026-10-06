@extends('layouts.app')

@section('title', 'Canteen Dashboard')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h1 class="h3 text-white mb-2">Canteen Dashboard</h1>
            <p class="text-white-50 mb-0">Plan service around confirmed and pending meal demand.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @can('viewAny', \App\Models\CanteenReservation::class)
                <a href="{{ route('canteen.index') }}" class="btn btn-outline-light"><i class="ti ti-list-check me-1"></i>Existing reservations</a>
            @endcan
            @can('create', \App\Models\CanteenReservation::class)
                <a href="{{ route('canteen.create') }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i>New reservation</a>
            @endcan
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Expected today</div>
                    <div class="d-flex align-items-baseline gap-2 mt-2"><h3 class="mb-0">{{ number_format($todayTotal ?? 0) }}</h3><span class="text-muted">orders</span></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Confirmed meals</div>
                    <div class="d-flex align-items-baseline gap-2 mt-2"><h3 class="mb-0">{{ number_format($todayConfirmed ?? 0) }}</h3><span class="text-muted">orders</span></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Awaiting review</div>
                    <div class="d-flex align-items-baseline gap-2 mt-2"><h3 class="mb-0">{{ number_format($todayPending ?? 0) }}</h3><span class="text-muted">groups</span></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-semibold">Busiest service slot</div>
                    <h3 class="mt-2 mb-0">{{ $peakSlot ?? 'No bookings' }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h2 class="h5 mb-3">Today by meal</h2>
                    @forelse($mealBreakdown as $row)
                        <div class="d-flex justify-content-between align-items-center py-2 @if(! $loop->last) border-bottom @endif">
                            <div>
                                <div class="fw-semibold">{{ $row['meal'] }}</div>
                                <div class="small text-muted">{{ number_format($row['groups']) }} {{ \Illuminate\Support\Str::plural('group', $row['groups']) }}</div>
                            </div>
                            <div class="fw-semibold">{{ number_format($row['orders']) }}</div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No pending or confirmed orders for today.</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-end mb-3">
                        <div>
                            <h2 class="h5 mb-1">Next 7 days</h2>
                            <p class="text-muted mb-0">Pending and confirmed orders only</p>
                        </div>
                    </div>
                    <div class="row g-3">
                        @foreach($forecast as $entry)
                            <div class="col-6 col-md-4 col-xl-3">
                                <a href="{{ route('canteen.forecast', ['date' => $entry['date']]) }}" class="d-block h-100 text-decoration-none text-reset border rounded p-3">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <div class="fw-semibold">{{ \Carbon\Carbon::parse($entry['date'])->format('D') }}</div>
                                            <div class="text-muted small">{{ \Carbon\Carbon::parse($entry['date'])->format('d M') }}</div>
                                        </div>
                                        <i class="ti ti-arrow-up-right text-muted" aria-hidden="true"></i>
                                    </div>
                                    <div class="mt-3">
                                        <span class="fs-4 fw-semibold">{{ number_format($entry['total'] ?? 0) }}</span>
                                        <span class="text-muted small ms-1">orders</span>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
