<section class="rs-dash-panel">
    <div class="rs-dash-panel__head">
        <h2>{{ $list['title'] }}</h2>
    </div>
    @forelse($list['rows'] ?? [] as $row)
        @php $tag = !empty($row['href']) ? 'a' : 'div'; @endphp
        <{{ $tag }} class="rs-row" @if(!empty($row['href'])) href="{{ $row['href'] }}" @endif>
            <div class="rs-row__body">
                <strong>{{ $row['title'] }}</strong>
                <span>{{ $row['meta'] }}</span>
            </div>
            <span class="rs-pill tone-{{ $row['tone'] }}">{{ $row['status'] }}</span>
        </{{ $tag }}>
    @empty
        <p class="rs-dash-empty mb-0">{{ $list['empty'] }}</p>
    @endforelse
</section>
