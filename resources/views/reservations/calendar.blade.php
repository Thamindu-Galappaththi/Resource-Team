@extends('layouts.app')

@section('title', 'Reservation Calendar')

@section('content')

<div class="container-fluid mt-4">

    <div class="card border-0 shadow-sm">

        <div class="card-body">

            <!-- Month Navigation -->
            <div class="d-flex justify-content-between align-items-center mb-4">

                <a href="{{ route('reservations.calendar', [
                    'year' => $currentDate->copy()->subMonth()->year,
                    'month' => $currentDate->copy()->subMonth()->month
                ]) }}"
                   class="btn btn-outline-secondary">

                    &lt; Previous

                </a>


                <!-- Click Month Name -->
                <button type="button"
                        class="btn btn-light fw-bold fs-4 border-0"
                        data-bs-toggle="modal"
                        data-bs-target="#monthModal">

                    {{ $currentDate->format('F Y') }}

                </button>


                <a href="{{ route('reservations.calendar', [
                    'year' => $currentDate->copy()->addMonth()->year,
                    'month' => $currentDate->copy()->addMonth()->month
                ]) }}"
                   class="btn btn-outline-secondary">

                    Next &gt;

                </a>

            </div>


            <!-- Days -->
            <div class="row text-center fw-bold bg-light border">

                <div class="col py-3">Sunday</div>
                <div class="col py-3">Monday</div>
                <div class="col py-3">Tuesday</div>
                <div class="col py-3">Wednesday</div>
                <div class="col py-3">Thursday</div>
                <div class="col py-3">Friday</div>
                <div class="col py-3">Saturday</div>

            </div>


            <!-- Calendar -->
            <div class="row">

                {{-- Empty cells before first day --}}
                @for ($i = 0; $i < $startingDayOfWeek; $i++)

                    <div class="col border p-3"
                         style="min-height:120px;">
                    </div>

                @endfor


                {{-- Actual Dates --}}
                @for ($day = 1; $day <= $daysInMonth; $day++)

                    @php
                        $date = $currentDate->copy()->day($day);
                    @endphp

                    <div class="col border p-3"
                         style="min-height:120px; cursor:pointer;"
                         data-bs-toggle="modal"
                         data-bs-target="#eventModal"
                         data-display-date="{{ $date->format('F d, Y') }}">

                        <strong>{{ $day }}</strong>

                    </div>


                    @if (($startingDayOfWeek + $day) % 7 == 0)

                        </div>

                        @if ($day < $daysInMonth)

                            <div class="row">

                        @endif

                    @endif

                @endfor


                {{-- Empty cells after last day --}}
                @php
                    $remainingCells =
                    (7-(($startingDayOfWeek+$daysInMonth)%7))%7;
                @endphp

                @for ($i = 0; $i < $remainingCells; $i++)

                    <div class="col border p-3"
                         style="min-height:120px;">
                    </div>

                @endfor

            </div>

        </div>

    </div>

</div>



<!-- ================= EVENT MODAL ================= -->

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

                <p>
                    No events for this day.
                </p>

            </div>

        </div>

    </div>

</div>



<!-- ================= MONTH MODAL ================= -->

<div class="modal fade"
     id="monthModal"
     tabindex="-1">

    <div class="modal-dialog modal-dialog-centered">

        <div class="modal-content">

            <div class="modal-header">

                <button type="button"
                        class="btn btn-sm btn-outline-secondary"
                        id="previousYear">

                    &lt;

                </button>


                <h5 class="modal-title"
                    id="yearTitle">

                    {{ $currentDate->year }}

                </h5>


                <button type="button"
                        class="btn btn-sm btn-outline-secondary"
                        id="nextYear">

                    &gt;

                </button>

            </div>


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

    /* Event Popup */

    document.querySelectorAll('[data-bs-target="#eventModal"]')
        .forEach(function(day){

            day.addEventListener('click',function(){

                document.getElementById('selectedDate').textContent =
                    this.getAttribute('data-display-date');

            });

        });



    /* Month Popup */

    let selectedYear = {{ $currentDate->year }};

    const yearTitle =
        document.getElementById('yearTitle');

    document.getElementById('previousYear')
        .addEventListener('click',function(){

            selectedYear--;

            yearTitle.textContent = selectedYear;

        });


    document.getElementById('nextYear')
        .addEventListener('click',function(){

            selectedYear++;

            yearTitle.textContent = selectedYear;

        });



    document.querySelectorAll('.month-btn')
        .forEach(function(month){

            month.addEventListener('click',function(){

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