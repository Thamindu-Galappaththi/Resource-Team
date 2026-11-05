<div class="table-responsive">
    <table class="table rsv-table mb-0" id="rr-rate-table">
        <thead><tr><th>Photo</th><th>Location</th><th>Resource</th><th>Daily rate</th><th>Half-day rate</th><th>Effective</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        @forelse($rates as $rate)
            @php
                $scope = $rate->resource_name ?: ($rate->resource?->name_model ?? $rate->resourceType?->name ?? 'Resource');
                $daily = (float) ($rate->daily_rate ?? $rate->base_fee);
                $halfDay = (float) ($rate->half_day_rate ?? $rate->base_fee);
            @endphp
            <tr data-rate data-type="{{ $rate->resource_type_id }}" data-active="{{ $rate->is_active ? '1' : '0' }}">
                <td data-label="Photo">@if($rate->photo_path)<img class="rr-table-photo" src="{{ asset('storage/'.$rate->photo_path) }}" alt="{{ $scope }} photo" loading="lazy">@else<span class="rr-photo-placeholder"><i class="ti ti-photo"></i></span>@endif</td>
                <td data-label="Location">{{ $rate->location ? 'Training Centre '.$rate->location->name : 'All locations' }}</td>
                <td data-label="Resource"><strong>{{ $scope }}</strong>@if($rate->resource_id)<span class="rr-override-badge">Overrides type rate</span>@endif</td>
                <td data-label="Daily rate" class="rr-money">{{ $daily == 0 ? 'Free' : 'LKR '.number_format($daily, 2) }}</td>
                <td data-label="Half-day rate" class="rr-money">{{ $halfDay == 0 ? 'Free' : 'LKR '.number_format($halfDay, 2) }}</td>
                <td data-label="Effective">{{ $rate->effective_from?->format('d M Y') }} → {{ $rate->effective_to?->format('d M Y') ?? 'No end date' }}</td>
                <td data-label="Status">@if($rate->is_active)<span class="badge text-bg-success">Active</span>@else<span class="badge text-bg-secondary">Inactive</span>@endif</td>
                <td data-label="Actions" class="rsv-actions">
                    <div class="rr-actions">
                        <button type="button" class="btn btn-sm btn-outline-secondary rsv-icon-btn rr-edit-rate" title="Edit" aria-label="Edit {{ $scope }}"
                            data-action="{{ route('payments.rates.update', $rate) }}" data-label="{{ $scope }}" data-location="{{ $rate->location_id ?? 'all' }}" data-resource-name="{{ $scope }}" data-active="{{ $rate->is_active ? '1' : '0' }}"
                            data-daily="{{ $daily }}" data-half-day="{{ $halfDay }}" data-extra="{{ $rate->extra_hour_rate }}" data-tax="{{ $rate->tax_percent }}"
                            data-from="{{ $rate->effective_from?->toDateString() }}" data-to="{{ $rate->effective_to?->toDateString() }}"><i class="ti ti-edit"></i></button>
                        @if($rate->is_active)
                            <form method="POST" action="{{ route('payments.rates.deactivate', $rate) }}" data-confirm-title="Deactivate this rate?" data-confirm-text="This rate will no longer be used for new invoices." data-confirm-button="Deactivate" data-confirm-kind="deactivate">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-danger rsv-icon-btn" type="submit" title="Deactivate" aria-label="Deactivate {{ $scope }}"><i class="ti ti-ban"></i></button></form>
                        @else
                            <form method="POST" action="{{ route('payments.rates.activate', $rate) }}" data-confirm-title="Activate this rate?" data-confirm-text="Check that no active effective period overlaps before activating." data-confirm-button="Activate" data-confirm-kind="activate">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success rsv-icon-btn" type="submit" title="Activate" aria-label="Activate {{ $scope }}"><i class="ti ti-circle-check"></i></button></form>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr class="rr-empty-row"><td colspan="8">{{ $rateCount ? 'No rates match those filters.' : 'No rates have been created.' }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@include('payments.partials.history-pager', ['paginator' => $rates, 'section' => 'rate_catalog'])
