@extends('layouts.app')

@section('title', 'Hostel '.$reservation->reference)

@section('content')
<style>
    /* Scoped to hostel pages; follows the Resource Calendar's visual style. */
    .hostel-page {
        width: 100%;
        margin: 0 auto;
        padding: 35px 25px 50px !important;
        min-height: 100vh;
        background: transparent;
        color: #111827;
        font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }
    .hostel-page h1, .hostel-page > div > div > h2 {
        color: #0f172a !important;
        font-size: 26px;
        font-weight: 800;
        letter-spacing: -.02em;
    }
    .hostel-page .page-subtitle, .hostel-page > div > div > p,
    .hostel-page nav, .hostel-page nav a { color: #6b7280 !important; font-size: 14px; }
    .hostel-reservations > .d-flex h1,
    .hostel-reservations > .d-flex .page-subtitle,
    .hostel-page.hostel-details > .d-flex > div > h2,
    .hostel-page.hostel-details > .d-flex > div > p { color: #fff !important; }
    .hostel-page.hostel-create > .mb-4 > h1,
    .hostel-page.hostel-create > .mb-4 > nav,
    .hostel-page.hostel-create > .mb-4 > nav a,
    .hostel-page.hostel-create > .mb-4 > nav span,
    .hostel-page.hostel-create > .mb-4 > nav i { color: #fff !important; }
    .hostel-page .card, .hostel-page .summary-card {
        background: rgba(255,255,255,.82);
        border: 1px solid rgba(235,238,244,.8) !important;
        border-radius: 10px;
        box-shadow: 0 8px 25px rgba(0,0,0,.08) !important;
    }
    .hostel-reservations > .card { background: transparent; border: 0 !important; box-shadow: none !important; }
    .hostel-reservations > .card > .card-body { padding: 0 !important; }
    .hostel-page .summary-card { height: 100%; padding: 16px; transition: transform .22s ease, box-shadow .22s ease; }
    .hostel-page .summary-card:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(23,105,232,.12) !important; }
    .hostel-page .summary-label { font-size: 12px; color: #6b7280; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
    .hostel-page .summary-value { margin-top: 8px; font-size: 26px; font-weight: 900; color: #0f172a; }
    .hostel-page .filter-panel {
        background: rgba(255,255,255,.78);
        border: 1px solid rgba(235,238,244,.8);
        border-radius: 10px;
        box-shadow: 0 8px 25px rgba(0,0,0,.06);
    }
    .hostel-page .form-label, #hostel-reservation-modals .form-label { font-size: 12px; color: #374151; font-weight: 700; }
    .hostel-page .form-control, .hostel-page .form-select,
    #hostel-reservation-modals .form-control {
        min-height: 38px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background-color: #fff;
        color: #111827;
        font-size: 13px;
    }
    .hostel-page .form-control:focus, .hostel-page .form-select:focus,
    #hostel-reservation-modals .form-control:focus { border-color: #1769e8; box-shadow: 0 0 0 3px rgba(23,105,232,.14); }
    .hostel-page .btn, #hostel-reservation-modals .btn { border-radius: 8px; font-size: 13px; font-weight: 600; padding: 8px 14px; transition: background .2s ease, color .2s ease; }
    .hostel-page .btn-primary { background: #1769d1; border-color: #1769d1; color: #fff; }
    .hostel-page .btn-primary:hover { background: #155bbb; border-color: #155bbb; }
    .hostel-page .btn-outline-primary, .hostel-page .btn-outline-secondary,
    .hostel-page .btn-outline-light, .hostel-page .btn-light,
    #hostel-reservation-modals .btn-outline-secondary {
        background: rgba(255,255,255,.35); border: 1px solid #1769e8; color: #1769d1;
    }
    .hostel-page .btn-outline-primary:hover, .hostel-page .btn-outline-secondary:hover,
    .hostel-page .btn-outline-light:hover, .hostel-page .btn-light:hover,
    #hostel-reservation-modals .btn-outline-secondary:hover { background: #1769d1; color: #fff; }
    .hostel-page .status-capsules { display: flex; flex-wrap: wrap; gap: 4px; padding: 4px; width: fit-content; border: 1px solid #dbe5f6; border-radius: 9px; background: rgba(255,255,255,.78); }
    .hostel-page .status-capsules .btn { border: 0; border-radius: 7px; }
    .hostel-page .status-capsules .btn-outline-primary { background: transparent; }
    .hostel-page .status-capsules .btn-outline-primary:hover { background: transparent; color: #0f172a; }
    .hostel-page .status-capsules .btn-primary { box-shadow: 0 3px 9px rgba(23,105,232,.2); }
    .hostel-page .table-responsive { padding: 16px; background: rgba(255,255,255,.82); border: 0 !important; border-radius: 10px !important; box-shadow: 0 8px 25px rgba(0,0,0,.08); }
    .hostel-page .table { font-size: 13px; color: #111827; }
    .hostel-page .table > :not(caption) > * > * { padding: 10px 12px; background: transparent; border-bottom-color: #e5e7eb; }
    .hostel-page .table thead th { background: #f9fafb; color: #374151; font-weight: 800; white-space: nowrap; }
    .hostel-page .table tbody tr:hover { background: #f9fafb; }
    .hostel-page .table .btn { padding: 5px 10px; font-size: 12px; }
    .hostel-page .badge, #hostel-reservation-modals .badge { font-size: 12px; font-weight: 600; }
    .hostel-create .booking-card { padding: 28px; }
    .hostel-create .booking-heading { color: #0f172a; font-size: 18px; font-weight: 800; border-bottom: 1px solid #e5e7eb; padding-bottom: 18px; margin-bottom: 24px; }
    .hostel-create textarea.form-control { min-height: 120px; }
    .hostel-create .features-heading { background: #eef3fa; color: #1769d1; padding: 18px 22px; }
    .hostel-create .feature-list { list-style: none; padding: 0; margin: 0; }
    .hostel-create .feature-list li { display: flex; gap: 12px; align-items: center; margin-bottom: 16px; }
    .hostel-create .feature-list i { color: #1769d1; font-size: 21px; }
    .hostel-create .help-panel { background: rgba(238,243,250,.65); border-top: 1px solid #e5e7eb; padding: 22px; }
    .hostel-create .create-button { min-height: 44px; }
    .hostel-details .card-body { padding: 24px; }
    .hostel-details .card h5 { color: #0f172a; font-size: 18px; font-weight: 800; }
    .hostel-details .card strong { display: block; margin-bottom: 5px; color: #6b7280; font-size: 12px; font-weight: 700; }
    #hostel-reservation-modals .modal-content { border: 1px solid #e5e7eb; border-radius: 12px; background: #fff; box-shadow: 0 18px 50px rgba(15,23,42,.2); color: #111827; overflow: hidden; }
    #hostel-reservation-modals .modal-header { padding: 20px 24px; background: #eef3fa !important; border-bottom: 1px solid #e5e7eb; }
    #hostel-reservation-modals .modal-title { color: #0f172a; font-weight: 800; }
    #hostel-reservation-modals .modal-body { padding: 24px; }
    #hostel-reservation-modals .modal-footer { border-top: 1px solid #e5e7eb; padding: 16px 24px; }
    @media (max-width: 575.98px) {
        .hostel-page { padding: 24px 12px 32px !important; }
        .hostel-create .booking-card, .hostel-details .card-body { padding: 20px; }
        .hostel-details > .d-flex { flex-wrap: wrap; gap: 16px; }
        .hostel-page .status-capsules { width: 100%; }
    }
    @media (prefers-reduced-motion: reduce) {
        .hostel-page .summary-card, .hostel-page .btn { transition: none; }
        .hostel-page .summary-card:hover { transform: none; }
    }
</style>
<div class="hostel-page hostel-details container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1 text-white">{{ $reservation->reference }}</h2>
            <p class="text-white mb-0">{{ $reservation->title }}</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge rounded-pill px-3 {{ $reservation->statusEnum()->badgeClass() }}">{{ $reservation->statusEnum()->label() }}</span>
            <a href="{{ route('hostel.index') }}" class="btn btn-outline-light">Back to list</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h5 class="mb-3">Stay details</h5>
                    <div class="row g-3">
                        <div class="col-md-6"><strong>Guest name</strong><div>{{ $reservation->hostelStay?->guest_name }}</div></div>
                        <div class="col-md-6"><strong>Room</strong><div>{{ $reservation->resourceNames() }}</div></div>
                        <div class="col-md-6"><strong>Room category</strong><div>{{ $reservation->hostelStay?->roomType?->name }}</div></div>
                        <div class="col-md-6"><strong>Location</strong><div>{{ $reservation->location?->name }}</div></div>
                        <div class="col-md-6"><strong>Check-in</strong><div>{{ $reservation->hostelStay?->check_in_at?->timezone(config('reservations.display_timezone'))->format('d M Y H:i') }}</div></div>
                        <div class="col-md-6"><strong>Check-out</strong><div>{{ $reservation->hostelStay?->check_out_at?->timezone(config('reservations.display_timezone'))->format('d M Y H:i') }}</div></div>
                        <div class="col-md-6"><strong>Guests</strong><div>{{ $reservation->hostelStay?->number_of_guests }}</div></div>
                        <div class="col-md-6"><strong>Requester</strong><div>{{ $reservation->requester?->name }}</div></div>
                        <div class="col-12"><strong>Special requirements</strong><div>{{ $reservation->hostelStay?->special_requirements ?: '—' }}</div></div>
                        @if($reservation->cancellation_reason)
                            <div class="col-12"><strong>Cancellation reason</strong><div>{{ $reservation->cancellation_reason }}</div></div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h5 class="mb-3">Status history</h5>
                    @forelse($reservation->statusHistory as $history)
                        <div class="mb-3 pb-3 border-bottom">
                            <div class="fw-semibold">{{ $history->from_status ? $history->from_status.' → ' : '' }}{{ $history->to_status }}</div>
                            <div class="small text-muted">{{ $history->actor?->name ?? 'System' }} · {{ $history->created_at?->timezone(config('reservations.display_timezone'))->format('d M Y H:i') }}</div>
                        </div>
                    @empty
                        <p class="text-muted mb-0">No history recorded.</p>
                    @endforelse
                </div>
            </div>
            @can('cancelHostel', $reservation)
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h5 class="mb-3">Cancel reservation</h5>
                        <form method="POST" action="{{ route('hostel.cancel', $reservation) }}">
                            @csrf
                            <textarea name="cancellation_reason" class="form-control mb-3" rows="3" required placeholder="Reason is required"></textarea>
                            <button class="btn btn-danger w-100">Cancel reservation</button>
                        </form>
                    </div>
                </div>
            @endcan
        </div>
    </div>
</div>
@endsection
