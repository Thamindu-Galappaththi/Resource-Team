@extends('layouts.app')

@section('title', 'Hostel Reservations')

@section('content')
<style>
    /* Scoped to hostel pages; follows the Resource Calendar's visual style. */
    .hostel-page {
        width: 100%;
        margin: 0 auto;
        padding: 35px 25px 50px !important;
        min-height: 100vh;
        background: transparent;
        color: #111827;
        font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }
    .hostel-page h1, .hostel-page > div > div > h2 {
        color: #0f172a !important;
        font-size: 26px;
        font-weight: 800;
        letter-spacing: -.02em;
    }
    .hostel-page .page-subtitle, .hostel-page > div > div > p,
    .hostel-page nav, .hostel-page nav a { color: #6b7280 !important; font-size: 14px; }
    .hostel-reservations > .d-flex h1,
    .hostel-reservations > .d-flex .page-subtitle,
    .hostel-page.hostel-details > .d-flex > div > h2,
    .hostel-page.hostel-details > .d-flex > div > p { color: #fff !important; }
    .hostel-page.hostel-create > .mb-4 > h1,
    .hostel-page.hostel-create > .mb-4 > nav,
    .hostel-page.hostel-create > .mb-4 > nav a,
    .hostel-page.hostel-create > .mb-4 > nav span,
    .hostel-page.hostel-create > .mb-4 > nav i { color: #fff !important; }
    .hostel-page .card, .hostel-page .summary-card {
        background: rgba(255,255,255,.82);
        border: 1px solid rgba(235,238,244,.8) !important;
        border-radius: 10px;
        box-shadow: 0 8px 25px rgba(0,0,0,.08) !important;
    }
    .hostel-reservations > .card { background: transparent; border: 0 !important; box-shadow: none !important; }
    .hostel-reservations > .card > .card-body { padding: 0 !important; }
    .hostel-page .summary-card { height: 100%; padding: 16px; transition: transform .22s ease, box-shadow .22s ease; }
    .hostel-page .summary-card:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(23,105,232,.12) !important; }
    .hostel-page .summary-label { font-size: 12px; color: #6b7280; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
    .hostel-page .summary-value { margin-top: 8px; font-size: 26px; font-weight: 900; color: #0f172a; }
    .hostel-page .filter-panel {
        background: rgba(255,255,255,.78);
        border: 1px solid rgba(235,238,244,.8);
        border-radius: 10px;
        box-shadow: 0 8px 25px rgba(0,0,0,.06);
    }
    .hostel-page .form-label, #hostel-reservation-modals .form-label { font-size: 12px; color: #374151; font-weight: 700; }
    .hostel-page .form-control, .hostel-page .form-select,
    #hostel-reservation-modals .form-control {
        min-height: 38px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background-color: #fff;
        color: #111827;
        font-size: 13px;
    }
    .hostel-page .form-control:focus, .hostel-page .form-select:focus,
    #hostel-reservation-modals .form-control:focus { border-color: #1769e8; box-shadow: 0 0 0 3px rgba(23,105,232,.14); }
    .hostel-page .btn, #hostel-reservation-modals .btn { border-radius: 8px; font-size: 13px; font-weight: 600; padding: 8px 14px; transition: background .2s ease, color .2s ease; }
    .hostel-page .btn-primary { background: #1769d1; border-color: #1769d1; color: #fff; }
    .hostel-page .btn-primary:hover { background: #155bbb; border-color: #155bbb; }
    .hostel-page .btn-outline-primary, .hostel-page .btn-outline-secondary,
    .hostel-page .btn-outline-light, .hostel-page .btn-light,
    #hostel-reservation-modals .btn-outline-secondary {
        background: rgba(255,255,255,.35); border: 1px solid #1769e8; color: #1769d1;
    }
    .hostel-page .btn-outline-primary:hover, .hostel-page .btn-outline-secondary:hover,
    .hostel-page .btn-outline-light:hover, .hostel-page .btn-light:hover,
    #hostel-reservation-modals .btn-outline-secondary:hover { background: #1769d1; color: #fff; }
    .hostel-page .status-capsules { display: flex; flex-wrap: wrap; gap: 4px; padding: 4px; width: fit-content; border: 1px solid #dbe5f6; border-radius: 9px; background: rgba(255,255,255,.78); }
    .hostel-page .status-capsules .btn { border: 0; border-radius: 7px; }
    .hostel-page .status-capsules .btn-outline-primary { background: transparent; }
    .hostel-page .status-capsules .btn-outline-primary:hover { background: transparent; color: #0f172a; }
    .hostel-page .status-capsules .btn-primary { box-shadow: 0 3px 9px rgba(23,105,232,.2); }
    .hostel-page .table-responsive { padding: 16px; background: rgba(255,255,255,.82); border: 0 !important; border-radius: 10px !important; box-shadow: 0 8px 25px rgba(0,0,0,.08); }
    .hostel-page .table { font-size: 13px; color: #111827; }
    .hostel-page .table > :not(caption) > * > * { padding: 10px 12px; background: transparent; border-bottom-color: #e5e7eb; }
    .hostel-page .table thead th { background: #f9fafb; color: #374151; font-weight: 800; white-space: nowrap; }
    .hostel-page .table tbody tr:hover { background: #f9fafb; }
    .hostel-page .table .btn { padding: 5px 10px; font-size: 12px; }
    .hostel-page .badge, #hostel-reservation-modals .badge { font-size: 12px; font-weight: 600; }
    .hostel-create .booking-card { padding: 28px; }
    .hostel-create .booking-heading { color: #0f172a; font-size: 18px; font-weight: 800; border-bottom: 1px solid #e5e7eb; padding-bottom: 18px; margin-bottom: 24px; }
    .hostel-create textarea.form-control { min-height: 120px; }
    .hostel-create .features-heading { background: #eef3fa; color: #1769d1; padding: 18px 22px; }
    .hostel-create .feature-list { list-style: none; padding: 0; margin: 0; }
    .hostel-create .feature-list li { display: flex; gap: 12px; align-items: center; margin-bottom: 16px; }
    .hostel-create .feature-list i { color: #1769d1; font-size: 21px; }
    .hostel-create .help-panel { background: rgba(238,243,250,.65); border-top: 1px solid #e5e7eb; padding: 22px; }
    .hostel-create .create-button { min-height: 44px; }
    .hostel-details .card-body { padding: 24px; }
    .hostel-details .card h5 { color: #0f172a; font-size: 18px; font-weight: 800; }
    .hostel-details .card strong { display: block; margin-bottom: 5px; color: #6b7280; font-size: 12px; font-weight: 700; }
    #hostel-reservation-modals .modal-content { border: 1px solid #e5e7eb; border-radius: 12px; background: #fff; box-shadow: 0 18px 50px rgba(15,23,42,.2); color: #111827; overflow: hidden; }
    #hostel-reservation-modals .modal-header { padding: 20px 24px; background: #eef3fa !important; border-bottom: 1px solid #e5e7eb; }
    #hostel-reservation-modals .modal-title { color: #0f172a; font-weight: 800; }
    #hostel-reservation-modals .modal-body { padding: 24px; }
    #hostel-reservation-modals .modal-footer { border-top: 1px solid #e5e7eb; padding: 16px 24px; }
    @media (max-width: 575.98px) {
        .hostel-page { padding: 24px 12px 32px !important; }
        .hostel-create .booking-card, .hostel-details .card-body { padding: 20px; }
        .hostel-details > .d-flex { flex-wrap: wrap; gap: 16px; }
        .hostel-page .status-capsules { width: 100%; }
    }
    @media (prefers-reduced-motion: reduce) {
        .hostel-page .summary-card, .hostel-page .btn { transition: none; }
        .hostel-page .summary-card:hover { transform: none; }
    }
</style>
<div class="hostel-page hostel-reservations py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h1 class="h3 mb-2 text-white">Hostel Reservations</h1>
            <p class="page-subtitle mb-0 text-white-50">Manage student and guest accommodation logistics across the Nebula campus.</p>
        </div>
        @can('createHostel', \App\Models\Reservation::class)
        <a href="{{ route('hostel.create') }}" class="btn btn-primary">
            <i class="ti ti-circle-plus me-2" aria-hidden="true"></i>New Reservation
        </a>
        @endcan
    </div>

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="summary-card">
                        <div class="summary-label mb-1">Total Bookings</div>
                        <div class="summary-value">{{ $summary['total'] }}</div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="summary-card check-ins">
                        <div class="summary-label mb-1">Check-ins Today</div>
                        <div class="summary-value">{{ $summary['check_ins_today'] }}</div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="summary-card">
                        <div class="summary-label mb-1">Available Rooms</div>
                        <div class="summary-value">{{ $summary['available_rooms'] }}</div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="summary-card">
                        <div class="summary-label mb-1">Pending Requests</div>
                        <div class="summary-value">{{ $summary['pending'] }}</div>
                    </div>
                </div>
            </div>

            <form id="hostel-filter-form" method="GET" action="{{ route('hostel.index') }}" class="filter-panel p-3 mb-4">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" id="search" name="search" class="form-control"
                            value="{{ request('search') }}" placeholder="ID or guest">
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="check_in_from" class="form-label">Check-in From</label>
                        <input type="date" id="check_in_from" name="check_in_from" class="form-control"
                            value="{{ request('check_in_from') }}">
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="check_in_to" class="form-label">Check-in To</label>
                        <input type="date" id="check_in_to" name="check_in_to" class="form-control"
                            value="{{ request('check_in_to') }}">
                    </div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label for="room_type_id" class="form-label">Room Category</label>
                        <select id="room_type_id" name="room_type_id" class="form-select">
                            <option value="">All Categories</option>
                            @foreach($roomTypes as $type)
                            <option value="{{ $type->id }}" @selected(request('room_type_id') == $type->id)>{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label for="status" class="form-label">Reservation Status</label>
                        <input type="hidden" id="status" name="status" value="{{ request('status') }}">
                        <div class="status-capsules" role="group" aria-label="Filter by reservation status">
                            <button type="button" class="btn {{ request('status') ? 'btn-outline-primary' : 'btn-primary' }}" data-hostel-status="" aria-pressed="{{ request('status') ? 'false' : 'true' }}">All</button>
                            @foreach($statuses as $status)
                                <button type="button" class="btn {{ request('status') === $status->value ? 'btn-primary' : 'btn-outline-primary' }}" data-hostel-status="{{ $status->value }}" aria-pressed="{{ request('status') === $status->value ? 'true' : 'false' }}">{{ $status->label() }}</button>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <button type="button" id="hostel-reset-filters" class="btn btn-light">Reset</button>
                        <button class="btn btn-primary">Apply Filters</button>
                    </div>
                </div>
            </form>

            @push('scripts')
                <script>
                    const hostelSearch = document.getElementById('search');
                    const hostelFilterForm = document.getElementById('hostel-filter-form');
                    let hostelSearchTimeout;
                    let hostelSearchRequest;

                    function hostelFilterUrl() {
                        const url = new URL(hostelFilterForm.action, window.location.origin);
                        url.search = new URLSearchParams(new FormData(hostelFilterForm)).toString();
                        return url.toString();
                    }

                    async function refreshHostelResults(url) {
                        hostelSearchRequest?.abort();
                        hostelSearchRequest = new AbortController();

                        try {
                            const response = await fetch(url, {
                                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                                signal: hostelSearchRequest.signal,
                            });
                            if (!response.ok) throw new Error('Unable to load hostel reservations.');

                            const html = await response.text();
                            const page = new DOMParser().parseFromString(html, 'text/html');
                            const nextResults = page.querySelector('#hostel-reservation-results');
                            const nextModals = page.querySelector('#hostel-reservation-modals');
                            const currentResults = document.getElementById('hostel-reservation-results');
                            const currentModals = document.getElementById('hostel-reservation-modals');
                            if (!nextResults || !nextModals || !currentResults || !currentModals) {
                                throw new Error('The reservation results could not be updated.');
                            }

                            currentResults.replaceWith(nextResults);
                            currentModals.replaceWith(nextModals);
                            window.history.replaceState(null, '', url);
                        } catch (error) {
                            if (error.name === 'AbortError') return;
                            window.location.assign(url);
                        }
                    }

                    hostelFilterForm?.addEventListener('submit', (event) => {
                        event.preventDefault();
                        window.clearTimeout(hostelSearchTimeout);
                        refreshHostelResults(hostelFilterUrl());
                    });

                    document.getElementById('hostel-reset-filters')?.addEventListener('click', () => {
                        window.clearTimeout(hostelSearchTimeout);
                        ['search', 'check_in_from', 'check_in_to', 'room_type_id', 'status'].forEach((name) => {
                            hostelFilterForm.elements.namedItem(name).value = '';
                        });
                        hostelFilterForm.querySelectorAll('[data-hostel-status]').forEach((capsule) => {
                            const selected = capsule.dataset.hostelStatus === '';
                            capsule.classList.toggle('btn-primary', selected);
                            capsule.classList.toggle('btn-outline-primary', !selected);
                            capsule.setAttribute('aria-pressed', selected ? 'true' : 'false');
                        });
                        refreshHostelResults(hostelFilterForm.action);
                    });

                    hostelFilterForm?.querySelectorAll('[data-hostel-status]').forEach((button) => {
                        button.addEventListener('click', () => {
                            document.getElementById('status').value = button.dataset.hostelStatus;
                            hostelFilterForm.querySelectorAll('[data-hostel-status]').forEach((capsule) => {
                                const selected = capsule === button;
                                capsule.classList.toggle('btn-primary', selected);
                                capsule.classList.toggle('btn-outline-primary', !selected);
                                capsule.setAttribute('aria-pressed', selected ? 'true' : 'false');
                            });
                            hostelFilterForm.requestSubmit();
                        });
                    });

                    hostelSearch?.addEventListener('input', () => {
                        window.clearTimeout(hostelSearchTimeout);
                        hostelSearchTimeout = window.setTimeout(() => hostelFilterForm?.requestSubmit(), 1000);
                    });

                    document.addEventListener('click', (event) => {
                        const pageLink = event.target.closest?.('#hostel-reservation-results .pagination a');
                        if (!pageLink) return;

                        event.preventDefault();
                        refreshHostelResults(pageLink.href);
                    });
                </script>
            @endpush

            <div id="hostel-reservation-results">
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
                        @forelse($reservations as $reservation)
                            <tr>
                                <td>{{ $reservation->reference }}</td>
                                <td>{{ $reservation->hostelStay?->guest_name ?? '—' }}</td>
                                <td>{{ $reservation->hostelStay?->roomType?->name ?? '—' }}</td>
                                <td>{{ $reservation->hostelStay?->check_in_at?->timezone(config('reservations.display_timezone'))->format('d M Y') }}</td>
                                <td>{{ $reservation->hostelStay?->check_out_at?->timezone(config('reservations.display_timezone'))->format('d M Y') }}</td>
                                <td><span class="badge rounded-pill px-3 {{ $reservation->statusEnum()->badgeClass() }}">{{ $reservation->statusEnum()->label() }}</span></td>
                                <td class="text-end">
                                    <div class="d-flex flex-wrap justify-content-end align-items-center gap-1">
                                    @can('viewHostel', $reservation)
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#viewModal-{{ $reservation->id }}" aria-label="View reservation {{ $reservation->reference }}">View</button>
                                    @endcan
                                    @can('manageHostel', $reservation)
                                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approveModal-{{ $reservation->id }}" aria-label="Approve reservation {{ $reservation->reference }}">
                                            Approve
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#rejectModal-{{ $reservation->id }}" aria-label="Reject reservation {{ $reservation->reference }}">
                                            Reject
                                        </button>
                                    @endcan
                                    @can('cancelHostel', $reservation)
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#cancelModal-{{ $reservation->id }}">Cancel</button>
                                    @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="ti ti-bed d-block fs-7 mb-2" aria-hidden="true"></i>
                                No reservations found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">{{ $reservations->links() }}</div>
            </div>
        </div>
    </div>
</div>

<div id="hostel-reservation-modals">
@foreach($reservations as $reservation)
    @can('viewHostel', $reservation)
        <div class="modal fade" id="viewModal-{{ $reservation->id }}" tabindex="-1" aria-labelledby="viewModalTitle-{{ $reservation->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-light">
                        <div>
                            <h2 class="modal-title fs-5 mb-1" id="viewModalTitle-{{ $reservation->id }}">{{ $reservation->reference }}</h2>
                            <div class="small text-muted">{{ $reservation->title }}</div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge rounded-pill px-3 {{ $reservation->statusEnum()->badgeClass() }}">{{ $reservation->statusEnum()->label() }}</span>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row g-4">
                            <section class="col-lg-7" aria-labelledby="stayDetails-{{ $reservation->id }}">
                                <h3 class="fs-6 mb-3" id="stayDetails-{{ $reservation->id }}">Stay details</h3>
                                <div class="row g-3">
                                    <div class="col-sm-6"><div class="small text-muted">Guest name</div><div class="fw-medium">{{ $reservation->hostelStay?->guest_name ?: '—' }}</div></div>
                                    <div class="col-sm-6"><div class="small text-muted">Room</div><div class="fw-medium">{{ $reservation->resourceNames() ?: '—' }}</div></div>
                                    <div class="col-sm-6"><div class="small text-muted">Room category</div><div class="fw-medium">{{ $reservation->hostelStay?->roomType?->name ?: '—' }}</div></div>
                                    <div class="col-sm-6"><div class="small text-muted">Location</div><div class="fw-medium">{{ $reservation->location?->name ?: '—' }}</div></div>
                                    <div class="col-sm-6"><div class="small text-muted">Check-in</div><div class="fw-medium">{{ $reservation->hostelStay?->check_in_at?->timezone(config('reservations.display_timezone'))->format('d M Y H:i') ?: '—' }}</div></div>
                                    <div class="col-sm-6"><div class="small text-muted">Check-out</div><div class="fw-medium">{{ $reservation->hostelStay?->check_out_at?->timezone(config('reservations.display_timezone'))->format('d M Y H:i') ?: '—' }}</div></div>
                                    <div class="col-sm-6"><div class="small text-muted">Guests</div><div class="fw-medium">{{ $reservation->hostelStay?->number_of_guests ?: '—' }}</div></div>
                                    <div class="col-sm-6"><div class="small text-muted">Requester</div><div class="fw-medium">{{ $reservation->requester?->name ?: '—' }}</div></div>
                                    <div class="col-12"><div class="small text-muted">Special requirements</div><div class="fw-medium">{{ $reservation->hostelStay?->special_requirements ?: '—' }}</div></div>
                                    @if($reservation->cancellation_reason)
                                        <div class="col-12"><div class="small text-muted">Cancellation reason</div><div class="fw-medium">{{ $reservation->cancellation_reason }}</div></div>
                                    @endif
                                </div>
                            </section>
                            <section class="col-lg-5" aria-labelledby="statusHistory-{{ $reservation->id }}">
                                <h3 class="fs-6 mb-3" id="statusHistory-{{ $reservation->id }}">Status history</h3>
                                @forelse($reservation->statusHistory as $history)
                                    <div class="border-start border-2 ps-3 pb-3 mb-3">
                                        <div class="fw-medium">{{ $history->from_status ? $history->from_status.' → ' : '' }}{{ $history->to_status }}</div>
                                        <div class="small text-muted">{{ $history->actor?->name ?? 'System' }} · {{ $history->created_at?->timezone(config('reservations.display_timezone'))->format('d M Y H:i') }}</div>
                                    </div>
                                @empty
                                    <p class="small text-muted mb-0">No history recorded.</p>
                                @endforelse
                            </section>
                        </div>
                        @can('cancelHostel', $reservation)
                            <section class="border-top mt-4 pt-4" aria-labelledby="cancelReservation-{{ $reservation->id }}">
                                <h3 class="fs-6 mb-3" id="cancelReservation-{{ $reservation->id }}">Cancel reservation</h3>
                                <form method="POST" action="{{ route('hostel.cancel', $reservation) }}" class="row g-2 align-items-end">
                                    @csrf
                                    <div class="col-12 col-md">
                                        <label for="cancellationReason-{{ $reservation->id }}" class="visually-hidden">Cancellation reason</label>
                                        <textarea id="cancellationReason-{{ $reservation->id }}" name="cancellation_reason" class="form-control" rows="2" maxlength="1000" required placeholder="Reason is required"></textarea>
                                    </div>
                                    <div class="col-12 col-md-auto">
                                        <button type="submit" class="btn btn-danger">Cancel reservation</button>
                                    </div>
                                </form>
                            </section>
                        @endcan
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endcan
    @can('manageHostel', $reservation)
        <div class="modal fade" id="approveModal-{{ $reservation->id }}" tabindex="-1" aria-labelledby="approveModalTitle-{{ $reservation->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('hostel.approval', $reservation) }}" class="modal-content">
                    @csrf
                    <input type="hidden" name="status" value="approved">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="approveModalTitle-{{ $reservation->id }}">Approve reservation?</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-1"><strong>{{ $reservation->reference }}</strong> · {{ $reservation->hostelStay?->guest_name }}</p>
                        <p class="small text-muted mb-0">Approving confirms the room for {{ $reservation->hostelStay?->check_in_at?->timezone(config('reservations.display_timezone'))->format('d M Y') }} to {{ $reservation->hostelStay?->check_out_at?->timezone(config('reservations.display_timezone'))->format('d M Y') }}.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Keep pending</button>
                        <button type="submit" class="btn btn-success">Confirm approval</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="modal fade" id="rejectModal-{{ $reservation->id }}" tabindex="-1" aria-labelledby="rejectModalTitle-{{ $reservation->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('hostel.approval', $reservation) }}" class="modal-content">
                    @csrf
                    <input type="hidden" name="status" value="rejected">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="rejectModalTitle-{{ $reservation->id }}">Reject reservation?</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3"><strong>{{ $reservation->reference }}</strong> · {{ $reservation->hostelStay?->guest_name }}</p>
                        <label for="rejectReason-{{ $reservation->id }}" class="form-label">Reason for rejection</label>
                        <textarea id="rejectReason-{{ $reservation->id }}" name="reason" class="form-control" rows="3" maxlength="1000" required></textarea>
                        <div class="form-text">The room will be released, and this reason will be saved in the reservation history.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Keep pending</button>
                        <button type="submit" class="btn btn-danger">Confirm rejection</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
    @can('cancelHostel', $reservation)
        <div class="modal fade" id="cancelModal-{{ $reservation->id }}" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" action="{{ route('hostel.cancel', $reservation) }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Cancel {{ $reservation->reference }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label">Cancellation reason</label>
                        <textarea name="cancellation_reason" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button class="btn btn-danger">Cancel reservation</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endforeach
</div>
@endsection
