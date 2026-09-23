@extends('layouts.app')

@section('title', 'Canteen Reservation Details')

@section('content')
<div class="container-fluid py-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Reservation {{ $reservation->reservation_ref }}</h4>
            <span class="badge bg-light text-dark">{{ $reservation->status }}</span>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6"><strong>Reservation Name</strong><div>{{ $reservation->reservation_name }}</div></div>
                <div class="col-md-6"><strong>Requester</strong><div>{{ $reservation->requestedBy?->name ?? 'Unknown' }}</div></div>
                <div class="col-md-6"><strong>Meal Type</strong><div>{{ ucfirst(str_replace('_', ' ', $reservation->meal_type)) }}</div></div>
                <div class="col-md-6"><strong>Date & time</strong><div>{{ $reservation->reservation_date->format('d M Y') }} at {{ $reservation->reservation_time }}</div></div>
                <div class="col-md-12"><strong>Order Details</strong><div>{{ $reservation->order_details ?? 'N/A' }}</div></div>
                <div class="col-md-12"><strong>Special Remarks</strong><div>{{ $reservation->special_remarks ?? 'N/A' }}</div></div>
            </div>
            @if($reservation->approval_comments)
                <div class="alert alert-light border mt-4 mb-0">
                    <strong>Approval Comment:</strong> {{ $reservation->approval_comments }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
