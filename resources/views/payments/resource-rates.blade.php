@extends('layouts.app')

@section('title', 'Resource Rates')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/create-user.css') }}?v=15">
<link rel="stylesheet" href="{{ asset('css/reservation-index.css') }}?v=18">
<link rel="stylesheet" href="{{ asset('css/resource-calendar.css') }}?v=38">
<link rel="stylesheet" href="{{ asset('css/resource-payments.css') }}?v=43">
@endpush

@section('content')
@php
    $typeOptions = $types->map(function ($type) {
        return [
            'value' => (string) $type->id,
            'label' => trim(($type->category?->name ? $type->category->name.' · ' : '').$type->name),
        ];
    })->values();
    $resourceOptions = $resources->map(function ($resource) {
        return [
            'value' => (string) $resource->id,
            'label' => $resource->name_model.($resource->type?->name ? ' ('.$resource->type->name.')' : ''),
            'name' => $resource->name_model,
            'type' => (string) $resource->resource_type_id,
        ];
    })->values();
    $oldType = (string) old('resource_type_id', '');
    $oldResource = (string) old('resource_id', '');
    $matchedType = $typeOptions->firstWhere('value', $oldType);
    $matchedResource = $resourceOptions->firstWhere('value', $oldResource);
    $oldTypeLabel = $matchedType['label'] ?? 'Select type';
    $oldResourceLabel = $matchedResource['label'] ?? 'All resources of type';
    $activeTab = 'rates';
    if (session('rr_tab') === 'history') {
        $activeTab = 'history';
    } elseif (session('rr_tab') === 'discounts' || $errors->hasAny(['customer_category', 'discount_percent']) || old('customer_category') !== null || old('discount_percent') !== null) {
        $activeTab = 'discounts';
    }
