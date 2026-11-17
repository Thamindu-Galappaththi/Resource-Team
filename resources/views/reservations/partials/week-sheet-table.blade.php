@php
    $count = $rows->count();
@endphp
<table class="summary">
    <tr>
        <td><strong>{{ $count }}</strong><span>Approved this week</span></td>
        <td><strong>{{ $specialCount }}</strong><span>Special approvals</span></td>
        <td><strong>{{ $groups->count() }}</strong><span>Days with bookings</span></td>
        <td><strong>{{ $label }}</strong><span>Week (Mon–Sun)</span></td>
    </tr>
</table>

@forelse($groups as $date => $dayRows)
    <h2>{{ $date }}</h2>
    <table class="list">
        <thead>
            <tr>
                <th class="no">#</th>
                <th>ID</th>
                <th>Time</th>
                <th>Location</th>
                <th>Resource</th>
                <th>Reservation</th>
                <th>Special details</th>
                <th>Requester</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($dayRows as $index => $row)
                <tr>
                    <td class="no">{{ $index + 1 }}</td>
                    <td class="ref">{{ $row['reference'] }}</td>
                    <td class="when">{{ $row['time'] }}</td>
                    <td>{{ $row['location'] }}</td>
                    <td>{{ $row['resources'] }}</td>
                    <td>
                        <div class="name">{{ $row['name'] }}</div>
                    </td>
                    <td>
                        @if($row['special'])
                            <span class="tag tag-special">Special</span>
                        @endif
                        @if($row['details'])
                            <div class="muted">{{ $row['details'] }}</div>
                        @elseif(! $row['special'])
                            <span class="muted">—</span>
                        @endif
                    </td>
                    <td>{{ $row['requester'] }}</td>
                    <td>{{ $row['status'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@empty
    <div class="empty">No approved resource reservations for this week.</div>
@endforelse
