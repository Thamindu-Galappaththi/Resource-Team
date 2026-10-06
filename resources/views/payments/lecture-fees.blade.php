@extends('layouts.app')

@section('title', 'Lecture Fees')

@section('content')
<div class="container-fluid">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Lecture Fees</h2>
            <p class="text-muted mb-0">
                View calculated lecture fees based on lecture hours and approved rates.
            </p>
        </div>

        <div>
            <a href="{{ route('payments.lecture-fees') }}" class="btn btn-outline-secondary">
                <i class="fas fa-sync-alt"></i> Reset
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-filter"></i> Filters
            </h5>
        </div>

        <div class="card-body">
            <form method="GET" action="{{ route('payments.lecture-fees') }}">

                <div class="row">

                    {{-- Search --}}
                    <div class="col-md-3">
                        <label for="search">Lecturer</label>
                        <input
                            type="text"
                            name="search"
                            id="search"
                            class="form-control"
                            placeholder="Search lecturer..."
                            value="{{ request('search') }}"
                        >
                    </div>

                    {{-- Department --}}
                    <div class="col-md-3">
                        <label for="department">Department</label>
                        <select name="department" id="department" class="form-control">
                            <option value="">All Departments</option>

                            @foreach($departments as $department)
                                <option
                                    value="{{ $department }}"
                                    {{ request('department') == $department ? 'selected' : '' }}
                                >
                                    {{ $department }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Date From --}}
                    <div class="col-md-2">
                        <label for="date_from">From Date</label>
                        <input
                            type="date"
                            name="date_from"
                            id="date_from"
                            class="form-control"
                            value="{{ request('date_from') }}"
                        >
                    </div>

                    {{-- Date To --}}
                    <div class="col-md-2">
                        <label for="date_to">To Date</label>
                        <input
                            type="date"
                            name="date_to"
                            id="date_to"
                            class="form-control"
                            value="{{ request('date_to') }}"
                        >
                    </div>

                    {{-- Search Button --}}
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>

                </div>

            </form>
        </div>
    </div>

    {{-- Lecture Fees Table --}}
    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                Lecture Fee Records
            </h5>

            <span class="text-muted">
                {{ $payables->total() }} record(s)
            </span>
        </div>

        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover table-bordered mb-0">

                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Lecturer</th>
                            <th>Department</th>
                            <th>Qualification</th>
                            <th>Lecture Date</th>
                            <th>Hours</th>
                            <th>Hourly Rate</th>
                            <th>Total Fee</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($payables as $payable)

                            <tr>

                                {{-- Number --}}
                                <td>
                                    {{ $payables->firstItem() + $loop->index }}
                                </td>

                                {{-- Lecturer --}}
                                <td>
                                    <strong>
                                        {{ $payable->lecturer->name ?? 'N/A' }}
                                    </strong>
                                </td>

                                {{-- Department --}}
                                <td>
                                    {{ $payable->lectureSession->department ?? 'N/A' }}
                                </td>

                                {{-- Qualification --}}
                                <td>
                                    {{ $payable->lectureSession->qualification ?? 'N/A' }}
                                </td>

                                {{-- Date --}}
                                <td>
                                    {{ $payable->lectureSession->session_date?->format('d M Y') }}
                                </td>

                                {{-- Hours --}}
                                <td>
                                    {{ number_format((float) $payable->hours, 2) }}
                                </td>

                                {{-- Hourly Rate --}}
                                <td>
                                    {{ $payable->currency }}
                                    {{ number_format($payable->hourly_rate_minor / 100, 2) }}
                                </td>

                                {{-- Total Fee --}}
                                <td>
                                    <strong>
                                        {{ $payable->currency }}
                                        {{ number_format($payable->net_amount_minor / 100, 2) }}
                                    </strong>
                                </td>

                                {{-- Status --}}
                                <td>
                                    @if($payable->status === 'pending')
                                        <span class="badge badge-warning">
                                            Pending
                                        </span>
                                    @elseif($payable->status === 'paid')
                                        <span class="badge badge-success">
                                            Paid
                                        </span>
                                    @else
                                        <span class="badge badge-secondary">
                                            {{ ucfirst($payable->status) }}
                                        </span>
                                    @endif
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <i class="fas fa-info-circle"></i>
                                    No lecture fee records found.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

        {{-- Pagination --}}
        @if($payables->hasPages())
            <div class="card-footer">
                {{ $payables->links() }}
            </div>
        @endif

    </div>

</div>
@endsection