@endphp
<div class="container-fluid mt-4 rsv-page rp-page rr-admin" data-rr-tab="{{ $activeTab }}">
    <header class="rr-page-heading">
        <div>
            <h1>Resource Rates</h1>
            <p>Configure effective-dated rates for resources and resource types.</p>
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

    <nav class="rr-tabs" aria-label="Resource rate sections">
        <a href="#rr-rates" @class(['is-active' => $activeTab === 'rates']) @if($activeTab === 'rates') aria-current="page" @endif>Rates</a>
        <a href="#rr-discounts" @class(['is-active' => $activeTab === 'discounts']) @if($activeTab === 'discounts') aria-current="page" @endif>Discounts</a>
        <a href="#rr-history" @class(['is-active' => $activeTab === 'history']) @if($activeTab === 'history') aria-current="page" @endif>History</a>
    </nav>

    <div class="rr-stats rc-cards">
        <div class="rc-card">
            <span class="rc-card-icon" aria-hidden="true"><i class="ti ti-list-details"></i></span>
            <div class="rc-card-label">Rates</div>
            <div class="rc-card-value">{{ number_format($rateCount) }}</div>
        </div>
        <div class="rc-card">
            <span class="rc-card-icon" aria-hidden="true"><i class="ti ti-circle-check"></i></span>
            <div class="rc-card-label">Active rates</div>
            <div class="rc-card-value" id="rr-active-rates">{{ number_format($activeRateCount) }}</div>
        </div>
        <div class="rc-card">
            <span class="rc-card-icon" aria-hidden="true"><i class="ti ti-percentage"></i></span>
            <div class="rc-card-label">Active discounts</div>
            <div class="rc-card-value" id="rr-active-discounts">{{ number_format($activeDiscountCount) }}</div>
        </div>
    </div>

    <section class="card border-0 rsv-panel mb-4 rr-management" id="rr-add-rate">
        <div class="rr-catalog-heading">
            <div>
                <span class="rp-title-icon"><i class="ti ti-tag"></i></span>
                <div>
                    <h2 class="rr-section-title">Add rate</h2>
                    <p class="rr-muted">A specific resource overrides the type rate. Leave it open to cover every resource of that type.</p>
                </div>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('payments.rates.store') }}" class="rr-fields" novalidate>
                @csrf
                <div class="rr-field">
                    <label class="form-label" for="rr-type">Resource type</label>
                    <x-rm-select id="rr-type" name="resource_type_id" placeholder="Select type" :value="$oldType" :selected-label="$oldTypeLabel" :required="true" data-rr-required
                        :options="collect([['value' => '', 'label' => 'Select type']])->concat($typeOptions)->all()" />
                    <p class="rr-field-error">Please select an item in the list.</p>
                </div>
                <div class="rr-field">
                    <label class="form-label" for="rr-resource">Specific resource</label>
                    <x-rm-select id="rr-resource" name="resource_id" placeholder="All resources of type" :value="$oldResource" :selected-label="$oldResourceLabel"
                        :options="collect([['value' => '', 'label' => 'All resources of type']])->concat($resourceOptions)->all()" />
                </div>
                <div class="rr-field">
                    <label class="form-label" for="rr-base">Base fee (LKR)</label>
                    <input id="rr-base" type="number" name="base_fee" class="form-control" min="0" step="0.01" value="{{ old('base_fee') }}" data-rr-required>
                    <p class="rr-field-error"></p>
                </div>
                <div class="rr-field">
                    <label class="form-label" for="rr-hours">Included hours</label>
                    <input id="rr-hours" type="number" name="base_duration_hours" class="form-control" min="0.01" step="0.01" value="{{ old('base_duration_hours') }}" data-rr-required>
                    <p class="rr-field-error"></p>
                </div>
                <div class="rr-field">
                    <label class="form-label" for="rr-extra">Extra hour rate (LKR)</label>
                    <input id="rr-extra" type="number" name="extra_hour_rate" class="form-control" min="0" step="0.01" value="{{ old('extra_hour_rate') }}" data-rr-required>
                    <p class="rr-field-error"></p>
                </div>
                <div class="rr-field">
                    <label class="form-label" for="rr-tax">Tax (%)</label>
                    <input id="rr-tax" type="number" name="tax_percent" class="form-control" min="0" max="100" step="0.001" value="{{ old('tax_percent', 0) }}" data-rr-required>
                    <p class="rr-field-error"></p>
                </div>
                <div class="rr-field">
                    <label class="form-label" for="rr-from">Effective from</label>
                    <input id="rr-from" type="date" name="effective_from" class="form-control" value="{{ old('effective_from', now()->toDateString()) }}" data-rr-required>
                    <p class="rr-field-error"></p>
                </div>
                <div class="rr-field">
                    <label class="form-label" for="rr-to">Effective to</label>
                    <input id="rr-to" type="date" name="effective_to" class="form-control" value="{{ old('effective_to') }}">
                </div>
                <div class="rr-field rr-field-action">
                    <button type="submit" class="btn btn-primary rr-submit rr-submit-rate">Add Rate</button>
                </div>
            </form>
        </div>
    </section>

    <section class="card border-0 rsv-panel mb-4 rr-catalog-panel" id="rr-rates">
        <div class="card-body">
            <div class="rr-catalog-heading">
                <div>
                    <span class="rp-title-icon"><i class="ti ti-list-details"></i></span>
                    <div><h2 class="rr-section-title">Rate catalog</h2>
                    <p class="rr-muted">Search, compare, and manage the rates applied to reservations.</p></div>
                </div>
            </div>
            <form method="GET" action="{{ route('payments.rates.index') }}" class="rr-catalog-filters" data-rr-catalog-search>
                <div class="rr-field rr-rate-search"><label class="form-label" for="rr-rate-search">Search</label><div class="input-group rsv-search-group rp-search-group"><span class="input-group-text"><i class="ti ti-search"></i></span><input id="rr-rate-search" type="search" name="catalog_search" class="form-control" placeholder="Search rates" value="{{ $catalogSearch }}" autocomplete="off"></div></div>
                <div class="rr-field"><label class="form-label" for="rr-type-filter">Resource type</label><x-rm-select id="rr-type-filter" name="catalog_type" placeholder="All types" :value="$catalogType ?? ''" :options="collect([['value' => '', 'label' => 'All types']])->concat($typeOptions)->all()" /></div>
                <div class="rr-field"><label class="form-label" for="rr-status-filter">Status</label><x-rm-select id="rr-status-filter" name="catalog_status" placeholder="Active" :value="$catalogStatus ?? 'active'" :options="[['value' => 'active', 'label' => 'Active'], ['value' => 'all', 'label' => 'All statuses'], ['value' => 'inactive', 'label' => 'Inactive']]" /></div>
                <div class="rr-field rr-catalog-actions">
                    <label class="form-label rr-field-spacer" for="rr-clear-filters">&nbsp;</label>
                    <div class="rr-catalog-actions-row">
                        <button id="rr-clear-filters" type="button" class="rsv-filter-btn rsv-filter-clear" @disabled($catalogSearch === '' && ($catalogType ?? '') === '' && ($catalogStatus ?? 'active') === 'active')>Clear</button>
                        <button id="rr-export-csv" type="button" class="btn rr-export-btn" aria-label="Export CSV"><i class="ti ti-download"></i> Export CSV</button>
                    </div>
                </div>
            </form>
            <div id="rr-rate-catalog" class="rr-history-body">
                @include('payments.partials.rate-catalog')
            </div>
        </div>
    </section>

    <section class="card border-0 rsv-panel mb-4 rr-management" id="rr-discounts">
        <div class="card-body">
            <h2 class="rr-section-title">Customer category discounts</h2>
            <p class="rr-muted mb-3">The category must match the requester’s user type, such as student or staff. The discount applies after the rate subtotal and before tax.</p>
            <form method="POST" action="{{ route('payments.discounts.store') }}" class="rr-fields rr-discount-fields mb-3" autocomplete="off" novalidate>
                @csrf
                <div class="rr-field">
                    <label class="form-label" for="rr-category">Customer category</label>
                    <input id="rr-category" type="text" name="customer_category" class="form-control" maxlength="80" data-rr-pattern="[A-Za-z0-9_-]+" value="{{ old('customer_category') }}" autocomplete="off" data-rr-required>
                    <p class="rr-field-error">Use letters, numbers, hyphens, or underscores.</p>
                </div>
                <div class="rr-field">
                    <label class="form-label" for="rr-percent">Discount percent</label>
                    <input id="rr-percent" type="number" name="discount_percent" class="form-control" min="0" max="100" step="0.01" value="{{ old('discount_percent', 0) }}" data-rr-required>
                    <p class="rr-field-error"></p>
                </div>
                <div class="rr-field rr-field-inline-action">
                    <label class="form-label rr-field-spacer" for="rr-discount-submit">&nbsp;</label>
                    <button id="rr-discount-submit" type="submit" class="btn btn-primary rr-submit">Add discount</button>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table rsv-table mb-0">
                    <thead>
                        <tr><th>Category</th><th>Discount</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    @forelse($discountRules as $rule)
                        <tr>
                            <td data-label="Category">{{ $rule->customer_category }}</td>
                            <td data-label="Discount">{{ number_format($rule->discount_percent, 2) }}%</td>
                            <td data-label="Status">
                                @if($rule->is_active)
                                    <span class="badge text-bg-success">Active</span>
                                @else
                                    <span class="badge text-bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td data-label="Actions" class="rsv-actions">
                                <div class="rr-actions">
                                    <button type="button" class="btn btn-sm btn-outline-secondary rsv-icon-btn rr-edit-discount"
                                        title="Edit" aria-label="Edit {{ $rule->customer_category }} discount"
                                        data-action="{{ route('payments.discounts.update', $rule) }}"
                                        data-category="{{ $rule->customer_category }}"
                                        data-percent="{{ $rule->discount_percent }}"><i class="ti ti-edit"></i></button>
                                    @if($rule->is_active)
                                        <form method="POST" action="{{ route('payments.discounts.deactivate', $rule) }}" data-confirm-title="Deactivate this discount?" data-confirm-text="This category will use a 0% discount until a new rule is added." data-confirm-button="Deactivate" data-confirm-kind="deactivate">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-sm btn-outline-danger rsv-icon-btn" type="submit" title="Deactivate" aria-label="Deactivate {{ $rule->customer_category }} discount"><i class="ti ti-ban"></i></button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('payments.discounts.activate', $rule) }}" data-confirm-title="Activate this discount?" data-confirm-text="This category discount will apply again when a reservation is invoiced." data-confirm-button="Activate" data-confirm-kind="activate">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-sm btn-outline-success rsv-icon-btn" type="submit" title="Activate" aria-label="Activate {{ $rule->customer_category }} discount"><i class="ti ti-circle-check"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="rr-empty-row"><td colspan="4">No category discounts configured. Categories without a rule use a 0% discount.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="card border-0 rsv-panel mb-4 rr-management" id="rr-history">
        <div class="card-body">
            <h2 class="rr-section-title">Discount change history</h2>
            <p class="rr-muted mb-3">Discount create, update, activate, and deactivate records.</p>
            <div id="rr-discount-history" class="rr-history-body">
                @include('payments.partials.discount-history')
            </div>
        </div>
    </section>

    <section class="card border-0 rsv-panel mb-4 rr-management" id="rr-rate-history-panel">
        <div class="card-body">
            <h2 class="rr-section-title">Rate change history</h2>
            <p class="rr-muted mb-3">Rate create, update, activate, and deactivate records.</p>
            <div id="rr-rate-history" class="rr-history-body">
                @include('payments.partials.rate-history')
            </div>
        </div>
    </section>
