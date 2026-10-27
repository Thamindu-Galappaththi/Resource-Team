<div class="table-responsive">
    <table class="table rsv-table mb-0" id="rr-rate-table">
        <thead>
            <tr>
                <th>Scope</th>
                <th>Base</th>
                <th>Included</th>
                <th>Extra/hr</th>
                <th>Tax</th>
                <th>Effective</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @forelse($rates as $rate)
            @php $scope = ($rate->resourceType?->name ?? 'Type').($rate->resource ? ' · '.$rate->resource->name_model : ' · all resources'); @endphp
            <tr data-rate>
                <td data-label="Scope">{{ $scope }}</td>
                <td data-label="Base">LKR {{ number_format($rate->base_fee, 2) }}</td>
                <td data-label="Included">{{ number_format($rate->base_duration_hours, 2) }} hrs</td>
                <td data-label="Extra/hr">LKR {{ number_format($rate->extra_hour_rate, 2) }}</td>
                <td data-label="Tax">{{ number_format($rate->tax_percent, 3) }}%</td>
                <td data-label="Effective">{{ $rate->effective_from?->format('d M Y') }} – {{ $rate->effective_to?->format('d M Y') ?? 'Open' }}</td>
                <td data-label="Status">
                    @if($rate->is_active)
                        <span class="badge text-bg-success">Active</span>
                    @else
                        <span class="badge text-bg-secondary">Inactive</span>
                    @endif
                </td>
                <td data-label="Actions" class="rsv-actions">
                    <div class="rr-actions">
                        <button type="button" class="btn btn-sm btn-outline-secondary rsv-icon-btn rr-edit-rate"
                            title="Edit" aria-label="Edit {{ $scope }}"
                            data-action="{{ route('payments.rates.update', $rate) }}"
                            data-label="{{ $scope }}"
                            data-type="{{ $rate->resource_type_id }}"
                            data-resource="{{ $rate->resource_id }}"
                            data-base="{{ $rate->base_fee }}"
                            data-hours="{{ $rate->base_duration_hours }}"
                            data-extra="{{ $rate->extra_hour_rate }}"
                            data-tax="{{ $rate->tax_percent }}"
                            data-from="{{ $rate->effective_from?->toDateString() }}"
                            data-to="{{ $rate->effective_to?->toDateString() }}"><i class="ti ti-edit"></i></button>
                        @if($rate->is_active)
                            <form method="POST" action="{{ route('payments.rates.deactivate', $rate) }}" data-confirm-title="Deactivate this rate?" data-confirm-text="It will no longer be used when a reservation is invoiced." data-confirm-button="Deactivate" data-confirm-kind="deactivate">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-sm btn-outline-danger rsv-icon-btn" type="submit" title="Deactivate" aria-label="Deactivate {{ $scope }}"><i class="ti ti-ban"></i></button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('payments.rates.activate', $rate) }}" data-confirm-title="Activate this rate?" data-confirm-text="It will be used when a reservation is invoiced." data-confirm-button="Activate" data-confirm-kind="activate">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-sm btn-outline-success rsv-icon-btn" type="submit" title="Activate" aria-label="Activate {{ $scope }}"><i class="ti ti-circle-check"></i></button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr class="rr-empty-row"><td colspan="8">{{ $catalogSearch ? 'No rates match that search.' : 'No rates have been created.' }}</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@include('payments.partials.history-pager', ['paginator' => $rates, 'section' => 'rate_catalog'])
