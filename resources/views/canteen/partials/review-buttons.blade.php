@can('manageStatus', $reservation)
    @if($reservation->status === \App\Enums\CanteenReservationStatus::PENDING->value)
        <button type="button" class="btn btn-sm btn-outline-success cp-icon-btn" title="Approve" aria-label="Approve {{ $reservation->reservation_ref }}"
            data-cd-review
            data-cd-action="{{ route('canteen.status', $reservation) }}"
            data-cd-status="approved"
            data-cd-title="Approve reservation?"
            data-cd-sub="{{ $reservation->reservation_ref }} · {{ $reservation->reservation_name }}"
            data-cd-copy="Kitchen can prepare this order after you approve it."
            data-cd-submit="Approve reservation"
            data-cd-icon="ti ti-circle-check"><i class="ti ti-check"></i></button>
        <button type="button" class="btn btn-sm btn-outline-danger cp-icon-btn" title="Reject" aria-label="Reject {{ $reservation->reservation_ref }}"
            data-cd-review
            data-cd-action="{{ route('canteen.status', $reservation) }}"
            data-cd-status="rejected"
            data-cd-title="Reject reservation?"
            data-cd-sub="{{ $reservation->reservation_ref }} · {{ $reservation->reservation_name }}"
            data-cd-copy="The requester will be notified. Add a short reason below."
            data-cd-submit="Reject reservation"
            data-cd-icon="ti ti-circle-x"><i class="ti ti-x"></i></button>
    @endif
@endcan
