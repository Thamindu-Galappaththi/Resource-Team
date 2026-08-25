document.addEventListener('DOMContentLoaded', function () {

    const calendarElement =
        document.getElementById('resourceCalendar');

    if (!calendarElement) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Temporary calendar data
    |--------------------------------------------------------------------------
    |
    | This is sample data for the frontend.
    | Later we will replace this with real database data.
    |
    */

    const events = [

        {
            id: '1',
            title: 'Meeting Room A',
            start: '2026-08-25T09:00:00',
            end: '2026-08-25T11:00:00',

            extendedProps: {
                resource: 'Meeting Room A',
                owner: 'John Smith',
                location: 'Main Office',
                status: 'Approved'
            }
        },

        {
            id: '2',
            title: 'Laptop - Dell Latitude',
            start: '2026-08-26T10:00:00',
            end: '2026-08-26T13:00:00',

            extendedProps: {
                resource: 'Laptop - Dell Latitude',
                owner: 'Sarah Fernando',
                location: 'IT Department',
                status: 'Approved'
            }
        },

        {
            id: '3',
            title: 'Projector - Epson',
            start: '2026-08-27T14:00:00',
            end: '2026-08-27T16:00:00',

            extendedProps: {
                resource: 'Projector - Epson',
                owner: 'David Perera',
                location: 'Conference Room',
                status: 'Pending'
            }
        },

        {
            id: '4',
            title: 'Conference Room B',
            start: '2026-08-28T09:30:00',
            end: '2026-08-28T12:00:00',

            extendedProps: {
                resource: 'Conference Room B',
                owner: 'Nimal Silva',
                location: 'Main Office',
                status: 'Approved'
            }
        },

        {
            id: '5',
            title: 'MacBook Pro',
            start: '2026-08-29T13:00:00',
            end: '2026-08-29T15:00:00',

            extendedProps: {
                resource: 'MacBook Pro',
                owner: 'Amal Perera',
                location: 'IT Department',
                status: 'Approved'
            }
        }

    ];


    /*
    |--------------------------------------------------------------------------
    | FullCalendar
    |--------------------------------------------------------------------------
    */

    const calendar = new FullCalendar.Calendar(
        calendarElement,
        {

            initialView: 'dayGridMonth',

            initialDate: '2026-08-25',

            height: 'auto',

            contentHeight: 680,

            expandRows: true,

            firstDay: 1,

            navLinks: true,

            editable: false,

            selectable: false,

            nowIndicator: true,

            dayMaxEvents: 3,

            headerToolbar: {

                left: 'prev,next today',

                center: 'title',

                right: 'dayGridMonth,timeGridWeek,timeGridDay'

            },

            buttonText: {

                today: 'Today',

                month: 'Month',

                week: 'Week',

                day: 'Day'

            },

            events: events,


            /*
            |--------------------------------------------------------------------------
            | Event click
            |--------------------------------------------------------------------------
            */

            eventClick: function (info) {

                const event =
                    info.event;

                const details =
                    event.extendedProps;


                document.getElementById(
                    'detailResource'
                ).textContent =
                    details.resource || event.title;


                document.getElementById(
                    'detailDate'
                ).textContent =
                    formatDate(event.start);


                document.getElementById(
                    'detailTime'
                ).textContent =
                    formatTimeRange(
                        event.start,
                        event.end
                    );


                document.getElementById(
                    'detailOwner'
                ).textContent =
                    details.owner || '-';


                document.getElementById(
                    'detailLocation'
                ).textContent =
                    details.location || '-';


                document.getElementById(
                    'detailStatus'
                ).textContent =
                    details.status || '-';


                openReservationModal();

            }

        }
    );


    calendar.render();


    /*
    |--------------------------------------------------------------------------
    | Resource filter
    |--------------------------------------------------------------------------
    */

    const resourceFilter =
        document.getElementById('resourceFilter');


    const resources =
        [...new Set(
            events.map(event =>
                event.extendedProps.resource
            )
        )];


    resources.forEach(function (resource) {

        const option =
            document.createElement('option');

        option.value = resource;

        option.textContent = resource;

        resourceFilter.appendChild(option);

    });


    resourceFilter.addEventListener(
        'change',
        function () {

            applyFilters();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Location filter
    |--------------------------------------------------------------------------
    */

    const locationFilter =
        document.getElementById('locationFilter');


    const locations =
        [...new Set(
            events.map(event =>
                event.extendedProps.location
            )
        )];


    locations.forEach(function (location) {

        const option =
            document.createElement('option');

        option.value = location;

        option.textContent = location;

        locationFilter.appendChild(option);

    });


    locationFilter.addEventListener(
        'change',
        function () {

            applyFilters();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    */

    function applyFilters() {

        const selectedResource =
            resourceFilter.value;

        const selectedLocation =
            locationFilter.value;


        calendar.removeAllEvents();


        const filteredEvents =
            events.filter(function (event) {

                const resourceMatches =
                    selectedResource === 'all' ||
                    event.extendedProps.resource ===
                    selectedResource;


                const locationMatches =
                    selectedLocation === 'all' ||
                    event.extendedProps.location ===
                    selectedLocation;


                return (
                    resourceMatches &&
                    locationMatches
                );

            });


        calendar.addEventSource(filteredEvents);

    }


    /*
    |--------------------------------------------------------------------------
    | Modal
    |--------------------------------------------------------------------------
    */

    const modal =
        document.getElementById(
            'reservationModal'
        );


    const closeButton =
        document.getElementById(
            'closeReservationModal'
        );


    const overlay =
        document.querySelector(
            '.reservation-modal-overlay'
        );


    function openReservationModal() {

        modal.classList.add('show');

        modal.setAttribute(
            'aria-hidden',
            'false'
        );

    }


    function closeReservationModal() {

        modal.classList.remove('show');

        modal.setAttribute(
            'aria-hidden',
            'true'
        );

    }


    closeButton.addEventListener(
        'click',
        closeReservationModal
    );


    overlay.addEventListener(
        'click',
        closeReservationModal
    );


    document.addEventListener(
        'keydown',
        function (event) {

            if (event.key === 'Escape') {

                closeReservationModal();

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | Date formatting
    |--------------------------------------------------------------------------
    */

    function formatDate(date) {

        if (!date) {
            return '-';
        }


        return date.toLocaleDateString(
            'en-US',
            {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            }
        );

    }


    function formatTimeRange(start, end) {

        if (!start) {
            return '-';
        }


        const startTime =
            start.toLocaleTimeString(
                'en-US',
                {
                    hour: 'numeric',
                    minute: '2-digit'
                }
            );


        if (!end) {
            return startTime;
        }


        const endTime =
            end.toLocaleTimeString(
                'en-US',
                {
                    hour: 'numeric',
                    minute: '2-digit'
                }
            );


        return `${startTime} - ${endTime}`;

    }

});
