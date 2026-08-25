@extends('layouts.app')

@section('content')

<div class="resource-calendar-wrapper">

    <div class="resource-calendar-card">

        {{-- Page Header --}}
        <div class="resource-calendar-header">

            <div>
                <h1 class="resource-calendar-title">
                    Resource Calendar
                </h1>

                <p class="resource-calendar-subtitle">
                    View resource reservations and booking schedules
                </p>
            </div>

            <div class="resource-calendar-filters">

                <div class="calendar-filter">
                    <label for="resourceFilter">Resource</label>

                    <select id="resourceFilter">
                        <option value="all">All Resources</option>
                    </select>
                </div>

                <div class="calendar-filter">
                    <label for="locationFilter">Location</label>

                    <select id="locationFilter">
                        <option value="all">All Locations</option>
                    </select>
                </div>

            </div>

        </div>


        {{-- Calendar --}}
        <div class="calendar-main">

            <div id="resourceCalendar"></div>

        </div>

    </div>

</div>


{{-- Reservation Details Modal --}}
<div
    id="reservationModal"
    class="reservation-modal"
    aria-hidden="true"
>

    <div class="reservation-modal-overlay"></div>

    <div class="reservation-modal-box">

        <button
            type="button"
            id="closeReservationModal"
            class="reservation-modal-close"
        >
            &times;
        </button>

        <div class="reservation-modal-header">

            <div class="reservation-modal-icon">
                <i class="ti ti-calendar-event"></i>
            </div>

            <div>
                <h2>Reservation Details</h2>
                <p>Booking information</p>
            </div>

        </div>


        <div class="reservation-details">

            <div class="reservation-detail-row">

                <div class="detail-label">
                    Resource
                </div>

                <div
                    id="detailResource"
                    class="detail-value"
                >
                    -
                </div>

            </div>


            <div class="reservation-detail-row">

                <div class="detail-label">
                    Date
                </div>

                <div
                    id="detailDate"
                    class="detail-value"
                >
                    -
                </div>

            </div>


            <div class="reservation-detail-row">

                <div class="detail-label">
                    Booking Time
                </div>

                <div
                    id="detailTime"
                    class="detail-value"
                >
                    -
                </div>

            </div>


            <div class="reservation-detail-row">

                <div class="detail-label">
                    Reservation Owner
                </div>

                <div
                    id="detailOwner"
                    class="detail-value"
                >
                    -
                </div>

            </div>


            <div class="reservation-detail-row">

                <div class="detail-label">
                    Location
                </div>

                <div
                    id="detailLocation"
                    class="detail-value"
                >
                    -
                </div>

            </div>


            <div class="reservation-detail-row">

                <div class="detail-label">
                    Status
                </div>

                <div
                    id="detailStatus"
                    class="detail-value"
                >
                    -
                </div>

            </div>

        </div>

    </div>

</div>

@endsection


@push('styles')

{{-- FullCalendar --}}
<link
    href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.19/index.global.min.css"
    rel="stylesheet"
/>

{{-- Resource Calendar CSS --}}
<link
    rel="stylesheet"
    href="{{ asset('css/resource-calendar.css') }}"
/>

@endpush


@push('scripts')

{{-- FullCalendar --}}
<script
    src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.19/index.global.min.js">
</script>

{{-- Resource Calendar JS --}}
<script
    src="{{ asset('js/resource-calendar.js') }}">
</script>

@endpush