@extends('layouts.app')

@section('title', 'Maintenance')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 mb-4">
        <div>
            <div class="small text-uppercase fw-semibold text-primary mb-1">Canteen operations</div>
            <h2 class="mb-1">Maintenance</h2>
            <p class="text-muted mb-0">Resources currently unavailable for service.</p>
        </div>
        <span class="small text-muted">{{ now()->format('d M Y') }}</span>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-lg-4">
            <div class="border-start border-4 border-warning bg-white p-3 h-100">
                <div class="text-muted small text-uppercase fw-semibold">Under maintenance</div>
                <div class="d-flex align-items-baseline gap-2 mt-2"><span class="fs-3 fw-semibold">{{ $resources->count() }}</span><span class="text-muted">resources</span></div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="border-start border-4 border-secondary bg-white p-3 h-100">
                <div class="text-muted small text-uppercase fw-semibold">Tracked inventory</div>
                <div class="d-flex align-items-baseline gap-2 mt-2"><span class="fs-3 fw-semibold">{{ $resourceCount }}</span><span class="text-muted">resources</span></div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-end mb-3">
        <div><h4 class="mb-1">Maintenance queue</h4><p class="text-muted mb-0">Most recently updated resources first</p></div>
        <span class="badge bg-warning-subtle text-dark">{{ $resources->count() }} open</span>
    </div>

    <div class="border-top border-bottom bg-white">
        @if($resources->isEmpty())
            <div class="text-center px-3 py-5">
                <i class="ti ti-tool fs-1 text-muted" aria-hidden="true"></i>
                <h5 class="mt-3 mb-1">No resources in maintenance</h5>
                <p class="text-muted mb-0">Resources marked under maintenance will appear here.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Resource</th>
                            <th scope="col">Type</th>
                            <th scope="col">Location</th>
                            <th scope="col">Serial number</th>
                            <th scope="col">Last updated</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($resources as $resource)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $resource->name_model }}</div>
                                    <div class="small text-muted">{{ $resource->type?->category?->name ?? 'Uncategorized' }}</div>
                                </td>
                                <td>{{ $resource->type?->name ?? '-' }}</td>
                                <td>{{ $resource->location?->name ?? '-' }}</td>
                                <td><span class="font-monospace small">{{ $resource->serial_number }}</span></td>
                                <td>{{ $resource->updated_at?->format('d M Y') ?? '—' }}</td>
                                <td><span class="badge bg-warning-subtle text-dark">Under maintenance</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection