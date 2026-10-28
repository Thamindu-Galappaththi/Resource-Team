@extends('layouts.app')

@section('title', 'Hostel Reservations')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/create-user.css') }}?v=15">
<link rel="stylesheet" href="{{ asset('css/hostel-index.css') }}?v=6">
@endpush

@section('content')
@php
    $tz = config('reservations.display_timezone');
    $hasFilters = collect(['search', 'check_in_from', 'check_in_to', 'room_type_id', 'status'])->contains(fn ($key) => filled(request($key)));
    $selectedRoomType = $roomTypes->firstWhere('id', (int) request('room_type_id'));
    $selectedStatus = collect($statuses)->first(fn ($status) => $status->value === request('status'));
@endphp
<div class="container-fluid mt-4 hr-page">
    <div class="hr-header">
        <div>
            <h1 class="hr-title">Hostel Reservations</h1>
            <p class="hr-subtitle mb-0">Manage student and guest accommodation across campus.</p>
        </div>
        <div class="hr-header-actions">
            @can('createHostel', \App\Models\Reservation::class)
                <a href="{{ route('hostel.create') }}" class="btn hr-create-btn"><i class="ti ti-plus me-1"></i>New Reservation</a>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success hr-auto-alert" role="status">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger hr-auto-alert" role="alert">{{ $errors->first() }}</div>
    @endif
    <div id="hr-flash" class="alert hr-auto-alert d-none" role="status"></div>

            <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="hr-stat p-3 p-md-4" style="--stat-color:#1769c2;--stat-icon-bg:#e8f0fa">
                <span class="hr-stat-icon"><i class="ti ti-bed"></i></span>
                <div class="hr-stat-label mt-3">Total bookings</div>
                <div class="hr-stat-number">{{ number_format($summary['total']) }}</div>
                        </div>
                    </div>
        <div class="col-6 col-lg-3">
            <div class="hr-stat p-3 p-md-4" style="--stat-color:#d97706;--stat-icon-bg:#fff4e5">
                <span class="hr-stat-icon"><i class="ti ti-login"></i></span>
                <div class="hr-stat-label mt-3">Check-ins today</div>
                <div class="hr-stat-number">{{ number_format($summary['check_ins_today']) }}</div>
            </div>
                        </div>
        <div class="col-6 col-lg-3">
            <div class="hr-stat p-3 p-md-4" style="--stat-color:#16a77a;--stat-icon-bg:#e7f6f0">
                <span class="hr-stat-icon"><i class="ti ti-door-exit"></i></span>
                <div class="hr-stat-label mt-3">Available rooms</div>
                <div class="hr-stat-number">{{ number_format($summary['available_rooms']) }}</div>
                        </div>
                        </div>
        <div class="col-6 col-lg-3">
            <div class="hr-stat p-3 p-md-4" style="--stat-color:#dc3545;--stat-icon-bg:#fdecee">
                <span class="hr-stat-icon"><i class="ti ti-clock-hour-4"></i></span>
                <div class="hr-stat-label mt-3">Pending requests</div>
                <div class="hr-stat-number">{{ number_format($summary['pending']) }}</div>
                        </div>
                        </div>
                    </div>

    <form id="hr-filters" class="hr-filters" method="GET" action="{{ route('hostel.index') }}">
        <div class="hr-filter hr-f-search">
            <label for="search">Search</label>
            <div class="input-group hr-search-group">
                <span class="input-group-text"><i class="ti ti-search"></i></span>
                <input type="text" id="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="ID or guest" autocomplete="off">
            </div>
        </div>
        <div class="hr-filter">
            <label for="check_in_from">Check-in from</label>
            <input type="date" id="check_in_from" name="check_in_from" class="form-control" value="{{ request('check_in_from') }}">
        </div>
        <div class="hr-filter">
            <label for="check_in_to">Check-in to</label>
            <input type="date" id="check_in_to" name="check_in_to" class="form-control" value="{{ request('check_in_to') }}">
        </div>
        <div class="hr-filter">
            <label for="room_type_id">Room category</label>
            <x-rm-select id="room_type_id" name="room_type_id" placeholder="All categories" :value="(string) request('room_type_id', '')" :selected-label="$selectedRoomType->name ?? 'All categories'"
                :options="collect([['value' => '', 'label' => 'All categories']])->concat($roomTypes->map(fn ($type) => ['value' => (string) $type->id, 'label' => $type->name]))->all()" />
        </div>
        <div class="hr-filter">
            <label for="status">Status</label>
            <x-rm-select id="status" name="status" placeholder="All statuses" :value="(string) request('status', '')" :selected-label="$selectedStatus?->label() ?? 'All statuses'"
                :options="collect([['value' => '', 'label' => 'All statuses']])->concat(collect($statuses)->map(fn ($status) => ['value' => $status->value, 'label' => $status->label()]))->all()" />
        </div>
        <div class="hr-filter-actions">
            <button class="hr-filter-clear" type="button" id="hr-clear" @disabled(! $hasFilters)>Clear</button>
        </div>
    </form>

    <div class="card border-0 hr-panel">
        <div class="card-body position-relative">
            <div id="hr-loading" class="hr-loading" hidden>Updating…</div>
            <div id="hr-results">
                @if($reservations->isEmpty())
                    <div class="hr-empty">
                        <i class="ti ti-bed d-block mb-2"></i>
                        <p class="mb-0 fw-semibold">No reservations found.</p>
                        <p class="small mb-0">Try a different search or clear the filters.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table hr-table align-middle mb-0">
                    <caption class="visually-hidden">Hostel reservations</caption>
                    <thead>
                        <tr>
                                    <th>Reservation ID</th>
                                    <th>Guest name</th>
                                    <th>Room category</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                                @foreach($reservations as $reservation)
                                    <tr>
                                        <td class="hr-ref" data-label="Reservation ID">{{ $reservation->reference }}</td>
                                        <td data-label="Guest name">{{ $reservation->hostelStay?->guest_name ?? '—' }}</td>
                                        <td data-label="Room category">{{ $reservation->hostelStay?->roomType?->name ?? '—' }}</td>
                                        <td data-label="Check-in">{{ $reservation->hostelStay?->check_in_at?->timezone($tz)->format('d M Y') ?: '—' }}</td>
                                        <td data-label="Check-out">{{ $reservation->hostelStay?->check_out_at?->timezone($tz)->format('d M Y') ?: '—' }}</td>
                                        <td data-label="Status"><span class="badge {{ $reservation->statusEnum()->badgeClass() }}">{{ $reservation->statusEnum()->label() }}</span></td>
                                        <td class="text-end hr-actions" data-label="Actions">
                                            @php
                                                $canView = auth()->user()?->can('viewHostel', $reservation) ?? false;
                                                $canManage = auth()->user()?->can('manageHostel', $reservation) ?? false;
                                                $canCancel = auth()->user()?->can('cancelHostel', $reservation) ?? false;
                                            @endphp
                                            <span class="hr-action-group">
                                                <button type="button" class="btn btn-sm btn-outline-secondary hr-icon-btn" @if($canView) data-bs-toggle="modal" data-bs-target="#viewModal-{{ $reservation->id }}" title="View" aria-label="View reservation {{ $reservation->reference }}" @else disabled aria-disabled="true" title="View unavailable" @endif><i class="ti ti-eye"></i></button>
                                                <button type="button" class="btn btn-sm btn-outline-success hr-icon-btn" @if($canManage) data-bs-toggle="modal" data-bs-target="#approveModal-{{ $reservation->id }}" title="Approve" aria-label="Approve reservation {{ $reservation->reference }}" @else disabled aria-disabled="true" title="Approve unavailable" @endif><i class="ti ti-check"></i></button>
                                                <button type="button" class="btn btn-sm btn-outline-danger hr-icon-btn" @if($canManage) data-bs-toggle="modal" data-bs-target="#rejectModal-{{ $reservation->id }}" title="Reject" aria-label="Reject reservation {{ $reservation->reference }}" @else disabled aria-disabled="true" title="Reject unavailable" @endif><i class="ti ti-x"></i></button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary hr-icon-btn" @if($canCancel) data-bs-toggle="modal" data-bs-target="#cancelModal-{{ $reservation->id }}" title="Cancel" aria-label="Cancel reservation {{ $reservation->reference }}" @else disabled aria-disabled="true" title="Cancel unavailable" @endif><i class="ti ti-ban"></i></button>
                                            </span>
                            </td>
                        </tr>
                                @endforeach
                    </tbody>
                </table>
                    </div>
                    @if($reservations->hasPages())
                        <div class="hr-pager">
                            <p class="small text-muted mb-0">Showing {{ $reservations->firstItem() }}–{{ $reservations->lastItem() }} of {{ $reservations->total() }}</p>
                            <ul class="pagination mb-0">
                                <li class="page-item {{ $reservations->onFirstPage() ? 'disabled' : '' }}">
                                    @if($reservations->onFirstPage())
                                        <span class="page-link">‹</span>
                                    @else
                                        <a class="page-link" href="{{ $reservations->previousPageUrl() }}">‹</a>
                                    @endif
                                </li>
                                @for($page = 1; $page <= $reservations->lastPage(); $page++)
                                    <li class="page-item {{ $page === $reservations->currentPage() ? 'active' : '' }}">
                                        <a class="page-link" href="{{ $reservations->url($page) }}">{{ $page }}</a>
                                    </li>
                                @endfor
                                <li class="page-item {{ $reservations->hasMorePages() ? '' : 'disabled' }}">
                                    @if($reservations->hasMorePages())
                                        <a class="page-link" href="{{ $reservations->nextPageUrl() }}">›</a>
                                    @else
                                        <span class="page-link">›</span>
                                    @endif
                                </li>
                            </ul>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

