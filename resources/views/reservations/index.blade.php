@extends('layouts.app')

@section('title', 'Existing Reservations')

@php
    $statusLabels = [
        'pending' => 'Pending',
        'approved' => 'Confirmed',
        'confirmed' => 'Confirmed',
        'rejected' => 'Rejected',
        'declined' => 'Rejected',
    ];

    $statusClasses = [
        'pending' => 'badge bg-primary-subtle text-primary-emphasis',
        'approved' => 'badge bg-success-subtle text-success-emphasis',
        'confirmed' => 'badge bg-success-subtle text-success-emphasis',
        'rejected' => 'badge bg-danger-subtle text-danger-emphasis',
        'declined' => 'badge bg-danger-subtle text-danger-emphasis',
    ];

    $displayRows = $reservations;
@endphp

@section('content')
<style>
    @media (max-width: 768px) {
        .summary-card { margin-bottom: 1rem; }
        .filter-row { padding: 1rem 0; }
        .filter-row .col-lg-3 { margin-bottom: 1rem; }
        .table-responsive { border-radius: 12px; overflow: hidden; }
    }
    
    @media (max-width: 992px) {
    }
    
    .stat-card-icon { 
        display: inline-flex; 
        align-items: center; 
        justify-content: center; 
        width: 48px; 
        height: 48px; 
        border-radius: 12px; 
        font-size: 1.5rem; 
        transition: transform 0.2s ease;
    }
    
    .stat-card-icon:hover {
        transform: scale(1.08);
    }
    
    .stat-value {
        font-size: clamp(2rem, 5vw, 2.8rem);
        font-weight: 700;
        line-height: 1;
    }
    
    .filter-card {
        background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
        border: 1px solid rgba(13, 22, 36, 0.08);
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    }

    .reservation-heading-card {
        background: #ffffff;
        border: 1px solid rgba(13, 22, 36, 0.08);
        border-radius: 12px;
        padding: 1.5rem 1.75rem;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
    }
    
    .filter-input, .filter-select {
        border-radius: 8px;
        border-color: rgba(15, 23, 42, 0.12);
        background: rgba(255, 255, 255, 0.8);
        transition: all 0.2s ease;
        font-size: 0.95rem;
    }
    
    .filter-input:focus, .filter-select:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.08), inset 0 1px 2px rgba(15, 23, 42, 0.04);
        background: #fff;
    }
    
    .table-card {
        overflow: hidden;
        border-radius: 12px;
        border: 1px solid rgba(13, 22, 36, 0.08);
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
    }
    
    .table thead {
        background: linear-gradient(180deg, #f8fafc 0%, #f0f4f9 100%);
    }
    
    .table tbody tr {
        border-bottom: 1px solid #edf2f7;
        transition: all 0.15s ease;
    }
    
    .table tbody tr:hover {
        background: #f9fafb;
    }
    
    .table tbody tr:last-child {
        border-bottom: none;
    }
    
    .avatar-badge {
        min-width: 36px;
        height: 36px;
        border-radius: 8px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    
    .btn-icon-sm {
        width: 32px;
        height: 32px;
        padding: 0;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }
    
    .btn-icon-sm:hover {
        transform: translateY(-1px);
    }
    
    .pagination-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.5rem;
        border-top: 1px solid #edf2f7;
        background: #fafbfc;
        border-radius: 0 0 12px 12px;
    }
    
    .pagination {
        gap: 0.25rem;
    }
    
    .page-link {
        border-radius: 6px;
        border: 1px solid #d1d5db;
        transition: all 0.2s ease;
        min-width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #374151;
        text-decoration: none;
    }
    
    .page-link:hover:not(.disabled) {
        background: #f3f4f6;
        border-color: #9ca3af;
    }
    
    .page-item.active .page-link {
        background: #2563eb;
        border-color: #2563eb;
        color: white;
    }
    
    .page-item.disabled .page-link {
        color: #d1d5db;
        cursor: not-allowed;
        background: #f9fafb;
    }
</style>

<div class="container-fluid py-4">
    <div class="reservation-heading-card mb-4">
        <h1 class="mb-2 fw-bold text-dark" style="font-size: clamp(2rem, 2.8vw, 2.8rem); letter-spacing: -0.04em;">Existing Reservations</h1>
    </div>

    <!-- Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-lg-4 col-md-6 col-12">
            <div class="card border-0 h-100 summary-card" style="background: rgba(255,255,255,0.92); border: 1px solid rgba(13, 22, 36, 0.12); border-radius: 14px; box-shadow: 0 0 0 1px rgba(148,163,184,0.06);">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="stat-card-icon bg-primary-subtle text-primary">
                            <i class="ti ti-calendar-event"></i>
                        </div>
                        <span class="text-primary fw-semibold small badge bg-primary-subtle text-primary-emphasis">+12%</span>
                    </div>
                    <div class="text-secondary text-uppercase small mb-2 fw-semibold" style="letter-spacing: 0.05em;">Total Reservations</div>
                    <div class="stat-value text-dark mb-0">{{ $totalCount }}</div>
                    <div class="text-muted small mt-2">Last month's data</div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 col-12">
            <div class="card border-0 h-100 summary-card" style="background: rgba(255,255,255,0.92); border: 1px solid rgba(13, 22, 36, 0.12); border-radius: 14px; box-shadow: 0 0 0 1px rgba(148,163,184,0.06);">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="stat-card-icon bg-warning-subtle text-warning">
                            <i class="ti ti-clock-hour-4"></i>
                        </div>
                        <span class="text-warning fw-semibold small badge bg-warning-subtle text-warning-emphasis">Action</span>
                    </div>
                    <div class="text-secondary text-uppercase small mb-2 fw-semibold" style="letter-spacing: 0.05em;">Pending</div>
                    <div class="stat-value text-dark mb-0">{{ $pendingCount }}</div>
                    <div class="text-muted small mt-2">Requires review</div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-6 col-12">
            <div class="card border-0 h-100 summary-card" style="background: rgba(255,255,255,0.92); border: 1px solid rgba(220, 53, 69, 0.45); border-radius: 14px; box-shadow: 0 0 0 1px rgba(220,53,69,0.08);">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="stat-card-icon bg-danger-subtle text-danger">
                            <i class="ti ti-circle-x"></i>
                        </div>
                        <span class="text-danger fw-semibold small badge bg-danger-subtle text-danger-emphasis">Alert</span>
                    </div>
                    <div class="text-secondary text-uppercase small mb-2 fw-semibold" style="letter-spacing: 0.05em;">Rejected</div>
                    <div class="stat-value text-dark mb-0">{{ $rejectedCount }}</div>
                    <div class="text-muted small mt-2">Needs attention</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="filter-card mb-4">
        <div class="row g-3 align-items-end filter-row">
            <div class="col-lg-3 col-md-6">
                <label class="small text-uppercase text-secondary fw-semibold mb-2 d-block" style="letter-spacing: 0.04em;">Search by name</label>
                <input type="text" class="form-control filter-input" placeholder="Enter requester name" style="height: 44px;">
            </div>
            <div class="col-lg-3 col-md-6">
                <label class="small text-uppercase text-secondary fw-semibold mb-2 d-block" style="letter-spacing: 0.04em;">Resource type</label>
                <select class="form-select filter-select" style="height: 44px;">
                    <option>All Resources</option>
                    <option>Category 1</option>
                    <option>Category 2</option>
                    <option>Category 3</option>
                </select>
            </div>
            <div class="col-lg-3 col-md-6">
                <label class="small text-uppercase text-secondary fw-semibold mb-2 d-block" style="letter-spacing: 0.04em;">Date range</label>
                <input type="date" class="form-control filter-input" style="height: 44px;">
            </div>
            <div class="col-lg-3 col-md-6">
                <label class="small text-uppercase text-secondary fw-semibold mb-2 d-block" style="letter-spacing: 0.04em;">Status</label>
                <select class="form-select filter-select" style="height: 44px;">
                    <option>All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Desktop Table View -->
    <div class="table-card responsive-table-desktop">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-uppercase text-secondary small fw-semibold" style="border-bottom: 1px solid #e5e7eb;">Resource Name</th>
                        <th class="px-4 py-3 text-uppercase text-secondary small fw-semibold" style="border-bottom: 1px solid #e5e7eb;">Reserved By</th>
                        <th class="px-4 py-3 text-uppercase text-secondary small fw-semibold" style="border-bottom: 1px solid #e5e7eb;">Date</th>
                        <th class="px-4 py-3 text-uppercase text-secondary small fw-semibold" style="border-bottom: 1px solid #e5e7eb;">Time</th>
                        <th class="px-4 py-3 text-uppercase text-secondary small fw-semibold" style="border-bottom: 1px solid #e5e7eb;">Status</th>
                        <th class="px-4 py-3 text-uppercase text-secondary small fw-semibold text-center" style="border-bottom: 1px solid #e5e7eb;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($displayRows as $reservation)
                        @php
                            // Handle both Eloquent models and fallback arrays
                            if (is_object($reservation) && method_exists($reservation, 'getAttributes')) {
                                $resourceName = $reservation->resource->name_model ?? 'Unknown Resource';
                                $resourceCode = $reservation->resource->serial_number ?? '00';
                                $userName = $reservation->user->name ?? 'Unknown User';
                                $initials = strtoupper(substr(explode(' ', $userName)[0], 0, 1)) . strtoupper(substr(explode(' ', $userName)[1] ?? '', 0, 1));
                                $statusKey = strtolower((string) ($reservation->status ?? 'pending'));
                                $date = $reservation->reservation_date ? \Carbon\Carbon::parse($reservation->reservation_date)->format('M d, Y') : 'N/A';
                                $start = $reservation->start_time ?? 'N/A';
                                $end = $reservation->end_time ?? 'N/A';
                            } else {
                                $resourceName = $reservation['resource_name'] ?? 'Unknown Resource';
                                $resourceCode = $reservation['resource_code'] ?? '00';
                                $userName = $reservation['user_name'] ?? 'Unknown User';
                                $initials = $reservation['initials'] ?? strtoupper(substr($userName, 0, 2));
                                $statusKey = strtolower((string) ($reservation['status'] ?? 'pending'));
                                $date = $reservation['date'] ?? 'N/A';
                                $start = $reservation['start_time'] ?? 'N/A';
                                $end = $reservation['end_time'] ?? 'N/A';
                            }
                            
                            $statusText = $statusLabels[$statusKey] ?? ucfirst($statusKey);
                            $statusClass = $statusClasses[$statusKey] ?? 'badge bg-secondary-subtle text-secondary-emphasis';
                        @endphp
                        <tr style="background: #ffffff;">
                            <td class="px-4 py-3" style="border-bottom: 1px solid #edf2f7; vertical-align: middle;">
                                <div class="fw-semibold text-dark">{{ $resourceName }}</div>
                                <div class="text-muted small">{{ $resourceCode }}</div>
                            </td>
                            <td class="px-4 py-3" style="border-bottom: 1px solid #edf2f7; vertical-align: middle;">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-badge d-flex align-items-center justify-content-center fw-semibold text-primary bg-primary-subtle">
                                        {{ $initials }}
                                    </div>
                                    <span class="text-dark">{{ $userName }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-dark" style="border-bottom: 1px solid #edf2f7; vertical-align: middle;">{{ $date }}</td>
                            <td class="px-4 py-3 text-dark small" style="border-bottom: 1px solid #edf2f7; vertical-align: middle;">
                                <div>{{ $start }}</div>
                                <div class="text-muted">{{ $end }}</div>
                            </td>
                            <td class="px-4 py-3" style="border-bottom: 1px solid #edf2f7; vertical-align: middle;">
                                <span class="{{ $statusClass }} text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.04em; padding: .45rem .7rem; border-radius: 20px;">{{ $statusText }}</span>
                            </td>
                            <td class="px-4 py-3 text-center" style="border-bottom: 1px solid #edf2f7; vertical-align: middle;">
                                <div class="d-flex align-items-center justify-content-center gap-1">
                                    <button type="button" class="btn btn-icon-sm btn-outline-primary" aria-label="Edit reservation" title="Edit" style="border: 1px solid #d1d5db; color: #2563eb;"><i class="ti ti-pencil"></i></button>
                                    <button type="button" class="btn btn-icon-sm btn-outline-danger" aria-label="Delete reservation" title="Delete" style="border: 1px solid #d1d5db; color: #dc2626;"><i class="ti ti-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="pagination-container">
            <div class="text-secondary small">Showing {{ $reservations->firstItem() ?? 0 }} to {{ $reservations->lastItem() ?? 0 }} of {{ $reservations->total() }} entries</div>
            {{ $reservations->links() }}
        </div>
    </div>

</div>
@endsection
