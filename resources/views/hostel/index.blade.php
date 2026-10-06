@extends('layouts.app')

@section('title', 'Hostel Reservations')

@section('content')
<style>
    .hostel-reservations {
        max-width: 1440px;
        margin: 0 auto;
        padding: 24px;
        color: #111827;
    }
    .hostel-reservations h1 { font-size: 26px; font-weight: 800; }
    .hostel-reservations .page-subtitle { color: #6b7280; font-size: 14px; }
    .hostel-reservations > .card { background: transparent; box-shadow: none !important; }
    .hostel-reservations > .card > .card-body { padding: 0 !important; }
    .hostel-reservations .summary-card {
        height: 100%;
        padding: 16px;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, .04);
    }
    .hostel-reservations .summary-label { color: #6b7280; font-size: 12px; font-weight: 700; text-transform: uppercase; }
    .hostel-reservations .summary-value { color: #0f172a; font-size: 26px; font-weight: 900; }
    .hostel-reservations .filter-panel { background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; }
    .hostel-reservations .filter-panel .form-label { color: #374151; font-size: 12px; font-weight: 700; }
    .hostel-reservations .form-control, .hostel-reservations .form-select { border-color: #d1d5db; }
    .hostel-reservations .form-control:focus, .hostel-reservations .form-select:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, .15); }
    .hostel-reservations .status-capsules { display: flex; flex-wrap: wrap; gap: 8px; }
    .hostel-reservations .status-capsules .btn { border-radius: 999px; }
    .hostel-reservations .table-responsive { background: #fff; border-color: #e5e7eb !important; border-radius: 10px !important; }
    .hostel-reservations .table { color: #111827; }
    .hostel-reservations .table thead th { background: #f9fafb; color: #374151; font-size: 12px; font-weight: 800; text-transform: uppercase; white-space: nowrap; }
    .hostel-reservations .table td, .hostel-reservations .table th { padding: 12px 16px; border-bottom-color: #e5e7eb; }
    .hostel-reservations .table tbody tr:hover { background: #f9fafb; }
    .hostel-reservations .check-ins { border-top: 3px solid #13aacb; }
    @media (max-width: 575.98px) {
        .hostel-reservations { padding: 16px; }
    }
</style>
<div class="hostel-reservations py-4">
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
                        <a href="{{ route('hostel.index') }}" class="btn btn-light">Reset</a>
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
