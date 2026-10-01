@extends('layouts.app')

@section('title', 'Reservation Calendar')

@section('content')

<style>

    /* =========================================================
       CALENDAR CONTAINER
       ========================================================= */

    .reservation-calendar-wrapper {
        width: 100%;
        padding: 35px 25px 50px 25px;
    }


    /* =========================================================
       MONTH NAVIGATION
       ========================================================= */

    .calendar-navigation {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 5px;

        padding: 5px 10px 22px 10px;

        background: rgba(255,255,255,0.75) !important;

        border-radius: 10px;

    }


    /* Previous / Next buttons */

    .nav-button {
        min-width: 92px;
        height: 38px;

        display: flex;
        align-items: center;
        justify-content: center;

        border: 1px solid #1769e8 !important;
        border-radius: 8px !important;

        background: rgba(255, 255, 255, 0.20) !important;

        color: #1769d1 !important;

        font-size: 14px;
        font-weight: 500;

        transition: 0.2s ease;
    }


    .nav-button:hover {
        background: #1769d1 !important;
        color: white !important;
    }


    /* Month title */

    .month-title {
        border: none !important;
        border-radius: 6px !important;

        background: rgba(255, 255, 255, 0.32) !important;

        color: #1769e8 !important;

        padding: 8px 18px;

        font-size: 17px;
        font-weight: 600;

        box-shadow: 0 2px 8px rgba(0,0,0,0.04);

        transition: 0.2s ease;


    }


    .month-title:hover {
        background: rgba(255,255,255,0.95) !important;
    }


    /* =========================================================
       MAIN CALENDAR CARD
       ========================================================= */

    .calendar-card {
        border: none !important;
        border-radius: 10px !important;

        background: rgba(255,255,255,0.74) !important;

        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);

        box-shadow: 0 8px 25px rgba(0,0,0,0.08) !important;

        overflow: hidden;
    }


    .calendar-card .card-body {
        padding: 16px !important;
    }


    /* =========================================================
       DAYS HEADER
       ========================================================= */

    .calendar-header {
        margin: 0 !important;

        background: rgba(238,243,250,0.75) !important;

        border: none !important;

        border-radius: 6px 6px 0 0;
    }


    .calendar-header .col {
        padding-top: 14px !important;
        padding-bottom: 14px !important;

        color: #111827;

        font-size: 14px;
        font-weight: 500;

        background: transparent !important;

        border: none !important;
    }


    /* =========================================================
       CALENDAR ROWS
       ========================================================= */

    .calendar-row {
        margin: 0 !important;
    }


    /* =========================================================
       DATE CELLS
       ========================================================= */

    .calendar-date,
    .empty-date {

        min-height: 105px;

        display: flex;
        align-items: center;
        justify-content: center;

        position: relative;

        margin: 2px;

        border: 2px solid rgba(235,238,244,0.80);

        border-radius: 7px;

        background: rgba(255,255,255,0.72);

        transition: all 0.2s ease;
    }


    /* Actual date */

    .calendar-date {
        cursor: pointer;
    }


    .calendar-date:hover {
        background: rgba(23, 105, 203, 0.95);

        box-shadow: 0 3px 10px rgba(0,0,0,0.06);

        transform: translateY(-1px);
    }

    .date-number:hover{
        color: white;
    }


    /* =========================================================
       NORMAL DATE NUMBER
       ========================================================= */

    .date-number {

        padding: 50px 50px 50px 50px;
        width: 100%;

        text-align: center;

        color: #111827;

        font-size: 14px;

        font-weight: 500;
    }

    


    /* =========================================================
       TODAY
       ========================================================= */

    .today-circle {

        width: 47px;
        height: 47px;

        display: flex;

        align-items: center;
        justify-content: center;

        border-radius: 50%;

        background: #1769e8;

        color: white;

        font-size: 14px;

        font-weight: 600;

        box-shadow: 0 3px 8px rgba(23,105,232,0.25);
    }

    .today-circle:hover{
        

    }


    /* =========================================================
       EMPTY DATE CELLS
       ========================================================= */

    .empty-date {

        cursor: default;

        background: rgba(255,255,255,0.55);

        border-color: rgba(235,238,244,0.65);
    }


    /* =========================================================
       MONTH MODAL BUTTONS
       ========================================================= */

    .month-btn {

        border-radius: 6px;

        padding: 10px 5px;

        font-size: 14px;
    }


    /* =========================================================
       RESPONSIVE
       ========================================================= */

    @media (max-width: 992px) {

        .reservation-calendar-wrapper {
            padding: 25px 15px 40px 15px;
        }

        .calendar-date,
        .empty-date {
            min-height: 90px;
        }

    }


    @media (max-width: 768px) {

        .calendar-navigation {
            padding-left: 5px;
            padding-right: 5px;
        }

        .nav-button {
            min-width: 80px;
            font-size: 13px;
        }

        .month-title {
            font-size: 15px;
        }

        .calendar-date,
        .empty-date {
            min-height: 75px;
        }

        .calendar-header .col {
            font-size: 12px;
        }

    }

