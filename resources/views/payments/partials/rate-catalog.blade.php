<div class="table-responsive">
    <table class="table rsv-table mb-0" id="rr-rate-table">
        <thead>
            <tr>
                <th>Location</th>
                <th>Category</th>
                <th>Resource type</th>
                <th>Daily rate<br><small>09:00 – 17:00</small></th>
                <th>Half-day rate<br><small>09:00–13:00 / 13:00–17:00</small></th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse($rates as $rate)
            @php
                $typeName = $rate->resourceType?->name ?: ($rate->resource_name ?: 'Resource type');
                $categoryName = $rate->resourceType?->category?->name ?: '—';
                $locationName = $rate->location?->name ?? '—';
                $daily = (float) ($rate->daily_rate ?? $rate->base_fee);
                $halfDay = (float) ($rate->half_day_rate ?? $rate->base_fee);
            @endphp
            <tr data-rate data-type="{{ $rate->resource_type_id }}" data-category="{{ $rate->resourceType?->resource_category_id }}" data-active="{{ $rate->is_active ? '1' : '0' }}">
                <td data-label="Location">{{ $locationName }}</td>
                <td data-label="Category">{{ $categoryName }}</td>
                <td data-label="Resource type"><strong>{{ $typeName }}</strong></td>
                <td data-label="Daily rate" class="rr-money">{{ $daily == 0 ? 'Free' : 'LKR '.number_format($daily, 2) }}</td>
                <td data-label="Half-day rate" class="rr-money">{{ $halfDay == 0 ? 'Free' : 'LKR '.number_format($halfDay, 2) }}</td>
                <td data-label="Status">@if($rate->is_active)<span class="badge text-bg-success">Active</span>@else<span class="badge text-bg-secondary">Inactive</span>@endif</td>
                <td data-label="Actions" class="rsv-actions">
                    <div class="rr-actions">
                        <button type="button" class="btn btn-sm btn-outline-secondary rsv-icon-btn rr-edit-rate" title="Edit" aria-label="Edit {{ $typeName }}"
                            data-action="{{ route('payments.rates.update', $rate) }}"
                            data-label="{{ $typeName }}"
                            data-location="{{ $rate->location_id ?? '' }}"
                            data-location-label="{{ $rate->location?->name ?? '' }}"
                            data-category="{{ $rate->resourceType?->resource_category_id }}"
                            data-category-label="{{ $categoryName }}"
                            data-type="{{ $rate->resource_type_id }}"
                            data-type-label="{{ $typeName }}"
                            data-active="{{ $rate->is_active ? '1' : '0' }}"
                            data-daily="{{ $daily }}"
                            data-half-day="{{ $halfDay }}"><i class="ti ti-edit"></i></button>
                        @if($rate->is_active)
                            <form method="POST" action="{{ route('payments.rates.deactivate', $rate) }}" data-confirm-title="Deactivate this rate?" data-confirm-text="This rate will no longer be used for new invoices." data-confirm-button="Deactivate" data-confirm-kind="deactivate">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-danger rsv-icon-btn" type="submit" title="Deactivate" aria-label="Deactivate {{ $typeName }}"><i class="ti ti-ban"></i></button></form>
                        @else
                            <form method="POST" action="{{ route('payments.rates.activate', $rate) }}" data-confirm-title="Activate this rate?" data-confirm-text="Check that no other active rate exists for this location and type." data-confirm-button="Activate" data-confirm-kind="activate">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success rsv-icon-btn" type="submit" title="Activate" aria-label="Activate {{ $typeName }}"><i class="ti ti-circle-check"></i></button></form>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr class="rr-empty-row"><td colspan="7">{{ $rateCount ? 'No rates match those filters.' : 'No rates have been created.' }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@include('payments.partials.history-pager', ['paginator' => $rates, 'section' => 'rate_catalog'])
