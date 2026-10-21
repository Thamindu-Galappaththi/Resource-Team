<section class="rd-panel">
    <div class="rd-panel__head">
        <h2>{{ $list['title'] }}</h2>
        @if(!empty($list['rows']))
            <span class="rd-panel__badge">{{ count($list['rows']) }}</span>
        @endif
    </div>
    <div class="rd-panel__body">
        @php
            $icons = [
                'success' => ['icon' => 'ti ti-circle-check', 'bg' => '#dcfce7', 'color' => '#15803d'],
                'warning' => ['icon' => 'ti ti-clock-hour-4',  'bg' => '#fef9c3', 'color' => '#a16207'],
                'danger'  => ['icon' => 'ti ti-circle-x',      'bg' => '#fee2e2', 'color' => '#b91c1c'],
                'info'    => ['icon' => 'ti ti-calendar-event', 'bg' => '#dbeafe', 'color' => '#1d4ed8'],
                'neutral' => ['icon' => 'ti ti-minus-circle',   'bg' => '#f1f5f9', 'color' => '#64748b'],
            ];
        @endphp

        @if(empty($list['rows']))
            <div class="rd-empty">
                <i class="ti ti-inbox"></i>
                <p>{{ $list['empty'] }}</p>
            </div>
        @else
            <div class="rd-list" role="list">
                @foreach($list['rows'] as $row)
                    @php
                        $tone = $row['tone'] ?? 'neutral';
                        $ic = $icons[$tone] ?? $icons['neutral'];
                        $tag = !empty($row['href']) ? 'a' : 'div';
                    @endphp
                    <{{ $tag }} class="rd-list-item"
                        @if(!empty($row['href'])) href="{{ $row['href'] }}" @endif
                        role="listitem">
                        <span class="rd-list-item__icon"
                              style="background:{{ $ic['bg'] }};color:{{ $ic['color'] }}"
                              aria-hidden="true">
                            <i class="{{ $ic['icon'] }}"></i>
                        </span>
                        <div class="rd-list-item__body">
                            <span class="rd-list-item__title">{{ $row['title'] }}</span>
                            <span class="rd-list-item__meta">{{ $row['meta'] }}</span>
                        </div>
                        <span class="rd-pill tone-{{ $tone }}">{{ $row['status'] }}</span>
                    </{{ $tag }}>
                @endforeach
            </div>
        @endif
    </div>
</section>
