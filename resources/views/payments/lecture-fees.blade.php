@extends('layouts.app')

@section('title', 'Lecture Fees')

@section('content')
<div class="container-fluid">

    
    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4"
        style="
            background: linear-gradient(110deg, #0756a8 0%, #245fc5 55%, #594bc8 100%);
            padding: 26px;
            border-radius: 18px;
            color: white;
            box-shadow: 0 8px 24px rgba(20, 65, 150, 0.18);
        ">

        <div>
            <h1 class="mb-1 fw-bold" style="font-size: 36px; color: white;">
                Lecture Fees
            </h1>

            <p class="mb-0" style="font-size: 16px; color: #eaf1ff;">
                View calculated lecture fees based on lecture hours and approved rates.
            </p>
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
            <form
                method="GET"
                action="{{ route('payments.lecture-fees') }}"
                id="lecture-fees-filters"
                data-url="{{ route('payments.lecture-fees') }}"
            >

                <div class="row">

                    {{-- Search --}}
                    <div class="col-md-3">
                        <label for="search">Lecturer</label>
                        <input
                            type="text"
                            name="search"
                            id="lecture-fees-search"
                            class="form-control"
                            placeholder="Search by lecturer..."
                            value="{{ request('search') }}"
                            autocomplete="off"
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

                    {{-- Reset Button --}}
                    <div class="col-md-2 d-flex align-items-end">
                        <a href="{{ route('payments.lecture-fees') }}"
                        class="btn btn-outline-primary w-100">
                            <i class="fas fa-sync-alt me-1"></i> Reset
                        </a>
                    </div>

                </div>

            </form>
        </div>
    </div>

    {{-- Lecture Fees Results --}}
    <div id="lecture-fees-results">
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
                                            <span class="badge badge-warning text-dark">
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

    
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('lecture-fees-filters');
        const search = document.getElementById('lecture-fees-search');
        const results = document.getElementById('lecture-fees-results');

        if (!form || !search || !results) return;

        let debounceTimer;
        let controller;

        async function refreshTable(url = null) {
            if (controller) controller.abort();
            controller = new AbortController();

            const requestUrl = url || (() => {
                const target = new URL(form.dataset.url, window.location.origin);
                target.search = new URLSearchParams(new FormData(form)).toString();
                return target.href;
            })();

            results.style.opacity = '0.5';

            try {
                const response = await fetch(requestUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    signal: controller.signal
                });

                if (!response.ok) throw new Error('Unable to load lecture fees.');

                const html = await response.text();
                const documentResult = new DOMParser().parseFromString(html, 'text/html');
                const newResults = documentResult.getElementById('lecture-fees-results');

                if (!newResults) throw new Error('Lecture fees results not found.');

                results.innerHTML = newResults.innerHTML;

                const currentUrl = new URL(requestUrl);
                history.replaceState({}, '', currentUrl.pathname + currentUrl.search);
            } catch (error) {
                if (error.name !== 'AbortError') {
                    console.error(error);
                }
            } finally {
                results.style.opacity = '1';
            }
        }

        search.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => refreshTable(), 300);
        });

        form.querySelectorAll('select, input[type="date"]').forEach(function (field) {
            field.addEventListener('change', () => refreshTable());
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            clearTimeout(debounceTimer);
            refreshTable();
        });

        results.addEventListener('click', function (event) {
            const link = event.target.closest('.pagination a');
            if (!link) return;

            event.preventDefault();
            refreshTable(link.href);
        });
    });
    </script>
    @endpush


</div>
@endsection