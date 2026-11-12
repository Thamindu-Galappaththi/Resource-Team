<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Invoice {{ $invoice->invoice_number ?: $reservation->reference }}</title>
    <link rel="stylesheet" href="{{ asset('css/icons/tabler-icons/tabler-icons.css') }}">
    <link rel="stylesheet" href="{{ asset('css/resource-invoice-print.css') }}?v=4">
    <link rel="stylesheet" href="{{ asset('css/resource-payment-record.css') }}?v=5">
</head>
@php
    $requester = $reservation->requester ?? $reservation->createdBy;
    $isPaid = $invoice->status === 'paid';
    $isDraft = ! $invoice->invoice_number;
    $startTime = substr((string) $reservation->start_time, 0, 5);
    $endTime = substr((string) $reservation->end_time, 0, 5);
    $start = \Illuminate\Support\Carbon::parse(($reservation->reservation_date?->toDateString() ?? now()->toDateString()).' '.$startTime);
    $end = \Illuminate\Support\Carbon::parse(($reservation->reservation_date?->toDateString() ?? now()->toDateString()).' '.$endTime);
    if ($end->lessThanOrEqualTo($start)) $end->addDay();
    $duration = $start->diffInMinutes($end) / 60;
    $bookingType = match ($reservation->booking_type) {
        'full_day' => 'Full day',
        'half_day_morning' => 'Half day morning',
        'half_day_afternoon' => 'Half day afternoon',
        default => ($duration >= 7 ? 'Full day' : ($start->hour < 12 ? 'Half day morning' : 'Half day afternoon')),
    };
    $statusLabel = $isPaid ? 'Paid' : ($isDraft ? 'Draft' : 'Unpaid');
    $money = fn ($amount) => 'LKR '.number_format((float) $amount, 2);
