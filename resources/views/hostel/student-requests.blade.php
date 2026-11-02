@extends('layouts.app')

@section('title', 'Student Hostel Requests')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/create-user.css') }}?v=15">
<link rel="stylesheet" href="{{ asset('css/hostel-index.css') }}?v=6">
<link rel="stylesheet" href="{{ asset('css/hostel-student-requests.css') }}?v=1">
@endpush

@section('content')
@php
    $tz = config('reservations.display_timezone');
    $selectedStatus = collect($statuses)->first(fn ($status) => $status->value === request('status'));
    $hasFilters = filled(request('search')) || $selectedStatus;
    $shareText = 'Nebula Hostel: request a hostel reservation using this form: ';
@endphp
<div class="container-fluid mt-4 hr-page">
    <div class="hr-header">
        <div>
            <h1 class="hr-title">Student Hostel Requests</h1>
            <p class="hr-subtitle mb-0">Share one link with students, review their requests, and send the decision by SMS.</p>
        </div>
        <div class="hr-header-actions">
            <a href="{{ route('hostel.index') }}" class="btn hr-create-btn"><i class="ti ti-building-community me-1"></i>Hostel Reservations</a>
        </div>
    </div>

    @foreach(['success' => 'success', 'warning' => 'warning'] as $key => $tone)
        @if(session($key))
            <div class="alert alert-{{ $tone }} hr-auto-alert" role="status">{{ session($key) }}</div>
        @endif
    @endforeach
    @if($errors->any())
        <div class="alert alert-danger hr-auto-alert" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="hsr-link-card mb-4" aria-labelledby="hsr-link-title">
        <div class="hsr-link-head">
            <span class="hsr-link-icon" aria-hidden="true"><i class="ti ti-link"></i></span>
            <div>
                <h2 id="hsr-link-title" class="hsr-link-title">Student request link</h2>
                <p class="hsr-link-sub mb-0">This link never changes. Send the same link to every student by WhatsApp or copy and paste.</p>
            </div>
            @if($pendingCount > 0)
                <span class="badge bg-warning text-dark ms-auto">{{ $pendingCount }} pending</span>
            @endif
        </div>
        <div class="hsr-link-body">
            @if($link)
                <div class="hsr-link-row">
                    <div class="input-group hsr-link-input">
                        <span class="input-group-text"><i class="ti ti-world"></i></span>
                        <input type="text" id="hsr-share-url" class="form-control" value="{{ $link->url() }}" readonly aria-label="Student request link">
                    </div>
                    <button type="button" class="btn hsr-btn hsr-btn-copy" id="hsr-copy" data-copy-target="hsr-share-url"><i class="ti ti-copy me-1"></i><span>Copy link</span></button>
                    <a class="btn hsr-btn hsr-btn-whatsapp" href="https://wa.me/?text={{ rawurlencode($shareText.$link->url()) }}" target="_blank" rel="noopener"><i class="ti ti-brand-whatsapp me-1"></i>Share on WhatsApp</a>
                    <a class="btn hsr-btn hsr-btn-open" href="{{ $link->url() }}" target="_blank" rel="noopener"><i class="ti ti-external-link me-1"></i>Open form</a>
                </div>
                <p class="hsr-link-meta mb-0">Created {{ $link->created_at?->timezone($tz)->format('d M Y') }}{{ $link->createdBy ? ' by '.$link->createdBy->name : '' }}.</p>
            @else
                <div class="hsr-link-empty">
                    <p class="mb-0">No link yet. Generate it once and share it with students.</p>
                    <form method="POST" action="{{ route('hostel.student-requests.link') }}">
                        @csrf
                        <button type="submit" class="btn hsr-btn hsr-btn-copy"><i class="ti ti-link-plus me-1"></i>Generate link</button>
                    </form>
                </div>
            @endif
        </div>
    </section>

    <form id="hsr-filters" class="hr-filters" method="GET" action="{{ route('hostel.student-requests.index') }}" data-action-lock="off">
        <div class="hr-filter hr-f-search">
            <label for="search">Search</label>
            <div class="input-group hr-search-group">
                <span class="input-group-text"><i class="ti ti-search"></i></span>
                <input type="text" id="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Reference, student, ID, phone, or town" autocomplete="off">
            </div>
        </div>
        <div class="hr-filter">
            <label for="status">Status</label>
            <x-rm-select id="status" name="status" placeholder="All statuses" :value="(string) request('status', '')" :selected-label="$selectedStatus?->label() ?? 'All statuses'"
                :options="collect([['value' => '', 'label' => 'All statuses']])->concat(collect($statuses)->map(fn ($status) => ['value' => $status->value, 'label' => $status->label()]))->all()" />
        </div>
        <div class="hr-filter-actions">
            <a href="{{ route('hostel.student-requests.index') }}" class="hr-filter-clear {{ $hasFilters ? '' : 'disabled' }}" @unless($hasFilters) aria-disabled="true" tabindex="-1" @endunless>Clear</a>
        </div>
    </form>

    <div class="card border-0 hr-panel">
        <div class="card-body">
            @if($requests->isEmpty())
                <div class="hr-empty">
                    <i class="ti ti-school d-block mb-2"></i>
                    <p class="mb-0 fw-semibold">No student requests found.</p>
                    <p class="small mb-0">{{ $hasFilters ? 'Try a different search or clear the filters.' : 'Requests appear here when students submit the shared form.' }}</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table hr-table align-middle mb-0">
                        <caption class="visually-hidden">Student hostel requests</caption>
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Student</th>
                                <th>Student ID</th>
                                <th>Phone</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($requests as $studentRequest)
                                @php $next = collect($studentRequest->status->nextStatuses())->map->value; @endphp
                                <tr>
                                    <td class="hr-ref" data-label="Reference">{{ $studentRequest->reference }}</td>
                                    <td data-label="Student">{{ $studentRequest->student_name }}</td>
                                    <td data-label="Student ID">{{ $studentRequest->student_id }}</td>
                                    <td data-label="Phone">{{ $studentRequest->phone }}</td>
                                    <td data-label="Check-in">{{ $studentRequest->checkInLabel() }}</td>
                                    <td data-label="Check-out">{{ $studentRequest->checkOutLabel() }}</td>
                                    <td data-label="Status"><span class="badge {{ $studentRequest->status->badgeClass() }}">{{ $studentRequest->status->label() }}</span></td>
                                    <td class="text-end hr-actions" data-label="Actions">
                                        <span class="hr-action-group">
                                            <button type="button" class="btn btn-sm btn-outline-secondary hr-icon-btn" data-bs-toggle="modal" data-bs-target="#hsrView-{{ $studentRequest->id }}" title="View" aria-label="View {{ $studentRequest->reference }}"><i class="ti ti-eye"></i></button>
                                            @foreach([
                                                ['approved', 'btn-outline-success', 'ti-check', 'Approve'],
                                                ['rejected', 'btn-outline-danger', 'ti-x', 'Reject'],
                                                ['cancelled', 'btn-outline-secondary', 'ti-ban', 'Cancel'],
                                            ] as [$value, $tone, $icon, $label])
                                                @if($next->contains($value))
                                                    <button type="button" class="btn btn-sm {{ $tone }} hr-icon-btn" data-bs-toggle="modal" data-bs-target="#hsrDecision"
                                                        data-hsr-action="{{ route('hostel.student-requests.decision', $studentRequest) }}" data-hsr-status="{{ $value }}" data-hsr-label="{{ $label }}"
                                                        data-hsr-ref="{{ $studentRequest->reference }}" data-hsr-student="{{ $studentRequest->student_name }}" data-hsr-phone="{{ $studentRequest->phone }}"
                                                        title="{{ $label }}" aria-label="{{ $label }} {{ $studentRequest->reference }}"><i class="ti {{ $icon }}"></i></button>
                                                @else
                                                    <button type="button" class="btn btn-sm {{ $tone }} hr-icon-btn" disabled aria-disabled="true" title="{{ $label }} unavailable"><i class="ti {{ $icon }}"></i></button>
                                                @endif
                                            @endforeach
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($requests->hasPages())
                    <div class="hr-pager">
                        <p class="small text-muted mb-0">Showing {{ $requests->firstItem() }}–{{ $requests->lastItem() }} of {{ $requests->total() }}</p>
                        {{ $requests->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

@foreach($requests as $studentRequest)
    <div class="modal fade hr-modal" id="hsrView-{{ $studentRequest->id }}" tabindex="-1" aria-labelledby="hsrViewTitle-{{ $studentRequest->id }}" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <span class="hr-modal-icon" aria-hidden="true"><i class="ti ti-school"></i></span>
                    <div>
                        <h2 class="modal-title fs-5" id="hsrViewTitle-{{ $studentRequest->id }}">{{ $studentRequest->reference }}</h2>
                        <p class="hr-modal-sub mb-0">{{ $studentRequest->student_name }}</p>
                    </div>
                    <span class="badge {{ $studentRequest->status->badgeClass() }}">{{ $studentRequest->status->label() }}</span>
                    <button type="button" class="hr-modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
                </div>
                <div class="modal-body">
                    <div class="hr-details">
                        @foreach([
                            'Student name' => $studentRequest->student_name,
                            'Student ID' => $studentRequest->student_id,
                            'Phone number' => $studentRequest->phone,
                            'Home town' => $studentRequest->home_town,
                            'Check-in' => $studentRequest->checkInLabel(),
                            'Check-out' => $studentRequest->checkOutLabel(),
                            'Emergency contact' => $studentRequest->emergency_contact_name.' · '.$studentRequest->emergency_contact_phone,
                            'Submitted' => $studentRequest->created_at?->timezone($tz)->format('d M Y H:i'),
                            'Decision note' => $studentRequest->decision_note,
                            'Decided by' => $studentRequest->decidedBy ? $studentRequest->decidedBy->name.' · '.$studentRequest->decided_at?->timezone($tz)->format('d M Y H:i') : null,
                            'SMS' => $studentRequest->sms_status ? ucfirst($studentRequest->sms_status).($studentRequest->sms_sent_at ? ' · '.$studentRequest->sms_sent_at->timezone($tz)->format('d M Y H:i') : '') : null,
                        ] as $label => $value)
                            <div class="hr-detail-row"><span class="hr-detail-label">{{ $label }}</span><span class="hr-detail-value">{{ $value ?: '—' }}</span></div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endforeach

<div class="modal fade hr-modal" id="hsrDecision" tabindex="-1" aria-labelledby="hsrDecisionTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="" class="modal-content" id="hsrDecisionForm">
            @csrf
            <input type="hidden" name="status" value="">
            <div class="modal-header">
                <span class="hr-modal-icon" aria-hidden="true"><i class="ti ti-message-2"></i></span>
                <div>
                    <h2 class="modal-title fs-5" id="hsrDecisionTitle">Update request</h2>
                    <p class="hr-modal-sub mb-0" data-hsr-sub></p>
                </div>
                <button type="button" class="hr-modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
            </div>
            <div class="modal-body">
                <label for="hsrDecisionNote" class="form-label" data-hsr-note-label>Note for the student</label>
                <textarea id="hsrDecisionNote" name="decision_note" class="form-control" rows="3" maxlength="500" placeholder="e.g. Room 12 is reserved for you. Bring your student ID at check-in."></textarea>
                <p class="small text-muted mt-2 mb-0" data-hsr-sms></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Back</button>
                <button type="submit" class="btn btn-primary" data-hsr-submit>Confirm</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/resource-select.js') }}?v=2"></script>
<script src="{{ asset('js/cu-dropdowns.js') }}?v=4"></script>
<script src="{{ asset('js/hostel-student-requests.js') }}?v=2"></script>
@endpush
