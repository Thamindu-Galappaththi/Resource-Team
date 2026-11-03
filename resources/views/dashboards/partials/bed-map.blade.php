<section class="rd-panel hbm-panel" aria-labelledby="hbm-title">
    <div class="rd-panel__head hbm-head">
        <div>
            <h2 id="hbm-title">Bed availability</h2>
            <p class="hbm-sub mb-0">Each square is one bed, like seats on a bus.</p>
        </div>
        <div class="hbm-tools">
            <ul class="hbm-legend" role="list">
                <li><span class="hbm-dot is-free" aria-hidden="true"></span>Available</li>
                <li><span class="hbm-dot is-pending" aria-hidden="true"></span>Pending approval</li>
                <li><span class="hbm-dot is-booked" aria-hidden="true"></span>Booked</li>
            </ul>
            <label class="hbm-date">
                <span class="visually-hidden">Show beds for date</span>
                <i class="ti ti-calendar" aria-hidden="true"></i>
                <input type="date" id="hbm-date" value="{{ $bedMap['date'] }}" data-url="{{ $bedMap['url'] }}">
            </label>
        </div>
    </div>
    <div class="rd-panel__body position-relative">
        <div class="hbm-loading" id="hbm-loading" hidden>Updating…</div>
        @include('hostel.partials.bed-map', ['date' => $bedMap['date'], 'locations' => $bedMap['locations']])
    </div>
</section>