</style>


<!-- =============================================================
     MAIN CALENDAR
     ============================================================= -->

<div class="reservation-calendar-wrapper">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="h4 mb-1">Reservation Calendar</h2>
            <p class="text-muted mb-0">Click a date to view bookings or create one for that slot.</p>
        </div>
        @if($canCreate)
            <a href="{{ route('reservations.create') }}" class="btn btn-primary">Create Reservation</a>
        @endif
    </div>

    <form method="GET" action="{{ route('reservations.calendar') }}" class="card border-0 shadow-sm mb-3">
        <div class="card-body row g-2 align-items-end">
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="month" value="{{ $month }}">
            <div class="col-md-3">
                <label class="form-label">Location</label>
                <select name="location_id" class="form-select">
                    <option value="">All</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" @selected(request('location_id') == $location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Category</label>
                <select name="resource_category_id" class="form-select">
                    <option value="">All</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('resource_category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    @foreach($statuses as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-grid">
                <button class="btn btn-outline-primary">Apply filters</button>
            </div>
        </div>
    </form>

    <!-- =========================================================
         MONTH NAVIGATION
         ========================================================= -->

    <div class="calendar-navigation">


        <!-- Previous Month -->

        <a href="{{ route('reservations.calendar', array_merge(request()->except(['year', 'month']), [
            'year' => $currentDate->copy()->subMonth()->year,
            'month' => $currentDate->copy()->subMonth()->month
        ])) }}"
           class="btn nav-button">
           &lt; Previous
        </a>



        <!-- Month Name -->

        <button type="button"
                class="btn month-title"
                data-bs-toggle="modal"
                data-bs-target="#monthModal">

                {{ $currentDate->format('F Y') }}
        </button>


        <!-- Next Month -->

        <a href="{{ route('reservations.calendar', array_merge(request()->except(['year', 'month']), [
            'year' => $currentDate->copy()->addMonth()->year,
            'month' => $currentDate->copy()->addMonth()->month
        ])) }}"
           class="btn nav-button">
           Next &gt;
        </a>


    </div>



    <!-- =========================================================
         CALENDAR CARD
         ========================================================= -->

    <div class="card calendar-card">

        <div class="card-body">


            <!-- =================================================
                 DAYS
                 ================================================= -->

            <div class="row text-center fw-bold calendar-header">

                <div class="col"> Sunday </div>

                <div class="col"> Monday </div>

                <div class="col"> Tuesday </div>

                <div class="col"> Wednesday </div>

                <div class="col"> Thursday </div>

                <div class="col"> Friday </div>

                <div class="col"> Saturday </div>

            </div>



            <!-- =================================================
                 CALENDAR DATES
                 ================================================= -->

            <div class="row calendar-row">


                {{-- Empty cells before first day --}}

                @for ($i = 0; $i < $startingDayOfWeek; $i++)

                    <div class="col empty-date">
                    </div>

                @endfor



                {{-- Actual Dates --}}

                @for ($day = 1; $day <= $daysInMonth; $day++)

                    @php

                        $date = $currentDate->copy()->day($day);

                        $isToday = $date->isToday();

                        $dayReservations = $calendarReservations
                            ->where('reservation_date', $date->format('Y-m-d'))
                            ->values();

                    @endphp


                    <div class="col calendar-date"
                         data-bs-toggle="modal"
                         data-bs-target="#eventModal"
                         data-display-date="{{ $date->format('F d, Y') }}"
                         data-date="{{ $date->format('Y-m-d') }}"
                         data-reservations='@json($dayReservations)'>


                        @if ($isToday)

                            <div class="today-circle">

                                {{ $day }}

                            </div>

                        @else

                            <div class="date-number">

                                {{ $day }}

                            </div>

                        @endif

                        @if($dayReservations->count())
                            <span class="position-absolute bottom-0 start-50 translate-middle-x mb-1 badge rounded-pill text-bg-primary">
                                {{ $dayReservations->count() }}
                            </span>
                        @endif


                    </div>



                    {{-- Start new row after Saturday --}}

                    @if (($startingDayOfWeek + $day) % 7 == 0)

                        </div>


                        @if ($day < $daysInMonth)

                            <div class="row calendar-row">

                        @endif

                    @endif

                @endfor



                {{-- Empty cells after last day --}}

                @php

                    $remainingCells =
                        (7 - (($startingDayOfWeek + $daysInMonth) % 7)) % 7;

                @endphp


                @for ($i = 0; $i < $remainingCells; $i++)

                    <div class="col empty-date">
                    </div>

                @endfor


            </div>

        </div>

    </div>

</div>



<!-- =============================================================
     EVENT MODAL
     ============================================================= -->

