<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Weekly approved reservations — {{ $label }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #e8eef8; color: #14223a; font-family: Segoe UI, Arial, sans-serif; font-size: 13px; }
        .toolbar { position: sticky; top: 0; z-index: 2; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: space-between; padding: 12px 16px; background: #14315e; color: #fff; }
        .toolbar p { margin: 0; font-size: 13px; }
        .toolbar a, .toolbar button { display: inline-flex; align-items: center; gap: 6px; border: 0; border-radius: 8px; padding: 8px 12px; font-weight: 600; text-decoration: none; cursor: pointer; }
        .toolbar .pdf { background: #fff; color: #1769c2; }
        .toolbar .png { background: #16a77a; color: #fff; }
        .wrap { padding: 18px; }
        #week-sheet { background: #fff; padding: 22px 24px 28px; border-radius: 8px; box-shadow: 0 8px 24px rgba(20,34,58,.12); }
        .head { width: 100%; border-bottom: 2px solid #1769c2; padding-bottom: 8px; margin-bottom: 10px; }
        .head td { vertical-align: middle; }
        .logo { height: 38px; }
        .title { margin: 0; color: #14315e; font-size: 22px; font-weight: 700; }
        .sub { margin: 4px 0 0; color: #50607a; font-size: 13px; }
        .date-box { text-align: right; }
        .date { display: inline-block; padding: 6px 12px; border-radius: 6px; background: #1769c2; color: #fff; font-size: 14px; font-weight: 700; }
        .meta { margin-top: 4px; color: #50607a; font-size: 12px; }
        .summary { width: 100%; margin-bottom: 10px; border-collapse: separate; border-spacing: 6px 0; }
        .summary td { padding: 8px 10px; border: 1px solid #d5def0; border-radius: 6px; background: #f5f8ff; text-align: center; }
        .summary strong { display: block; color: #14315e; font-size: 16px; }
        .summary span { color: #50607a; font-size: 11px; }
        h2 { margin: 16px 0 6px; color: #14315e; font-size: 15px; }
        table.list { width: 100%; border-collapse: collapse; }
        table.list th { padding: 6px; border: 1px solid #b9c7de; background: #e8f0fb; color: #14315e; font-size: 11px; text-align: left; text-transform: uppercase; }
        table.list td { padding: 7px 6px; border: 1px solid #ccd6e6; vertical-align: top; }
        table.list tr:nth-child(even) td { background: #f8fafd; }
        .no { width: 22px; text-align: center; color: #50607a; }
        .ref { font-weight: 700; white-space: nowrap; }
        .name { font-weight: 700; }
        .muted { color: #50607a; font-size: 12px; }
        .when { white-space: nowrap; }
        .tag { display: inline-block; padding: 2px 6px; border-radius: 8px; font-size: 11px; font-weight: 700; }
        .tag-special { background: #f3e8ff; color: #7e22ce; }
        .empty { padding: 24px; border: 1px dashed #b9c7de; color: #50607a; text-align: center; }
        .status { font-size: 12px; color: #50607a; margin: 0 16px 12px; }
    </style>
</head>
<body>
    <div class="toolbar" data-html2canvas-ignore="true">
        <p>This week’s approved bookings — download as an image to share.</p>
        <div>
            <a class="pdf" href="{{ $pdfUrl }}">Download PDF</a>
            <button type="button" class="png" id="week-sheet-png" data-filename="{{ $filename }}.png">Download image</button>
        </div>
    </div>
    <p class="status" id="week-sheet-status" data-html2canvas-ignore="true" hidden></p>
    <div class="wrap">
        <div id="week-sheet">
            <table class="head">
                <tr>
                    <td style="width:62%">
                        <table><tr>
                            @if($logo)
                                <td style="padding-right:10px"><img src="{{ $logo }}" class="logo" alt="Nebula"></td>
                            @endif
                            <td>
                                <p class="title">Weekly approved reservations</p>
                                <p class="sub">Share this sheet for halls and labs booked this week, including special-approval details.</p>
                            </td>
                        </tr></table>
                    </td>
                    <td class="date-box">
                        <span class="date">{{ $label }}</span>
                        <div class="meta">Generated {{ $generatedAt->format('d M Y, H:i') }} by {{ $generatedBy }}</div>
                    </td>
                </tr>
            </table>
            @include('reservations.partials.week-sheet-table')
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script src="{{ asset('js/week-sheet-image.js') }}?v=1"></script>
</body>
</html>