</div>

<div class="modal fade rsv-modal rr-modal-md" id="rr-rate-modal" tabindex="-1" aria-labelledby="rr-edit-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="rr-rate-edit-form" class="modal-content" novalidate>
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h2 class="modal-title h5" id="rr-edit-title">Edit rate</h2>
                <button type="button" class="rsv-modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
            </div>
            <div class="modal-body">
                <div class="rr-fields">
                <div class="rr-field">
                    <label class="form-label" for="rr-edit-type">Resource type</label>
                    <x-rm-select id="rr-edit-type" name="resource_type_id" placeholder="Select type" :required="true" data-rr-required
                        :options="collect([['value' => '', 'label' => 'Select type']])->concat($typeOptions)->all()" />
                    <p class="rr-field-error">Please select an item in the list.</p>
                </div>
                <div class="rr-field">
                    <label class="form-label" for="rr-edit-resource">Specific resource</label>
                    <x-rm-select id="rr-edit-resource" name="resource_id" placeholder="All resources of type"
                        :options="collect([['value' => '', 'label' => 'All resources of type']])->concat($resourceOptions)->all()" />
                </div>
                <div class="rr-field"><label class="form-label" for="rr-edit-base">Base fee (LKR)</label><input id="rr-edit-base" type="number" name="base_fee" class="form-control" min="0" step="0.01" data-rr-required><p class="rr-field-error"></p></div>
                <div class="rr-field"><label class="form-label" for="rr-edit-hours">Included hours</label><input id="rr-edit-hours" type="number" name="base_duration_hours" class="form-control" min="0.01" step="0.01" data-rr-required><p class="rr-field-error"></p></div>
                <div class="rr-field"><label class="form-label" for="rr-edit-extra">Extra hour rate (LKR)</label><input id="rr-edit-extra" type="number" name="extra_hour_rate" class="form-control" min="0" step="0.01" data-rr-required><p class="rr-field-error"></p></div>
                <div class="rr-field"><label class="form-label" for="rr-edit-tax">Tax (%)</label><input id="rr-edit-tax" type="number" name="tax_percent" class="form-control" min="0" max="100" step="0.001" data-rr-required><p class="rr-field-error"></p></div>
                <div class="rr-field"><label class="form-label" for="rr-edit-from">Effective from</label><input id="rr-edit-from" type="date" name="effective_from" class="form-control" data-rr-required><p class="rr-field-error"></p></div>
                <div class="rr-field"><label class="form-label" for="rr-edit-to">Effective to</label><input id="rr-edit-to" type="date" name="effective_to" class="form-control"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save rate</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade rsv-modal rr-modal-sm" id="rr-info-modal" tabindex="-1" aria-labelledby="rr-info-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <span class="rsv-modal-icon" aria-hidden="true"><i class="ti ti-info-circle"></i></span>
                <div>
                    <h2 class="modal-title h5" id="rr-info-title">Change details</h2>
                    <p class="rsv-modal-sub mb-0" id="rr-info-sub"></p>
                </div>
                <button type="button" class="rsv-modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
            </div>
            <div class="modal-body">
                <div class="rsv-details" id="rr-info-details"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade rsv-modal rr-modal-sm" id="rr-confirm-modal" tabindex="-1" aria-labelledby="rr-confirm-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <span class="rsv-modal-icon" aria-hidden="true"><i class="ti ti-alert-triangle"></i></span>
                <div>
                    <h2 class="modal-title h5" id="rr-confirm-title">Confirm?</h2>
                </div>
                <button type="button" class="rsv-modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="rr-confirm-text"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="rr-confirm-submit">Confirm</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade rsv-modal rr-modal-sm" id="rr-discount-modal" tabindex="-1" aria-labelledby="rr-discount-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" id="rr-discount-edit-form" class="modal-content" novalidate>
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h2 class="modal-title h5" id="rr-discount-title">Edit discount</h2>
                <button type="button" class="rsv-modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                <div class="col-12"><label class="form-label" for="rr-edit-category">Customer category</label><input id="rr-edit-category" type="text" name="customer_category" class="form-control" maxlength="80" data-rr-pattern="[A-Za-z0-9_-]+" data-rr-required><p class="rr-field-error"></p></div>
                <div class="col-12"><label class="form-label" for="rr-edit-percent">Discount percent</label><input id="rr-edit-percent" type="number" name="discount_percent" class="form-control" min="0" max="100" step="0.01" data-rr-required><p class="rr-field-error"></p></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save discount</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script type="application/json" id="rr-catalog">@json(['types' => $typeOptions, 'resources' => $resourceOptions])</script>
<script src="{{ asset('js/resource-select.js') }}?v=3"></script>
<script src="{{ asset('js/resource-rates.js') }}?v=18"></script>
@endpush

