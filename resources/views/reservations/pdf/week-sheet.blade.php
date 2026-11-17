<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Weekly approved reservations — {{ $label }}</title>
    <style>
        @page { margin: 22px 24px 40px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #14223a; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        .head { width: 100%; border-bottom: 2px solid #1769c2; padding-bottom: 8px; margin-bottom: 10px; }
        .head td { vertical-align: middle; }
        .logo { height: 38px; }
        .title { margin: 0; color: #14315e; font-size: 17px; font-weight: bold; }
        .sub { margin: 2px 0 0; color: #50607a; font-size: 10px; }
        .date-box { text-align: right; }
        .date { display: inline-block; padding: 6px 12px; border-radius: 6px; background: #1769c2; color: #fff; font-size: 12px; font-weight: bold; }
        .meta { margin-top: 4px; color: #50607a; font-size: 9px; }
        .summary { width: 100%; margin-bottom: 10px; border-collapse: separate; border-spacing: 6px 0; }
        .summary td { padding: 6px 8px; border: 1px solid #d5def0; border-radius: 6px; background: #f5f8ff; text-align: center; }
        .summary strong { display: block; color: #14315e; font-size: 13px; }
        .summary span { color: #50607a; font-size: 9px; }
        h2 { margin: 12px 0 5px; color: #14315e; font-size: 12px; }
        table.list { width: 100%; border-collapse: collapse; }
        table.list th { padding: 5px 5px; border: 1px solid #b9c7de; background: #e8f0fb; color: #14315e; font-size: 8.5px; text-align: left; text-transform: uppercase; }
        table.list td { padding: 5px 5px; border: 1px solid #ccd6e6; vertical-align: top; }
        table.list tr:nth-child(even) td { background: #f8fafd; }
        .no { width: 18px; text-align: center; color: #50607a; }
        .ref { font-weight: bold; white-space: nowrap; }
        .name { font-size: 10.5px; font-weight: bold; }
        .muted { color: #50607a; font-size: 9px; }
        .when { white-space: nowrap; }
        .tag { display: inline-block; padding: 2px 6px; border-radius: 8px; font-size: 8.5px; font-weight: bold; white-space: nowrap; margin-bottom: 2px; }
        .tag-special { background: #f3e8ff; color: #7e22ce; }
        .empty { padding: 24px; border: 1px dashed #b9c7de; color: #50607a; font-size: 12px; text-align: center; }
        .footer { position: fixed; bottom: -26px; left: 0; right: 0; color: #7a879b; font-size: 8.5px; }
        .footer .page:after { content: counter(page) " / " counter(pages); }
    </style>
</head>
<body>
    <div class="footer">
        <table width="100%"><tr>
            <td>Nebula Institute of Technology · Weekly approved reservations · {{ $label }}</td>
            <td style="text-align:right">Page <span class="page"></span></td>
        </tr></table>
    </div>

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
</body>
</html>
