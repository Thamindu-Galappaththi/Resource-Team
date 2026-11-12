@extends('layouts.app')

@section('title', 'Lecture Fees')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/create-user.css') }}?v=15">
<link rel="stylesheet" href="{{ asset('css/reservation-index.css') }}?v=18">
<link rel="stylesheet" href="{{ asset('css/resource-calendar.css') }}?v=38">
<link rel="stylesheet" href="{{ asset('css/resource-payments.css') }}?v=48">
<link rel="stylesheet" href="{{ asset('css/lecture-fees.css') }}?v=4">
@endpush

@section('content')
@php
    $typeOptions = collect($types)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values();
    $courseOptions = collect($courseLevels)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values();
    $qualificationOptions = collect($qualifications)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values();
    $statusOptions = [
        ['value' => 'active', 'label' => 'Active'],
        ['value' => 'inactive', 'label' => 'Inactive'],
    ];
    $filterTypeOptions = collect([['value' => '', 'label' => 'All lecture types']])->concat($typeOptions)->all();
    $filterCourseOptions = collect([['value' => '', 'label' => 'All course levels']])->concat($courseOptions)->all();
    $filterQualificationOptions = collect([['value' => '', 'label' => 'All qualifications']])->concat($qualificationOptions)->all();
    $filterStatusOptions = [
        ['value' => 'all', 'label' => 'All statuses'],
        ['value' => 'active', 'label' => 'Active'],
        ['value' => 'inactive', 'label' => 'Inactive'],
    ];
    $oldType = (string) old('user_type', '');
    $oldCourse = (string) old('department', '');
    $oldQualification = (string) old('qualification', '');
    $oldStatus = (string) old('status', 'active');
    $oldTypeLabel = $typeOptions->firstWhere('value', $oldType)['label'] ?? 'Select lecture type';
    $oldCourseLabel = $courseOptions->firstWhere('value', $oldCourse)['label'] ?? 'Select course level';
    $oldQualificationLabel = $qualificationOptions->firstWhere('value', $oldQualification)['label'] ?? 'Select qualification';
    $filterType = (string) ($filters['user_type'] ?? '');
    $filterCourse = (string) ($filters['department'] ?? '');
    $filterQualification = (string) ($filters['qualification'] ?? '');
    $filterStatus = (string) ($filters['status'] ?? 'all');
    $filterTypeLabel = collect($filterTypeOptions)->firstWhere('value', $filterType)['label'] ?? 'All lecture types';
    $filterCourseLabel = collect($filterCourseOptions)->firstWhere('value', $filterCourse)['label'] ?? 'All course levels';
    $filterQualificationLabel = collect($filterQualificationOptions)->firstWhere('value', $filterQualification)['label'] ?? 'All qualifications';
    $filterStatusLabel = collect($filterStatusOptions)->firstWhere('value', $filterStatus)['label'] ?? 'All statuses';
    $hasFilters = ($filters['search'] ?? '') !== '' || $filterType !== '' || $filterCourse !== '' || $filterQualification !== '' || $filterStatus !== 'all';
