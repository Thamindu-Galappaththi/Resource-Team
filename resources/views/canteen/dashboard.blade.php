@extends('layouts.app')

@section('title', 'Canteen Dashboard')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Canteen Dashboard</h2>
            <p class="text-muted mb-0">Meal forecast and expected demand overview.</p>
        </div>
        <a href="{{ route('reservations.calendar') }}" class="btn btn-primary">Open Reservation Calendar</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Total Expected Today</div>
                    <h3 class="mt-2 mb-1">{{ $todayTotal ?? 0 }}</h3>
                    <small class="text-success">+12.5% vs yesterday</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Kitchen Readiness</div>
                    <h3 class="mt-2 mb-1">{{ $kitchenReadiness ?? 0 }}%</h3>
                    <small class="text-success">Operations ready</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Peak Occupancy Time</div>
                    <h3 class="mt-2 mb-1">{{ $peakSlot ?? '12:30 PM' }}</h3>
                    <small class="text-muted">Best planning window</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
            <h5 class="mb-0">7-Day Forecast</h5>
            <span class="text-muted small">Expected diners</span>
        </div>
        <div class="card-body">
            <div class="row g-3">
                @forelse($forecast as $entry)
                    <div class="col-md-6 col-xl-4">
                        <div class="border rounded p-3 h-100">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong>{{ \Carbon\Carbon::parse($entry['date'])->format('D, M d') }}</strong>
                                <span class="badge bg-light text-dark">{{ $entry['total'] ?? 0 }}</span>
                            </div>
                            <div class="mt-3 text-muted small">
                                {{ $entry['total'] ?? 0 }} expected people
                            </div>
                            <a href="{{ route('canteen.forecast', ['date' => $entry['date']]) }}" class="btn btn-sm btn-outline-primary mt-3">View Details</a>
                        </div>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-info mb-0">No forecast data is available yet.</div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
