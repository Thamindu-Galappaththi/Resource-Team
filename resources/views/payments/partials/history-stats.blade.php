@php
    $formatMoney = fn ($amount) => 'LKR '.number_format((float) $amount, 2);
@endphp
<article class="rc-card" style="--stat-color:#1769c2;--stat-icon-bg:#e8f0fa">
    <span class="rc-card-icon" aria-hidden="true"><i class="ti ti-file-invoice"></i></span>
    <div class="rc-card-label">Total invoices</div>
    <div class="rc-card-value">{{ number_format($summary['total_count']) }}</div>
    <small class="rph-stat-detail">{{ $formatMoney($summary['total_amount']) }} invoice value</small>
</article>
<article class="rc-card" style="--stat-color:#16a77a;--stat-icon-bg:#e7f6f0">
    <span class="rc-card-icon" aria-hidden="true"><i class="ti ti-circle-check"></i></span>
    <div class="rc-card-label">Paid</div>
    <div class="rc-card-value">{{ number_format($summary['paid_count']) }}</div>
    <small class="rph-stat-detail">{{ $formatMoney($summary['paid_amount']) }} collected</small>
</article>
<article class="rc-card" style="--stat-color:#d97706;--stat-icon-bg:#fff4e5">
    <span class="rc-card-icon" aria-hidden="true"><i class="ti ti-clock"></i></span>
    <div class="rc-card-label">Unpaid</div>
    <div class="rc-card-value">{{ number_format($summary['unpaid_count']) }}</div>
    <small class="rph-stat-detail">{{ $formatMoney($summary['unpaid_amount']) }} outstanding</small>
</article>
<article class="rc-card" style="--stat-color:#7355c7;--stat-icon-bg:#f0ebff">
    <span class="rc-card-icon" aria-hidden="true"><i class="ti ti-chart-pie"></i></span>
    <div class="rc-card-label">Collection rate</div>
    <div class="rc-card-value">{{ number_format($summary['collection_rate'], 1) }}%</div>
    <small class="rph-stat-detail">Paid value / total value</small>
</article>
