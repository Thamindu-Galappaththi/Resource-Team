@php
    $prefixes = ['permanent' => 'P', 'female' => 'L', 'male' => 'M'];
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
                    @php $prefix = $prefixes[$group['key']] ?? strtoupper(substr($group['key'], 0, 1)); @endphp
                    <section class="hbm-group" aria-label="{{ $location['location'] }} {{ $group['label'] }} beds">
                        <div class="hbm-group-head">
                            <span class="hbm-group-name">{{ $group['label'] }}</span>
                            <span class="hbm-group-count">{{ $group['available'] }} free · {{ $group['pending'] }} pending · {{ $group['occupied'] }} booked</span>
                        </div>
                        @if($group['capacity'] === 0)
                            <p class="hbm-none">No beds set up.</p>
                        @else
                            <ol class="hbm-seats" role="list">
                                @for($i = 1; $i <= $group['capacity']; $i++)
                                    @php
                                        $state = $i <= $group['occupied'] ? 'booked' : ($i <= $group['occupied'] + $group['pending'] ? 'pending' : 'free');
                                        $stateLabel = ['booked' => 'Booked', 'pending' => 'Pending approval', 'free' => 'Available'][$state];
                                    @endphp
                                    <li class="hbm-seat is-{{ $state }}" title="{{ $prefix }}{{ $i }} · {{ $stateLabel }}" aria-label="Bed {{ $prefix }}{{ $i }}: {{ $stateLabel }}">
                                        <i class="ti ti-bed" aria-hidden="true"></i>
                                        <span>{{ $prefix }}{{ $i }}</span>
                                    </li>
                                @endfor
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