<div id="hostel-reservation-modals">
@foreach($reservations as $reservation)
    @can('viewHostel', $reservation)
        <div class="modal fade hr-modal" id="viewModal-{{ $reservation->id }}" tabindex="-1" aria-labelledby="viewModalTitle-{{ $reservation->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <span class="hr-modal-icon" aria-hidden="true"><i class="ti ti-bed"></i></span>
                        <div>
                            <h2 class="modal-title fs-5" id="viewModalTitle-{{ $reservation->id }}">{{ $reservation->reference }}</h2>
                            <p class="hr-modal-sub mb-0">{{ $reservation->title }}</p>
                        </div>
                        <span class="badge {{ $reservation->statusEnum()->badgeClass() }}">{{ $reservation->statusEnum()->label() }}</span>
                        <button type="button" class="hr-modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-4">
                            <section class="col-lg-7" aria-labelledby="stayDetails-{{ $reservation->id }}">
                                <h3 class="hr-section-title" id="stayDetails-{{ $reservation->id }}">Stay details</h3>
                                <div class="hr-details">
                                    <div class="hr-detail-row"><span class="hr-detail-label">Guest name</span><span class="hr-detail-value">{{ $reservation->hostelStay?->guest_name ?: '—' }}</span></div>
                                    <div class="hr-detail-row"><span class="hr-detail-label">Room</span><span class="hr-detail-value">{{ $reservation->resourceNames() ?: '—' }}</span></div>
                                    <div class="hr-detail-row"><span class="hr-detail-label">Room category</span><span class="hr-detail-value">{{ $reservation->hostelStay?->roomType?->name ?: '—' }}</span></div>
                                    <div class="hr-detail-row"><span class="hr-detail-label">Location</span><span class="hr-detail-value">{{ $reservation->location?->name ?: '—' }}</span></div>
                                    <div class="hr-detail-row"><span class="hr-detail-label">Check-in</span><span class="hr-detail-value">{{ $reservation->hostelStay?->check_in_at?->timezone($tz)->format('d M Y H:i') ?: '—' }}</span></div>
                                    <div class="hr-detail-row"><span class="hr-detail-label">Check-out</span><span class="hr-detail-value">{{ $reservation->hostelStay?->check_out_at?->timezone($tz)->format('d M Y H:i') ?: '—' }}</span></div>
                                    <div class="hr-detail-row"><span class="hr-detail-label">Guests</span><span class="hr-detail-value">{{ $reservation->hostelStay?->number_of_guests ?: '—' }}</span></div>
                                    <div class="hr-detail-row"><span class="hr-detail-label">Requester</span><span class="hr-detail-value">{{ $reservation->requester?->name ?: '—' }}</span></div>
                                    <div class="hr-detail-row"><span class="hr-detail-label">Requirements</span><span class="hr-detail-value">{{ $reservation->hostelStay?->special_requirements ?: '—' }}</span></div>
                                    @if($reservation->cancellation_reason)
                                        <div class="hr-detail-row"><span class="hr-detail-label">Cancel reason</span><span class="hr-detail-value">{{ $reservation->cancellation_reason }}</span></div>
                                    @endif
                                </div>
                            </section>
                            <section class="col-lg-5" aria-labelledby="statusHistory-{{ $reservation->id }}">
                                <h3 class="hr-section-title" id="statusHistory-{{ $reservation->id }}">Status history</h3>
                                @forelse($reservation->statusHistory as $history)
                                    <div class="hr-history">
                                        <div class="hr-history-title">{{ $history->from_status ? $history->from_status.' → ' : '' }}{{ $history->to_status }}</div>
                                        <div class="hr-history-meta">{{ $history->actor?->name ?? 'System' }} · {{ $history->created_at?->timezone($tz)->format('d M Y H:i') }}</div>
                                    </div>
                                @empty
                                    <p class="small text-muted mb-0">No history recorded.</p>
                                @endforelse
                            </section>
                        </div>
                    </div>
                    <div class="modal-footer">
                        @can('cancelHostel', $reservation)
                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal-{{ $reservation->id }}" data-bs-dismiss="modal">Cancel reservation</button>
                        @endcan
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    @endcan
    @can('manageHostel', $reservation)
        <div class="modal fade hr-modal" id="approveModal-{{ $reservation->id }}" tabindex="-1" aria-labelledby="approveModalTitle-{{ $reservation->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('hostel.approval', $reservation) }}" class="modal-content">
                    @csrf
                    <input type="hidden" name="status" value="approved">
                    <div class="modal-header">
                        <span class="hr-modal-icon" aria-hidden="true"><i class="ti ti-circle-check"></i></span>
                        <div>
                            <h2 class="modal-title fs-5" id="approveModalTitle-{{ $reservation->id }}">Approve reservation?</h2>
                            <p class="hr-modal-sub mb-0">{{ $reservation->reference }} · {{ $reservation->hostelStay?->guest_name }}</p>
                        </div>
                        <button type="button" class="hr-modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">Approving confirms the room for {{ $reservation->hostelStay?->check_in_at?->timezone($tz)->format('d M Y') }} to {{ $reservation->hostelStay?->check_out_at?->timezone($tz)->format('d M Y') }}.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Keep pending</button>
                        <button type="submit" class="btn btn-success">Confirm approval</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="modal fade hr-modal" id="rejectModal-{{ $reservation->id }}" tabindex="-1" aria-labelledby="rejectModalTitle-{{ $reservation->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('hostel.approval', $reservation) }}" class="modal-content">
                    @csrf
                    <input type="hidden" name="status" value="rejected">
                    <div class="modal-header">
                        <span class="hr-modal-icon" aria-hidden="true"><i class="ti ti-ban"></i></span>
                        <div>
                            <h2 class="modal-title fs-5" id="rejectModalTitle-{{ $reservation->id }}">Reject reservation?</h2>
                            <p class="hr-modal-sub mb-0">{{ $reservation->reference }} · {{ $reservation->hostelStay?->guest_name }}</p>
                        </div>
                        <button type="button" class="hr-modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                    </div>
                    <div class="modal-body">
                        <label for="rejectReason-{{ $reservation->id }}" class="form-label">Reason for rejection</label>
                        <textarea id="rejectReason-{{ $reservation->id }}" name="reason" class="form-control" rows="3" maxlength="1000" required></textarea>
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
        <div class="modal fade hr-modal" id="cancelModal-{{ $reservation->id }}" tabindex="-1" aria-labelledby="cancelModalTitle-{{ $reservation->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <form method="POST" action="{{ route('hostel.cancel', $reservation) }}" class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <span class="hr-modal-icon" aria-hidden="true"><i class="ti ti-ban"></i></span>
                        <div>
                            <h2 class="modal-title fs-5" id="cancelModalTitle-{{ $reservation->id }}">Cancel {{ $reservation->reference }}</h2>
                            <p class="hr-modal-sub mb-0">This will release the booked room.</p>
                        </div>
                        <button type="button" class="hr-modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label" for="cancelReason-{{ $reservation->id }}">Cancellation reason</label>
                        <textarea id="cancelReason-{{ $reservation->id }}" name="cancellation_reason" class="form-control" rows="3" required placeholder="Reason is required"></textarea>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Keep reservation</button>
                        <button type="submit" class="btn btn-danger">Cancel reservation</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endforeach
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/resource-select.js') }}?v=2"></script>
<script src="{{ asset('js/cu-dropdowns.js') }}?v=4"></script>
<script src="{{ asset('js/hostel-index.js') }}?v=1"></script>
@endpush