@endphp
<div class="container-fluid mt-4 rsv-page rp-page rr-admin lf-page" data-lf-url="{{ route('payments.lecture-fees') }}">
    <header class="rr-page-heading">
        <div>
            <h1>Lecture Fees</h1>
            <p>Assign hourly lecture prices for Human Resource lecturers from the CHRO lecture fee scheme.</p>
        </div>
        <a href="{{ route('payments.resources') }}" class="rr-back-link"><i class="ti ti-receipt"></i> Back to invoicing</a>
    </header>

    @if(session('success'))
        <div class="alert alert-success rsv-auto-alert" role="status">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger rsv-auto-alert" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="rr-stats rc-cards">
        <div class="rc-card" style="--stat-color:#1769c2;--stat-icon-bg:#e8f0fa">
            <span class="rc-card-icon" aria-hidden="true"><i class="ti ti-tag"></i></span>
            <div class="rc-card-label">Lecture rates</div>
            <div class="rc-card-value" id="lf-stat-total">{{ number_format($totalCount) }}</div>
        </div>
        <div class="rc-card" style="--stat-color:#16a77a;--stat-icon-bg:#e7f6f0">
            <span class="rc-card-icon" aria-hidden="true"><i class="ti ti-circle-check"></i></span>
            <div class="rc-card-label">Active</div>
            <div class="rc-card-value" id="lf-stat-active">{{ number_format($activeCount) }}</div>
        </div>
        <div class="rc-card" style="--stat-color:#6b7280;--stat-icon-bg:#eef1f4">
            <span class="rc-card-icon" aria-hidden="true"><i class="ti ti-ban"></i></span>
            <div class="rc-card-label">Inactive</div>
            <div class="rc-card-value" id="lf-stat-inactive">{{ number_format($inactiveCount) }}</div>
        </div>
        <div class="rc-card" style="--stat-color:#d97706;--stat-icon-bg:#fff4e5">
            <span class="rc-card-icon" aria-hidden="true"><i class="ti ti-users"></i></span>
            <div class="rc-card-label">HR lecturers</div>
            <div class="rc-card-value" id="lf-stat-lecturers">{{ number_format($lecturerCount) }}</div>
        </div>
    </div>

    <section class="card border-0 rsv-panel mb-4 rr-management" id="lf-add">
        <div class="rr-catalog-heading">
            <div>
                <span class="rp-title-icon"><i class="ti ti-plus"></i></span>
                <div>
                    <h2 class="rr-section-title">Assign lecture rate</h2>
                    <p class="rr-muted">Set the hourly price by lecture type, course level, and qualification. Active is preferred.</p>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('payments.lecture-fees.store') }}" class="rr-fields rr-add-fields" id="lf-add-form" data-action-lock="off" novalidate>
                @csrf
                <div class="rr-field">
                    <label class="form-label" for="lf-type">Lecture type <span class="text-danger">*</span></label>
                    <x-rm-select id="lf-type" name="user_type" placeholder="Select lecture type" :value="$oldType" :selected-label="$oldTypeLabel" :options="$typeOptions->all()" data-rr-required required />
                    <p class="rr-field-error"></p>
                </div>
                <div class="rr-field">
                    <label class="form-label" for="lf-course">Course level <span class="text-danger">*</span></label>
                    <x-rm-select id="lf-course" name="department" placeholder="Select course level" :value="$oldCourse" :selected-label="$oldCourseLabel" :options="$courseOptions->all()" data-rr-required required />
                    <p class="rr-field-error"></p>
                </div>
                <div class="rr-field">
                    <label class="form-label" for="lf-qualification">Qualification <span class="text-danger">*</span></label>
                    <x-rm-select id="lf-qualification" name="qualification" placeholder="Select qualification" :value="$oldQualification" :selected-label="$oldQualificationLabel" :options="$qualificationOptions->all()" data-rr-required required />
                    <p class="rr-field-error"></p>
                </div>
                <div class="rr-field">
                    <label class="form-label" for="lf-rate">Hourly rate (LKR) <span class="text-danger">*</span></label>
                    <input id="lf-rate" type="number" name="hourly_rate" class="form-control" min="0" step="0.01" value="{{ old('hourly_rate') }}" required data-rr-required>
                    <p class="rr-field-error"></p>
                </div>
                <div class="rr-field">
                    <label class="form-label" for="lf-status">Status</label>
                    <x-rm-select id="lf-status" name="status" placeholder="Active" :value="$oldStatus" :selected-label="$oldStatus === 'inactive' ? 'Inactive' : 'Active'" :options="$statusOptions" />
                </div>
                <div class="rr-field rr-field-action">
                    <button type="button" class="btn btn-primary rr-submit rr-submit-rate" id="lf-add-submit">Add lecture rate</button>
                </div>
            </form>
        </div>
    </section>

    <section class="card border-0 rsv-panel mb-4 rr-catalog-panel" id="lf-catalog-panel">
        <div class="card-body">
            <div class="rr-catalog-heading">
                <div>
                    <span class="rp-title-icon"><i class="ti ti-list-details"></i></span>
                    <div>
                        <h2 class="rr-section-title">Lecture rate catalog</h2>
                        <p class="rr-muted">Search and activate or deactivate hourly prices used for Human Resource lectures.</p>
                    </div>
                </div>
            </div>
            <form method="GET" action="{{ route('payments.lecture-fees') }}" class="rr-catalog-filters lf-filters" data-lf-search data-action-lock="off">
                <div class="rr-field rr-rate-search">
                    <label class="form-label" for="lf-search">Search</label>
                    <div class="input-group rsv-search-group rp-search-group">
                        <span class="input-group-text"><i class="ti ti-search"></i></span>
                        <input id="lf-search" type="search" name="search" class="form-control" placeholder="Search type, course, qualification" value="{{ $filters['search'] ?? '' }}" autocomplete="off">
                    </div>
                </div>
                <div class="rr-field">
                    <label class="form-label" for="lf-filter-type">Lecture type</label>
                    <x-rm-select id="lf-filter-type" name="user_type" placeholder="All lecture types" :value="$filterType" :selected-label="$filterTypeLabel" :options="$filterTypeOptions" />
                </div>
                <div class="rr-field">
                    <label class="form-label" for="lf-filter-course">Course level</label>
                    <x-rm-select id="lf-filter-course" name="department" placeholder="All course levels" :value="$filterCourse" :selected-label="$filterCourseLabel" :options="$filterCourseOptions" />
                </div>
                <div class="rr-field">
                    <label class="form-label" for="lf-filter-qualification">Qualification</label>
                    <x-rm-select id="lf-filter-qualification" name="qualification" placeholder="All qualifications" :value="$filterQualification" :selected-label="$filterQualificationLabel" :options="$filterQualificationOptions" />
                </div>
                <div class="rr-field">
                    <label class="form-label" for="lf-filter-status">Status</label>
                    <x-rm-select id="lf-filter-status" name="status" placeholder="All statuses" :value="$filterStatus" :selected-label="$filterStatusLabel" :options="$filterStatusOptions" />
                </div>
                <div class="rr-field rr-catalog-actions">
                    <label class="form-label rr-field-spacer" for="lf-clear">&nbsp;</label>
                    <div class="rr-catalog-actions-row">
                        <button type="button" class="rsv-filter-btn rsv-filter-clear" id="lf-clear" @disabled(! $hasFilters)>Clear</button>
                    </div>
                </div>
            </form>
            <div id="lf-catalog" class="rr-history-body">
                @include('payments.partials.lecture-fee-catalog')
            </div>
        </div>
    </section>