<div class="modal fade"
     id="eventModal"
     tabindex="-1">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">


            <div class="modal-header">

                <h5 class="modal-title">
                    Events
                </h5>


                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                </button>

            </div>


            <div class="modal-body">

                <p id="selectedDate"
                   class="text-muted">
                </p>


                <div id="reservationList"></div>

            </div>


        </div>

    </div>

</div>



<!-- =============================================================
     MONTH MODAL
     ============================================================= -->

<div class="modal fade"
     id="monthModal"
     tabindex="-1">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">


            <!-- Modal Header -->

            <div class="modal-header">


                <!-- Previous Year -->

                <button type="button"
                        class="btn btn-sm btn-outline-secondary"
                        id="previousYear">

                    &lt;

                </button>



                <!-- Year -->

                <h5 class="modal-title"
                    id="yearTitle">

                    {{ $currentDate->year }}

                </h5>



                <!-- Next Year -->

                <button type="button"
                        class="btn btn-sm btn-outline-secondary"
                        id="nextYear">

                    &gt;

                </button>


            </div>



            <!-- Modal Body -->

            <div class="modal-body">

                <div class="row g-2">


                    @php

                        $months = [
                            'January',
                            'February',
                            'March',
                            'April',
                            'May',
                            'June',
                            'July',
                            'August',
                            'September',
                            'October',
                            'November',
                            'December'
                        ];

                    @endphp



                    @foreach($months as $index => $monthName)

                        <div class="col-4">

                            <button
                                type="button"
                                class="btn btn-outline-primary w-100 month-btn"
                                data-month="{{ $index + 1 }}">

                                {{ $monthName }}

                            </button>

                        </div>

                    @endforeach


                </div>

            </div>


        </div>

    </div>

</div>



<script>

    /* =========================================================
       EVENT POPUP
       ========================================================= */

    document.querySelectorAll('[data-bs-target="#eventModal"]')
    .forEach(function(day) {

        day.addEventListener('click', function() {

            // Get selected date
            const displayDate =
                this.getAttribute('data-display-date');

            // Get reservations from data attribute
            const reservations =
                JSON.parse(this.getAttribute('data-reservations'));

            // Show selected date
            document.getElementById('selectedDate').textContent =
                displayDate;


            // Get reservation list container
            const reservationList =
                document.getElementById('reservationList');


            // Clear previous reservations
            reservationList.innerHTML = '';


            // If there are no reservations
            const selectedIsoDate = this.getAttribute('data-date');
            const canCreate = @json($canCreate);
            const createUrl = @json(route('reservations.create'));

            if (reservations.length === 0) {
                reservationList.innerHTML = `
                    <p>No bookings for this day.</p>
                    ${canCreate ? `<a class="btn btn-primary btn-sm" href="${createUrl}?date=${selectedIsoDate}">Create reservation</a>` : ''}
                `;
                return;
            }

            reservations.forEach(function(reservation) {
                reservationList.innerHTML += `
                    <div class="border rounded p-3 mb-2">
                        <div class="d-flex justify-content-between">
                            <h6 class="mb-1">${reservation.reference ?? reservation.title}</h6>
                            <span class="badge bg-light text-dark">${reservation.status_label ?? ''}</span>
                        </div>
                        <p class="mb-1">${reservation.title ?? ''}</p>
                        <small class="text-muted d-block">${reservation.resources ?? ''}</small>
                        <small class="text-muted d-block">${reservation.requester ?? ''} · ${reservation.location ?? ''}</small>
                        <small class="text-muted d-block">${reservation.start_time} - ${reservation.end_time}</small>
                        ${reservation.url ? `<a class="btn btn-sm btn-outline-primary mt-2" href="${reservation.url}">View details</a>` : ''}
                    </div>
                `;
            });

            if (canCreate) {
                reservationList.innerHTML += `<a class="btn btn-primary btn-sm" href="${createUrl}?date=${selectedIsoDate}">Create reservation</a>`;
            }

        });

    });



    /* =========================================================
       MONTH POPUP
       ========================================================= */

    let selectedYear = {{ $currentDate->year }};


    const yearTitle =
        document.getElementById('yearTitle');



    /* =========================================================
       PREVIOUS YEAR
       ========================================================= */

    document.getElementById('previousYear')
        .addEventListener('click', function() {

            selectedYear--;

            yearTitle.textContent = selectedYear;

        });



    /* =========================================================
       NEXT YEAR
       ========================================================= */

    document.getElementById('nextYear')
        .addEventListener('click', function() {

            selectedYear++;

            yearTitle.textContent = selectedYear;

        });



    /* =========================================================
       SELECT MONTH
       ========================================================= */

    document.querySelectorAll('.month-btn')
        .forEach(function(month) {

            month.addEventListener('click', function() {

                let selectedMonth =
                    this.getAttribute('data-month');


                window.location.href =
                    "{{ route('reservations.calendar') }}"
                    + "?year=" + selectedYear
                    + "&month=" + selectedMonth;

            });

        });

</script>

@endsection