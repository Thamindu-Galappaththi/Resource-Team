@if(is_iterable($change['details']) && count($change['details']))
    <button type="button" class="btn btn-sm btn-outline-secondary rr-view-info" data-rr-info
        data-title="{{ $change['item'] }}"
        data-action="{{ $change['action'] }}"
        data-when="{{ $change['at']?->format('d M Y H:i') }}"
        data-actor="{{ $change['actor'] }}"
        data-details="{{ json_encode($change['details']) }}">
        <i class="ti ti-eye"></i> View info
    </button>
@else
    <span class="rr-none">None</span>
@endif
