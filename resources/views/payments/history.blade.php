@extends('layouts.app')

@section('title', 'View Payments')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/create-user.css') }}?v=15">
<link rel="stylesheet" href="{{ asset('css/reservation-index.css') }}?v=18">
<link rel="stylesheet" href="{{ asset('css/resource-calendar.css') }}?v=38">
<link rel="stylesheet" href="{{ asset('css/resource-payments.css') }}?v=48">
<link rel="stylesheet" href="{{ asset('css/dashboard-role.css') }}?v={{ @filemtime(public_path('css/dashboard-role.css')) ?: time() }}">
<link rel="stylesheet" href="{{ asset('css/resource-payment-history.css') }}?v=7">
@endpush

@section('content')
@php
    $statusValue = (string) ($filters['status'] ?? '');
    $typeValue = (string) ($filters['resource_type'] ?? '');
    $statusOptions = [
        ['value' => '', 'label' => 'All statuses'],
        ['value' => 'paid', 'label' => 'Paid'],
        ['value' => 'unpaid', 'label' => 'Unpaid'],
    ];
    $typeOptions = collect([['value' => '', 'label' => 'All types']])
        ->concat($resourceTypes->map(fn ($type) => ['value' => (string) $type->id, 'label' => $type->name]))
        ->all();
    $statusLabel = collect($statusOptions)->firstWhere('value', $statusValue)['label'] ?? 'All statuses';
    $typeLabel = collect($typeOptions)->firstWhere('value', $typeValue)['label'] ?? 'All types';
    $hasFilters = ($filters['search'] ?? '') !== ''
        || $statusValue !== ''
        || $typeValue !== ''
        || ($filters['date_from'] ?? '') !== ''
        || ($filters['date_to'] ?? '') !== '';
@endphp
<div class="container-fluid mt-4 rsv-page rp-page rr-admin rp-history-page" data-rph-url="{{ route('payments.history') }}" data-rph-export="{{ route('payments.history.export') }}" data-rph-tab="{{ $activeTab }}">
    <header class="rd-hero rph-hero">
        <div class="rd-hero__copy">
            <h1>View payments</h1>
            <p class="rd-hero__sub">Review resource invoices and payment status.</p>
        </div>
        <nav class="rd-hero__actions" aria-label="Payment navigation">
            <a href="{{ route('payments.resources') }}" class="rd-btn rd-btn--outline"><i class="ti ti-arrow-left"></i> Back to invoicing</a>
        </nav>
    </header>

    <section class="rr-stats rc-cards rph-stats" id="rph-stats" aria-label="Payment summary">
        @include('payments.partials.history-stats')
    </section>

    <form id="rph-filters" class="rr-catalog-filters rph-filters" data-action-lock="off">
        <div class="rr-field rr-rate-search">
            <label class="form-label" for="rph-search">Search</label>
            <div class="input-group rsv-search-group rp-search-group">
                <span class="input-group-text"><i class="ti ti-search"></i></span>
                <input id="rph-search" type="search" name="search" class="form-control" placeholder="Search invoices" value="{{ $filters['search'] ?? '' }}" autocomplete="off">
            </div>
        </div>
        <div class="rr-field">
            <label class="form-label" for="rph-status">Status</label>
            <x-rm-select id="rph-status" name="status" placeholder="All statuses" :value="$statusValue" :selected-label="$statusLabel" :options="$statusOptions" />
        </div>
        <div class="rr-field">
            <label class="form-label" for="rph-type">Resource type</label>
            <x-rm-select id="rph-type" name="resource_type" placeholder="All types" :value="$typeValue" :selected-label="$typeLabel" :options="$typeOptions" />
        </div>
        <div class="rr-field rph-date">
            <label class="form-label" for="rph-from">From</label>
            <input id="rph-from" type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] ?? '' }}">
        </div>
        <div class="rr-field rph-date">
            <label class="form-label" for="rph-to">To</label>
            <input id="rph-to" type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] ?? '' }}">
        </div>
        <div class="rr-field rr-catalog-actions">
            <label class="form-label rr-field-spacer" for="rph-clear">&nbsp;</label>
            <div class="rr-catalog-actions-row">
                <button class="rsv-filter-btn rsv-filter-clear" type="button" id="rph-clear" @disabled(! $hasFilters)>Clear</button>
                <a class="btn rr-export-btn" id="rph-export" href="{{ route('payments.history.export', collect($filters)->except(['tab', 'page', 'per_page', 'section'])->all()) }}"><i class="ti ti-download"></i> Export CSV</a>
            </div>
        </div>
    </form>

    <nav class="rr-tabs rph-tabs" aria-label="Payment history sections">
        <a href="{{ route('payments.history', array_merge(collect($filters)->except(['tab', 'section', 'page'])->all(), ['tab' => 'payments'])) }}" data-rph-tab="payments" @class(['is-active' => $activeTab === 'payments']) @if($activeTab === 'payments') aria-current="page" @endif><i class="ti ti-list-details"></i> Payments</a>
        <a href="{{ route('payments.history', array_merge(collect($filters)->except(['tab', 'section', 'page'])->all(), ['tab' => 'dashboard'])) }}" data-rph-tab="dashboard" @class(['is-active' => $activeTab === 'dashboard']) @if($activeTab === 'dashboard') aria-current="page" @endif><i class="ti ti-chart-donut"></i> Dashboard</a>
    </nav>

    <section class="rph-card rph-table-card" id="rph-payments-panel" @if($activeTab !== 'payments') hidden @endif>
        <div class="rph-card-heading rph-table-heading">
            <div>
                <h2>Resource invoices</h2>
                <p>View-only invoice and payment records.</p>
            </div>
            <span class="rph-record-count" id="rph-record-count">{{ number_format($payments->total()) }} records</span>
        </div>
        <div id="rph-payments-results" class="rph-section-body">
            @include('payments.partials.history-payments')
        </div>
    </section>

    <div id="rph-dashboard-panel" @if($activeTab !== 'dashboard') hidden @endif>
        <div id="rph-dashboard-results" class="rph-section-body">
            @include('payments.partials.history-dashboard')
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/resource-select.js') }}?v=3"></script>
<script src="{{ asset('js/resource-payment-history.js') }}?v=3"></script>
@endpush
