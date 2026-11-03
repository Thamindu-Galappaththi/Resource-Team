@php
    $prefixes = ['permanent' => 'P', 'female' => 'L', 'male' => 'M'];
    $stateLabels = ['booked' => 'Booked', 'pending' => 'Pending approval', 'free' => 'Available'];
@endphp
<div class="hbm-body" id="hbm-body" data-date="{{ $date }}">
    @forelse($locations as $location)
        @php
            $available = collect($location['groups'])->sum('available');
        @endphp
        <article class="hbm-location">
            <header class="hbm-location-head">
                <h3><i class="ti ti-map-pin" aria-hidden="true"></i>{{ $location['location'] }}</h3>
                <span class="hbm-location-free"><strong>{{ $available }}</strong> of {{ $location['total'] }} beds free</span>
            </header>
            <div class="hbm-groups">
                @foreach($location['groups'] as $group)
                    @php
                        $prefix = $prefixes[$group['key']] ?? strtoupper(substr($group['key'], 0, 1));
                        $seats = [];
                        foreach ($group['bookings'] ?? [] as $booking) {
                            for ($b = 0; $b < $booking['beds'] && count($seats) < $group['capacity']; $b++) {
                                $seats[] = ['state' => $booking['state'], 'booking' => $booking];
                            }
                        }
                        while (count($seats) < $group['capacity']) {
                            $seats[] = ['state' => 'free', 'booking' => null];
                        }
                    @endphp
                    <section class="hbm-group" aria-label="{{ $location['location'] }} {{ $group['label'] }} beds">
                        <div class="hbm-group-head">
                            <span class="hbm-group-name">{{ $group['label'] }}</span>
                            <span class="hbm-group-count">
                                <span class="hbm-count is-booked">{{ $group['occupied'] }} booked</span>
                                <span class="hbm-count is-pending">{{ $group['pending'] }} pending</span>
                                <span class="hbm-count is-free">{{ $group['available'] }} free</span>
                            </span>
                        </div>
                        @if($group['capacity'] === 0)
                            <p class="hbm-none">No beds set up.</p>
                        @else
                            <ol class="hbm-seats" role="list">
                                @foreach($seats as $index => $seat)
                                    @php
                                        $code = $prefix.($index + 1);
                                        $booking = $seat['booking'];
                                        $title = $code.' · '.$stateLabels[$seat['state']]
                                            .($booking ? ' · '.$booking['reference'].($booking['guest'] ? ' · '.$booking['guest'] : '').($booking['stay'] ? ' · '.$booking['stay'] : '') : '');
                                    @endphp
                                    <li class="hbm-seat is-{{ $seat['state'] }}" title="{{ $title }}">
                                        @if($booking && $booking['url'])
                                            <a href="{{ $booking['url'] }}" class="hbm-seat-link" aria-label="Bed {{ $title }}">
                                                <i class="ti ti-bed" aria-hidden="true"></i>
                                                <span>{{ $code }}</span>
                                            </a>
                                        @else
                                            <span class="hbm-seat-link" aria-label="Bed {{ $title }}">
                                                <i class="ti ti-bed" aria-hidden="true"></i>
                                                <span>{{ $code }}</span>
                                            </span>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </section>
                @endforeach
            </div>
        </article>
    @empty
        <div class="hbm-empty">
            <i class="ti ti-bed" aria-hidden="true"></i>
            <p class="mb-0">No hostel beds are set up yet. Add a Hostel Room / Bed resource for each location.</p>
        </div>
    @endforelse
</div>
