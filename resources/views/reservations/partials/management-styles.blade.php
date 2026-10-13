<style>
    .reservation-page {
        width: 100%;
        min-height: 100vh;
        margin: 0 auto;
        padding: 35px 25px 50px;
        color: #111827;
        font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
    }
    .reservation-page h1, .reservation-page h2, .reservation-page h3 {
        color: #0f172a;
        font-weight: 800;
        letter-spacing: -.02em;
    }
    .reservation-page .reservation-page-header > div > h1,
    .reservation-page .reservation-page-header > div > h2,
    .reservation-page .reservation-page-header > div > p,
    .reservation-page .reservation-page-header > div > nav,
    .reservation-page .reservation-page-header > div > nav a,
    .reservation-page .reservation-page-header > div > nav i {
        color: #fff !important;
    }
    .reservation-page h1, .reservation-page h2 { font-size: 26px; }
    .reservation-page p.text-muted { color: #6b7280 !important; font-size: 14px; }
    .reservation-page .card {
        background: rgba(255, 255, 255, .84);
        border: 1px solid rgba(235, 238, 244, .86) !important;
        border-radius: 10px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, .08) !important;
        color: #111827;
    }
    .reservation-page .form-label { color: #374151; font-size: 12px; font-weight: 700; }
    .reservation-page .form-control, .reservation-page .form-select {
        min-height: 38px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background-color: #fff;
        color: #111827;
        font-size: 13px;
    }
    .reservation-page .form-control:focus, .reservation-page .form-select:focus {
        border-color: #1769e8;
        box-shadow: 0 0 0 3px rgba(23, 105, 232, .14);
    }
    .reservation-page .btn { border-radius: 8px; font-size: 13px; font-weight: 600; padding: 8px 14px; }
    .reservation-page .btn-primary { background: #1769d1; border-color: #1769d1; }
    .reservation-page .btn-primary:hover { background: #155bbb; border-color: #155bbb; }
    .reservation-page .btn-outline-primary, .reservation-page .btn-outline-secondary {
        border-color: #1769e8;
        color: #1769d1;
    }
    .reservation-page .btn-outline-primary:hover, .reservation-page .btn-outline-secondary:hover {
        background: #1769d1;
        color: #fff;
    }
    .reservation-page .table-responsive {
        padding: 16px;
        border: 0 !important;
        border-radius: 10px !important;
        background: rgba(255, 255, 255, .9);
        box-shadow: 0 8px 25px rgba(0, 0, 0, .08);
    }
    .reservation-page .table { color: #111827; font-size: 13px; }
    .reservation-page .table > :not(caption) > * > * {
        padding: 10px 12px;
        background: transparent;
        border-bottom-color: #e5e7eb;
    }
    .reservation-page .table thead th { background: #f9fafb; color: #374151; font-weight: 800; white-space: nowrap; }
    .reservation-page .table tbody tr:hover { background: #f9fafb; }
    .reservation-page .table .btn { padding: 5px 10px; font-size: 12px; }
    .reservation-page .summary-card { height: 100%; padding: 16px; }
    .reservation-page .summary-label { color: #6b7280; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; }
    .reservation-page .summary-value { margin-top: 8px; color: #0f172a; font-size: 26px; font-weight: 900; }

    /* Keep the calendar surface white while sharing the Hostel Management styling. */
    .reservation-page.reservation-calendar-page .calendar-card,
    .reservation-page.reservation-calendar-page .calendar-date,
    .reservation-page.reservation-calendar-page .empty-date {
        background: rgba(255, 255, 255, .94) !important;
    }
    .reservation-page.reservation-calendar-page .calendar-header { background: #eef3fa !important; }

    @media (max-width: 575.98px) {
        .reservation-page { padding: 24px 12px 32px; }
    }
</style>