</div>

<div class="modal fade rsv-modal rr-modal-md" id="lf-edit-modal" tabindex="-1" aria-labelledby="lf-edit-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="lf-edit-form" class="modal-content" novalidate>
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h2 class="modal-title h5" id="lf-edit-title">Edit lecture rate</h2>
                <button type="button" class="rsv-modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
            </div>
            <div class="modal-body">
                <div class="rr-fields rr-add-fields">
                    <div class="rr-field"><label class="form-label" for="lf-edit-type">Lecture type <span class="text-danger">*</span></label><x-rm-select id="lf-edit-type" name="user_type" placeholder="Select lecture type" value="" selected-label="Select lecture type" :options="$typeOptions->all()" data-rr-required required /><p class="rr-field-error"></p></div>
                    <div class="rr-field"><label class="form-label" for="lf-edit-course">Course level <span class="text-danger">*</span></label><x-rm-select id="lf-edit-course" name="department" placeholder="Select course level" value="" selected-label="Select course level" :options="$courseOptions->all()" data-rr-required required /><p class="rr-field-error"></p></div>
                    <div class="rr-field"><label class="form-label" for="lf-edit-qualification">Qualification <span class="text-danger">*</span></label><x-rm-select id="lf-edit-qualification" name="qualification" placeholder="Select qualification" value="" selected-label="Select qualification" :options="$qualificationOptions->all()" data-rr-required required /><p class="rr-field-error"></p></div>
                    <div class="rr-field"><label class="form-label" for="lf-edit-rate">Hourly rate (LKR) <span class="text-danger">*</span></label><input id="lf-edit-rate" type="number" name="hourly_rate" class="form-control" min="0" step="0.01" required data-rr-required><p class="rr-field-error"></p></div>
                    <div class="rr-field"><label class="form-label" for="lf-edit-status">Status</label><x-rm-select id="lf-edit-status" name="status" placeholder="Active" value="active" selected-label="Active" :options="$statusOptions" /></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save lecture rate</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade rsv-modal rr-modal-sm" id="lf-confirm-modal" tabindex="-1" aria-labelledby="lf-confirm-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <span class="rsv-modal-icon" aria-hidden="true"><i class="ti ti-alert-triangle"></i></span>
                <div>
                    <h2 class="modal-title h5" id="lf-confirm-title">Confirm?</h2>
                </div>
                <button type="button" class="rsv-modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="lf-confirm-text"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="lf-confirm-submit">Confirm</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="application/json" id="lf-rates">@json($officialRates)</script>
<script type="application/json" id="lf-labels">@json(['types' => $types, 'courseLevels' => $courseLevels, 'qualifications' => $qualifications])</script>
<script src="{{ asset('js/resource-select.js') }}?v=3"></script>
<script src="{{ asset('js/lecture-fees.js') }}?v=9"></script>
@endpush
