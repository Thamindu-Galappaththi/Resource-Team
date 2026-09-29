@extends('layouts.app')

@section('title', 'Hostel Reservations')

@section('content')
<style>
    .hostel-reservations { max-width: 1200px; margin: 0 auto; }
    .hostel-reservations .summary-card { border: 1px solid #e8ebef; border-radius: 10px; padding: 20px; height: 100%; }
    .hostel-reservations .summary-label { font-size: 12px; text-transform: uppercase; color: #343a40; }
    .hostel-reservations .summary-value { font-size: 28px; font-weight: 600; color: #172431; }
    .hostel-reservations .check-ins { border-top: 3px solid #13aacb; }
    .hostel-reservations .filter-panel { background: #f7f8fa; border: 1px solid #edf0f3; border-radius: 8px; }
    .hostel-reservations .filter-panel .form-label { font-size: 11px; text-transform: uppercase; font-weight: 600; }
    .hostel-reservations .table thead th { background: #f7f8fa; font-size: 12px; text-transform: uppercase; white-space: nowrap; }
    .hostel-reservations .table td, .hostel-reservations .table th { padding: 16px; }
</style>
<div class="hostel-reservations py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 text-white mb-2">Hostel Reservations</h1>
            <p class="text-white mb-0">Manage student and guest accommodation logistics across the Nebula campus.</p>
        </div>
        @if(auth()->user()->hasPermission('hostel.create'))
            <a href="{{ route('hostel.create') }}" class="btn btn-primary">
                <i class="ti ti-circle-plus me-2" aria-hidden="true"></i>New Reservation
            </a>
        @endif
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            {{-- Database-backed totals and records will be connected with the booking backend. --}}
            <div class="row g-3 mb-4">
                @foreach(['Total Bookings', 'Check-ins Today', 'Available Rooms', 'Pending Requests'] as $label)
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="summary-card {{ $label === 'Check-ins Today' ? 'check-ins' : '' }}">
                            <div class="summary-label mb-1">{{ $label }}</div>
                            <div class="summary-value"><span aria-hidden="true">—</span><span class="visually-hidden">Not available</span></div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="filter-panel p-3 mb-4">
                <fieldset disabled aria-describedby="hostel-data-note">
                    <legend class="visually-hidden">Filter hostel reservations</legend>
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-6 col-xl-3">
                            <label for="hostel-check-in-from" class="form-label">Check-in From</label>
                            <input type="date" id="hostel-check-in-from" name="check_in_from" class="form-control">
                        </div>
                        <div class="col-12 col-md-6 col-xl-3">
                            <label for="hostel-check-in-to" class="form-label">Check-in To</label>
                            <input type="date" id="hostel-check-in-to" name="check_in_to" class="form-control">
                        </div>
                        <div class="col-12 col-md-6 col-xl-3">
                            <label for="hostel-room-category" class="form-label">Room Category</label>
                            <select id="hostel-room-category" name="room_category" class="form-select">
                                <option value="">All Categories</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6 col-xl-3">
                            <label for="hostel-reservation-status" class="form-label">Reservation Status</label>
                            <select id="hostel-reservation-status" name="status" class="form-select">
                                <option value="">All Statuses</option>
                            </select>
                        </div>
                        <div class="col-12 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-light">Reset</button>
                            <button type="button" class="btn btn-primary">Apply Filters</button>
                        </div>
                    </div>
                </fieldset>
            </div>

            <p id="hostel-data-note" class="small text-muted">Reservation data is not connected yet. Totals and filters will be available when booking data is connected.</p>
            <div class="table-responsive border rounded">
                <table class="table align-middle mb-0">
                    <caption class="visually-hidden">Hostel reservations</caption>
                    <thead>
                        <tr>
                            <th scope="col">Reservation ID</th>
                            <th scope="col">Guest Name</th>
                            <th scope="col">Room Category</th>
                            <th scope="col">Check-in</th>
                            <th scope="col">Check-out</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="ti ti-bed d-block fs-7 mb-2" aria-hidden="true"></i>
                                No reservation data to display.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
