@extends('layouts.app')

@section('title', 'Resource Rates')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/create-user.css') }}?v=15">
<link rel="stylesheet" href="{{ asset('css/reservation-index.css') }}?v=18">
<link rel="stylesheet" href="{{ asset('css/resource-payments.css') }}?v=9">
@endpush

@section('content')
<div class="container-fluid mt-4 rsv-page rp-page rr-admin">
    <div class="rsv-header">
        <div>
            <h1 class="rsv-title">Resource Rates</h1>
            <p class="rsv-subtitle mb-0">Configure effective-dated rates for resources and resource types.</p>
        </div>
        <div class="rsv-header-actions">
            <a href="{{ route('payments.resources') }}" class="btn rsv-header-btn rsv-calendar-btn"><i class="ti ti-receipt me-1"></i> Back to invoicing</a>
        </div>
    </div>
    @if(session('success'))<div class="rr-alert">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="rr-alert rr-errors">{{ $errors->first() }}</div>@endif
    <section class="rr-admin-card"><h2>Add rate</h2>
        <form method="POST" action="{{ route('payments.rates.store') }}" class="rr-admin-form">@csrf
            <label>Resource type<select name="resource_type_id" required><option value="">Select type</option>@foreach($types as $type)<option value="{{ $type->id }}">{{ $type->category?->name }} · {{ $type->name }}</option>@endforeach</select></label>
            <label>Specific resource (optional)<select name="resource_id"><option value="">All resources of type</option>@foreach($resources as $resource)<option value="{{ $resource->id }}">{{ $resource->name_model }} ({{ $resource->type?->name }})</option>@endforeach</select></label>
            <label>Base fee (LKR)<input type="number" name="base_fee" min="0" step="0.01" required></label><label>Included hours<input type="number" name="base_duration_hours" min="0.01" step="0.01" required></label>
            <label>Extra hour rate (LKR)<input type="number" name="extra_hour_rate" min="0" step="0.01" required></label><label>Tax (%)<input type="number" name="tax_percent" min="0" max="100" step="0.001" value="0" required></label>
            <label>Effective from<input type="date" name="effective_from" value="{{ now()->toDateString() }}" required></label><label>Effective to<input type="date" name="effective_to"></label><button type="submit">Add rate</button>
        </form>
    </section>
    <section class="rr-admin-card"><h2>Rate catalog</h2><table><thead><tr><th>Scope</th><th>Base</th><th>Included</th><th>Extra/hr</th><th>Tax</th><th>Effective</th><th>Status</th><th>Actions</th></tr></thead><tbody>
    @forelse($rates as $rate)<tr><td>{{ $rate->resourceType?->name }}{{ $rate->resource ? ' · '.$rate->resource->name_model : ' · all resources' }}</td><td>LKR {{ number_format($rate->base_fee,2) }}</td><td>{{ number_format($rate->base_duration_hours,2) }} hrs</td><td>LKR {{ number_format($rate->extra_hour_rate,2) }}</td><td>{{ number_format($rate->tax_percent,3) }}%</td><td>{{ $rate->effective_from?->format('d M Y') }} – {{ $rate->effective_to?->format('d M Y') ?? 'Open' }}</td><td>{{ $rate->is_active ? 'Active' : 'Inactive' }}</td><td>@if($rate->is_active)<details><summary>Edit</summary><form method="POST" action="{{ route('payments.rates.update',$rate) }}" class="rr-admin-form">@csrf @method('PUT')
        <label>Resource type<select name="resource_type_id" required>@foreach($types as $type)<option value="{{ $type->id }}" @selected($rate->resource_type_id===$type->id)>{{ $type->category?->name }} · {{ $type->name }}</option>@endforeach</select></label>
        <label>Specific resource<select name="resource_id"><option value="">All resources of type</option>@foreach($resources as $resource)<option value="{{ $resource->id }}" @selected($rate->resource_id===$resource->id)>{{ $resource->name_model }}</option>@endforeach</select></label>
        <label>Base fee<input type="number" name="base_fee" min="0" step="0.01" value="{{ $rate->base_fee }}" required></label><label>Included hours<input type="number" name="base_duration_hours" min="0.01" step="0.01" value="{{ $rate->base_duration_hours }}" required></label>
        <label>Extra hour rate<input type="number" name="extra_hour_rate" min="0" step="0.01" value="{{ $rate->extra_hour_rate }}" required></label><label>Tax (%)<input type="number" name="tax_percent" min="0" max="100" step="0.001" value="{{ $rate->tax_percent }}" required></label>
        <label>Effective from<input type="date" name="effective_from" value="{{ $rate->effective_from?->toDateString() }}" required></label><label>Effective to<input type="date" name="effective_to" value="{{ $rate->effective_to?->toDateString() }}"></label><button type="submit">Save</button></form></details>
        <form method="POST" action="{{ route('payments.rates.deactivate',$rate) }}" style="margin-top:6px">@csrf @method('PATCH')<button class="rr-deactivate" type="submit">Deactivate</button></form>@else—@endif</td></tr>@empty<tr><td colspan="8">No rates have been created.</td></tr>@endforelse
    </tbody></table>
    </section>
    <section class="rr-admin-card"><h2>Customer category discounts</h2><p class="rr-muted">Category must match the reservation requester’s user type (for example <code>student</code> or <code>staff</code>). Rules apply after the rate subtotal and before tax.</p>
        <form method="POST" action="{{ route('payments.discounts.store') }}" class="rr-admin-form">@csrf
            <label>Customer category key<input type="text" name="customer_category" maxlength="80" pattern="[A-Za-z0-9_-]+" placeholder="student" required></label>
            <label>Discount percent<input type="number" name="discount_percent" min="0" max="100" step="0.01" value="0" required></label>
            <button type="submit">Add discount</button>
        </form>
        <table><thead><tr><th>Category</th><th>Discount</th><th>Status</th><th>Actions</th></tr></thead><tbody>
        @forelse($discountRules as $rule)<tr><td>{{ $rule->customer_category }}</td><td>{{ number_format($rule->discount_percent, 2) }}%</td><td>{{ $rule->is_active ? 'Active' : 'Inactive' }}</td><td>@if($rule->is_active)
            <details><summary>Edit</summary><form method="POST" action="{{ route('payments.discounts.update',$rule) }}" class="rr-admin-form">@csrf @method('PUT')
                <label>Category key<input type="text" name="customer_category" maxlength="80" pattern="[A-Za-z0-9_-]+" value="{{ $rule->customer_category }}" required></label>
                <label>Discount percent<input type="number" name="discount_percent" min="0" max="100" step="0.01" value="{{ $rule->discount_percent }}" required></label><button type="submit">Save discount</button>
            </form></details>
            <form method="POST" action="{{ route('payments.discounts.deactivate',$rule) }}" style="margin-top:6px">@csrf @method('PATCH')<button class="rr-deactivate" type="submit">Deactivate</button></form>@else—@endif</td></tr>
        @empty<tr><td colspan="4">No category discounts configured. All categories currently use a 0% discount unless a legacy config rule is set.</td></tr>@endforelse
        </tbody></table>
    </section>
    <section class="rr-admin-card"><h2>Discount change history</h2><table><thead><tr><th>When</th><th>Category</th><th>Action</th><th>Actor</th><th>Details</th></tr></thead><tbody>
        @forelse($discountAudits as $audit)<tr><td>{{ $audit->created_at?->format('d M Y H:i') }}</td><td>{{ $audit->rule?->customer_category ?? 'Deleted rule' }}</td><td>{{ ucfirst($audit->action) }}</td><td>{{ $audit->actor?->name ?? 'Unknown' }}</td><td><details><summary>View values</summary><pre>{{ json_encode(['before'=>$audit->old_values,'after'=>$audit->new_values], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre></details></td></tr>@empty<tr><td colspan="5">No discount changes recorded.</td></tr>@endforelse
    </tbody></table></section>
    <section class="rr-admin-card"><h2>Recent changes</h2><table><thead><tr><th>When</th><th>Rate</th><th>Action</th><th>Actor</th><th>Details</th></tr></thead><tbody>
    @forelse($audits as $audit)<tr><td>{{ $audit->created_at?->format('d M Y H:i') }}</td><td>{{ $audit->rate?->resourceType?->name ?? 'Deleted rate' }}</td><td>{{ ucfirst($audit->action) }}</td><td>{{ $audit->actor?->name ?? 'Unknown' }}</td><td><details><summary>View values</summary><pre>{{ json_encode(['before'=>$audit->old_values,'after'=>$audit->new_values], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}</pre></details></td></tr>@empty<tr><td colspan="5">No changes recorded.</td></tr>@endforelse
    </tbody></table></section>
</div>
@endsection
