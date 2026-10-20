@php
    $pager = $pager ?? [];
@endphp
@if(($pager['total'] ?? 0))
    <div class="rpt-pager">
        <p class="small text-muted mb-0">Showing {{ $pager['from'] }}–{{ $pager['to'] }} of {{ number_format($pager['total']) }}</p>
        @if(($pager['last'] ?? 1) > 1)
            <ul class="pagination mb-0">
                <li class="page-item {{ $pager['prev'] ? '' : 'disabled' }}">
                    @if($pager['prev'])
                        <a class="page-link" href="{{ $pager['prev'] }}" data-rpt-page rel="prev">‹</a>
                    @else
                        <span class="page-link">‹</span>
                    @endif
                </li>
                @foreach($pager['pages'] as $index => $page)
                    @if($index && $page['page'] - $pager['pages'][$index - 1]['page'] > 1)
                        <li class="page-item disabled"><span class="page-link">…</span></li>
                    @endif
                    <li class="page-item {{ $page['active'] ? 'active' : '' }}">
                        @if($page['active'])
                            <span class="page-link">{{ $page['page'] }}</span>
                        @else
                            <a class="page-link" href="{{ $page['url'] }}" data-rpt-page>{{ $page['page'] }}</a>
                        @endif
                    </li>
                @endforeach
                <li class="page-item {{ $pager['next'] ? '' : 'disabled' }}">
                    @if($pager['next'])
                        <a class="page-link" href="{{ $pager['next'] }}" data-rpt-page rel="next">›</a>
                    @else
                        <span class="page-link">›</span>
                    @endif
                </li>
            </ul>
        @endif
    </div>
@endif
