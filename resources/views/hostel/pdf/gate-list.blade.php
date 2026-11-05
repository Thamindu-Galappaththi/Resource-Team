<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Hostel guest list — {{ $day->format('d M Y') }}</title>
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
        .date { display: inline-block; padding: 6px 12px; border-radius: 6px; background: #1769c2; color: #fff; font-size: 13px; font-weight: bold; }
        .meta { margin-top: 4px; color: #50607a; font-size: 9px; }
        .summary { width: 100%; margin-bottom: 10px; border-collapse: separate; border-spacing: 6px 0; }
        .summary td { padding: 6px 8px; border: 1px solid #d5def0; border-radius: 6px; background: #f5f8ff; text-align: center; }
        .summary strong { display: block; color: #14315e; font-size: 15px; }
        .summary span { color: #50607a; font-size: 9px; }
        h2 { margin: 12px 0 5px; color: #14315e; font-size: 12px; }
        table.list { width: 100%; border-collapse: collapse; }
        table.list th { padding: 5px 5px; border: 1px solid #b9c7de; background: #e8f0fb; color: #14315e; font-size: 9px; text-align: left; text-transform: uppercase; }
        table.list td { padding: 5px 5px; border: 1px solid #ccd6e6; vertical-align: top; }
        table.list tr:nth-child(even) td { background: #f8fafd; }
        .no { width: 20px; text-align: center; color: #50607a; }
        .ref { font-weight: bold; white-space: nowrap; }
        .guest { font-size: 11px; font-weight: bold; }
        .muted { color: #50607a; font-size: 9px; }
        .when { white-space: nowrap; }
        .tag { display: inline-block; padding: 2px 6px; border-radius: 8px; font-size: 9px; font-weight: bold; white-space: nowrap; }
        .tag-arriving { background: #e7f6f0; color: #0f7a58; }
        .tag-leaving { background: #fff4e5; color: #b45309; }
        .tag-stay { background: #e8f0fb; color: #1769c2; }
        .tag-both { background: #f3e8ff; color: #7e22ce; }
        .check { width: 42px; }
        .empty { padding: 24px; border: 1px dashed #b9c7de; color: #50607a; font-size: 12px; text-align: center; }
        .note { margin-top: 12px; padding: 7px 10px; border-left: 3px solid #1769c2; background: #f5f8ff; color: #26364d; font-size: 9.5px; }
        .footer { position: fixed; bottom: -26px; left: 0; right: 0; color: #7a879b; font-size: 8.5px; }
        .footer .page:after { content: counter(page) " / " counter(pages); }
    </style>
</head>
<body>
    <div class="footer">
        <table width="100%"><tr>
            <td>Nebula Institute of Technology · Hostel guest list · {{ $day->format('d M Y') }} · Help: 011 2954 069</td>
            <td style="text-align:right">Page <span class="page"></span></td>
        </tr></table>
    </div>

    <table class="head">
        <tr>
            <td style="width:60%">
                <table><tr>
                    @if($logo)
                        <td style="padding-right:10px"><img src="{{ $logo }}" class="logo" alt="Nebula"></td>
                    @endif
                    <td>
                        <p class="title">Hostel guest list for gate security</p>
                        <p class="sub">Allow entry only to guests listed below. Check the guest name and employee ID before entry.</p>
                    </td>
                </tr></table>
            </td>
            <td class="date-box">
                <span class="date">{{ $day->format('l, d M Y') }}</span>
                <div class="meta">Generated {{ $generatedAt->format('d M Y, H:i') }} by {{ $generatedBy }}</div>
            </td>
        </tr>
    </table>

    @php
        $count = fn (string $label) => $rows->where('today', $label)->count();
    @endphp
    <table class="summary">
        <tr>
            <td><strong>{{ $rows->count() }}</strong><span>Approved stays</span></td>
            <td><strong>{{ $rows->sum('guests') }}</strong><span>Guests in total</span></td>
            <td><strong>{{ $count('Arriving') + $count('Arriving & leaving') }}</strong><span>Arriving today</span></td>
            <td><strong>{{ $count('In stay') }}</strong><span>Already staying</span></td>
            <td><strong>{{ $count('Leaving') + $count('Arriving & leaving') }}</strong><span>Leaving today</span></td>
        </tr>
    </table>

    @forelse($groups as $location => $locationRows)
        <h2>{{ $location }} hostel · {{ $locationRows->sum('guests') }} {{ $locationRows->sum('guests') === 1 ? 'guest' : 'guests' }}</h2>
        <table class="list">
            <thead>
                <tr>
                    <th class="no">#</th>
                    <th>Reservation</th>
                    <th>Guest name</th>
                    <th>Employee ID(s)</th>
                    <th>Phone</th>
                    <th>Guests</th>
                    <th>Beds</th>
                    <th>Bed group</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Today</th>
                    <th class="check">Checked</th>
                </tr>
            </thead>
            <tbody>
                @foreach($locationRows as $index => $row)
                    @php
                        $tag = match ($row['today']) {
                            'Arriving' => 'tag-arriving',
                            'Leaving' => 'tag-leaving',
                            'Arriving & leaving' => 'tag-both',
                            default => 'tag-stay',
                        };
                    @endphp
                    <tr>
                        <td class="no">{{ $loop->iteration }}</td>
                        <td class="ref">{{ $row['reference'] }}</td>
                        <td>
                            <div class="guest">{{ $row['guest'] }}</div>
                            @if($row['requester'])<div class="muted">Booked by {{ $row['requester'] }}</div>@endif
                            @if($row['requirements'])<div class="muted">Note: {{ $row['requirements'] }}</div>@endif
                        </td>
                        <td>{{ $row['employee_ids'] ?: '—' }}</td>
                        <td class="when">{{ $row['phone'] ?: '—' }}</td>
                        <td style="text-align:center">{{ $row['guests'] }}</td>
                        <td style="text-align:center">{{ $row['beds'] ?: $row['guests'] }}</td>
                        <td>{{ $row['bed_group'] }}</td>
                        <td class="when">{{ $row['check_in']->format('d M Y') }}<br><strong>{{ $row['check_in']->format('H:i') }}</strong></td>
                        <td class="when">{{ $row['check_out']->format('d M Y') }}<br><strong>{{ $row['check_out']->format('H:i') }}</strong></td>
                        <td><span class="tag {{ $tag }}">{{ $row['today'] }}</span></td>
                        <td class="check"></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @empty
        <div class="empty">No approved hostel stays for {{ $day->format('l, d M Y') }}.</div>
    @endforelse

    <div class="note">
        Only approved reservations are listed. If a guest is not on this list, do not allow entry and call the hostel office on 011 2954 069.
    </div>
</body>
</html>