@endphp
<body>
    <div class="invoice-page">
        <nav class="invoice-actions" aria-label="Invoice actions">
            <a class="invoice-button is-secondary" href="{{ route('payments.resources') }}"><i class="ti ti-arrow-left" aria-hidden="true"></i> Back to payments</a>
            @if(! $isPaid && $canMarkPaid)
                <button class="invoice-button is-success" type="button" id="mark-invoice-paid" data-url="{{ route('payments.resources.mark-paid', ['reference' => $reservation->reference]) }}" data-extra-hours="{{ number_format((float) $invoice->extra_hours, 2, '.', '') }}" data-invoice-number="{{ $invoice->invoice_number ?: 'Draft invoice' }}" data-total="{{ number_format((float) ($breakdown['total'] ?? 0), 2, '.', '') }}"><i class="ti ti-cash" aria-hidden="true"></i> Mark as Paid</button>
            @endif
            <button class="invoice-button is-primary" type="button" onclick="window.print()"><i class="ti ti-printer" aria-hidden="true"></i> Print invoice</button>
        </nav>

        <main class="invoice-card">
            <div class="invoice-watermark" aria-hidden="true">NEBULA</div>
            <header class="invoice-header">
                <div class="invoice-brand">
                    <img class="invoice-logo" src="{{ asset('images/logos/nebula.png') }}" alt="SLT Mobitel Nebula Institute of Technology" onerror="this.hidden=true; this.nextElementSibling.hidden=false">
                    <span class="invoice-logo-fallback" hidden>SLT Mobitel Nebula Institute of Technology</span>
                </div>
                <div class="invoice-header-meta">
                    <span class="invoice-title">INVOICE</span>
                    <strong class="invoice-number">{{ $invoice->invoice_number ?: 'Draft invoice' }}</strong>
                    <span @class(['invoice-status', 'is-paid' => $isPaid, 'is-draft' => $isDraft, 'is-unpaid' => ! $isPaid && ! $isDraft])>
                        @if($isPaid)<i class="ti ti-circle-check"></i>@elseif($isDraft)<i class="ti ti-file-draft"></i>@else<i class="ti ti-clock"></i>@endif
                        {{ $statusLabel }}
                        @if($isPaid && ($invoice->payment_date || $invoice->paid_at))<small>{{ ($invoice->payment_date ?? $invoice->paid_at)->format('d M Y') }}</small>@endif
                    </span>
                </div>
            </header>

            <div class="invoice-content">
                <section class="invoice-info-grid" aria-label="Invoice details">
                    <article class="invoice-info-card">
                        <span class="invoice-section-label">Bill to</span>
                        <h2>{{ $requester?->name ?? 'Reservation user' }}</h2>
                        <p>{{ $requester?->email ?? 'Email not available' }}</p>
                    </article>
                    <article class="invoice-info-card reservation-info">
                        <span class="invoice-section-label">Reservation</span>
                        <dl>
                            <div><dt>Reference</dt><dd>{{ $reservation->reference }}</dd></div>
                            <div><dt>Resource</dt><dd>{{ $reservation->resourceNames() ?: 'Resource reservation' }}</dd></div>
                            <div><dt>Location</dt><dd>{{ $reservation->location?->name ?? 'Location not available' }}</dd></div>
                            <div><dt>Reservation date</dt><dd>{{ $reservation->reservation_date?->format('d M Y') ?? '—' }}</dd></div>
                            <div><dt>Booking type</dt><dd>{{ $bookingType }}</dd></div>
                            <div><dt>Issue date</dt><dd>{{ $issueDate?->format('d M Y') ?? '—' }}</dd></div>
                        </dl>
                    </article>
                </section>

                <section class="invoice-items" aria-label="Invoice line items">
                    <div class="invoice-table-scroll">
                        <table class="invoice-table">
                            <thead><tr><th>Description</th><th class="numeric">Qty</th><th class="numeric">Rate</th><th class="numeric">Amount</th></tr></thead>
                            <tbody>
                            @forelse($breakdown['lines'] as $line)
                                @php
                                    $quantity = (float) ($line['quantity'] ?? 1);
                                    $baseUnit = (float) ($line['base_unit_fee'] ?? ((float) $line['base_fee'] / max(1, $quantity)));
                                    $hourlyUnit = (float) ($line['extra_hourly_unit_rate'] ?? ((float) $line['extra_hourly_rate'] / max(1, $quantity)));
                                    $extraQty = (float) $line['extra_hours'];
                                @endphp
                                <tr>
                                    <td><strong>{{ $line['resource'] }} — {{ $line['booking_type'] ?? 'Reservation' }} rate</strong><small>{{ $line['booking_time'] ?? ($startTime.'–'.$endTime) }} · {{ number_format((float) $line['base_duration_hours'], 0) }} hours</small></td>
                                    <td class="numeric">{{ number_format($quantity, 2) }}{{ $quantity === 1.0 ? '' : ' units' }}</td>
                                    <td class="numeric">{{ $baseUnit == 0 ? 'Free' : $money($baseUnit) }}</td>
                                    <td class="numeric amount">{{ (float) $line['base_fee'] == 0 ? 'Free' : $money($line['base_fee']) }}</td>
                                </tr>
                                @if($extraQty > 0)
                                    <tr>
                                        <td><strong>{{ $line['resource'] }} — Extra hours</strong><small>{{ number_format($extraQty, 1) }} billable hours per resource</small></td>
                                        <td class="numeric">{{ number_format($extraQty, 1) }} hrs × {{ number_format($quantity, 2) }}</td>
                                        <td class="numeric">{{ $hourlyUnit == 0 ? 'Free' : $money($hourlyUnit) }} / hr</td>
                                        <td class="numeric amount">{{ (float) $line['extra_hour_fee'] == 0 ? 'Free' : $money($line['extra_hour_fee']) }}</td>
                                    </tr>
                                @endif
                            @empty
                            <tr><td><strong>Resource reservation</strong><small>Rate details are not available for this reservation.</small></td><td class="numeric">—</td><td class="numeric">—</td><td class="numeric amount">{{ $money(0) }}</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="invoice-totals" aria-label="Invoice totals">
                    <div class="total-row"><span>Subtotal</span><strong>{{ $money($breakdown['subtotal'] ?? 0) }}</strong></div>
                    <div class="total-row"><span>Discount <small>({{ rtrim(rtrim(number_format((float) ($breakdown['discount_percent'] ?? 0), 2), '0'), '.') }}%)</small></span><strong class="discount-value">− {{ $money($breakdown['discount'] ?? 0) }}</strong></div>
                    <div class="total-row"><span>Tax <small>({{ $breakdown['tax_percent'] === null ? 'per resource rate' : rtrim(rtrim(number_format((float) $breakdown['tax_percent'], 3), '0'), '.').'%' }})</small></span><strong>{{ $money($breakdown['tax_fee'] ?? 0) }}</strong></div>
                    <div class="total-due"><span>Total amount due</span><strong>{{ $money($breakdown['total'] ?? 0) }}</strong></div>
                </section>

                @if($isPaid)
                    <section class="invoice-paid-stamp" aria-label="Payment recorded">
                        <span class="paid-stamp-icon"><i class="ti ti-circle-check"></i></span>
                        <div><strong>PAID</strong><p>Paid {{ ($invoice->payment_date ?? $invoice->paid_at)?->format('d M Y') ?? '' }} · {{ str_replace('_', ' ', ucfirst($invoice->payment_method ?? 'Payment')) }}@if($invoice->payment_reference) · Ref {{ $invoice->payment_reference }}@endif</p></div>
                    </section>
                @elseif($isDraft)
                    <aside class="invoice-draft-note"><i class="ti ti-info-circle"></i><span>Draft only. Not a tax invoice until issued by Finance.</span></aside>
                @endif
                @unless($breakdown['has_rate'])
                    <aside class="invoice-rate-note"><i class="ti ti-alert-circle"></i><span>Rate details are not available for this resource and reservation date. Finance must configure an applicable rate before collecting payment.</span></aside>
                @endunless
            </div>
        </main>
    </div>

    <script src="{{ asset('js/resource-payment-record.js') }}?v=5"></script>
    @if(! $isPaid && $canMarkPaid)
        <script>
            document.getElementById('mark-invoice-paid')?.addEventListener('click', async function () {
                const button = this;
                button.disabled = true;
                try {
                    const result = await window.ResourcePaymentRecord.open({ invoiceNumber: button.dataset.invoiceNumber, total: button.dataset.total, url: button.dataset.url, extraHours: button.dataset.extraHours });
                    if (!result) { button.disabled = false; return; }
                    const paidDate = result.payment_date || result.paid_at || '';
                    const method = String(result.payment_method || '').replaceAll('_', ' ');
                    document.querySelector('.invoice-status')?.classList.remove('is-unpaid', 'is-draft');
                    const badge = document.querySelector('.invoice-status');
                    if (badge) { badge.classList.add('is-paid'); badge.innerHTML = `<i class="ti ti-circle-check"></i> Paid <small>${paidDate}</small>`; }
                    const watermark = document.querySelector('.invoice-watermark');
                    if (watermark) watermark.classList.add('is-paid');
                    document.querySelector('.invoice-draft-note')?.remove();
                    const stamp = document.createElement('section');
                    stamp.className = 'invoice-paid-stamp';
                    stamp.innerHTML = `<span class="paid-stamp-icon"><i class="ti ti-circle-check"></i></span><div><strong>PAID</strong><p>Paid ${paidDate} · ${method}${result.payment_reference ? ` · Ref ${String(result.payment_reference).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}` : ''}</p></div>`;
                    document.querySelector('.invoice-content')?.append(stamp);
                    button.remove();
                } catch (error) {
                    button.disabled = false;
                }
            });
        </script>
    @endif
</body>
</html>
