<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $reservation->reference }}</title>
    <link rel="stylesheet" href="{{ asset('css/resource-payments.css') }}?v=9">
    <style>
        html, body { height: auto; overflow: visible; }
        body { min-height: 100vh; margin: 0; padding: 32px 16px; background: #f3f6fb; }
        .rp-print-sheet { position: relative; max-width: 760px; margin: 0 auto; overflow: hidden; border: 1px solid #d5def0; border-radius: 18px; background: #fff; box-shadow: 0 12px 32px rgba(28,55,110,.1); }
        .rp-print-watermark { position: absolute; top: 44%; left: 50%; z-index: 0; transform: translate(-50%,-50%) rotate(-24deg); color: rgba(23,105,194,.08); font-size: clamp(64px,14vw,128px); font-weight: 900; letter-spacing: .08em; pointer-events: none; }
        .rp-print-brand { padding: 26px 30px; color: #fff; background: linear-gradient(120deg,#123a7a,#1769c2 52%,#5b4fc9); }
        .rp-print-brand small { color: rgba(255,255,255,.75); font-size: 11px; font-weight: 700; letter-spacing: .1em; }
        .rp-print-brand h1 { margin: 7px 0 0; font-size: 27px; }
        .rp-print-content { padding: 26px 30px; }
        .rp-print-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; padding-bottom: 20px; border-bottom: 1px solid #e8edf3; }
        .rp-print-meta div { display: grid; gap: 5px; }
        .rp-print-meta span { color: #8090a2; font-size: 11px; }
        .rp-print-meta strong { color: #24364a; font-size: 14px; }
        .rp-print-lines { margin: 12px 0; }
        .rp-print-line { display: flex; justify-content: space-between; gap: 16px; padding: 14px 0; border-bottom: 1px solid #edf0f4; color: #536477; }
        .rp-print-line strong { color: #24364a; white-space: nowrap; }
        .rp-print-total { display: flex; justify-content: space-between; align-items: center; padding: 20px 0; color: #1769c2; font-weight: 800; }
        .rp-print-total strong { color: #132033; font-size: 27px; }
        .rp-print-pending { padding: 12px 14px; border: 1px solid #dce8f8; border-radius: 10px; background: #f1f6fd; color: #496686; font-size: 12px; }
        .rp-print-actions { display: flex; justify-content: flex-end; gap: 10px; max-width: 760px; margin: 0 auto 14px; }
        .rp-print-actions button { padding: 10px 16px; border: 1px solid #1769c2; border-radius: 9px; background: #1769c2; color: white; font-weight: 700; cursor: pointer; }
        .rp-print-actions a { padding: 10px 16px; border: 1px solid #cad7e6; border-radius: 9px; background: white; color: #1769c2; font-weight: 700; text-decoration: none; }
        @media print {
            @page { size: auto; margin: 12mm; }
            html, body { height: auto !important; overflow: visible !important; padding: 0; background: #fff; }
            .rp-print-actions { display: none !important; }
            .rp-print-sheet { overflow: visible; border: 0; border-radius: 0; box-shadow: none; }
            .rp-print-brand { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
        @media(max-width:540px) {
            body { padding: 16px 10px; }
            .rp-print-content,.rp-print-brand { padding: 20px; }
            .rp-print-actions { flex-wrap: wrap; }
            .rp-print-actions a, .rp-print-actions button { flex: 1 1 140px; text-align: center; }
            .rp-print-meta { grid-template-columns: minmax(0, 1fr); }
            .rp-print-line { flex-wrap: wrap; }
            .rp-print-total { flex-wrap: wrap; gap: 8px; }
            .rp-print-total strong { font-size: 22px; }
        }
    </style>
</head>
<body>
    <div class="rp-print-actions"><a href="{{ route('payments.resources') }}">Back to payments</a><button type="button" onclick="window.print()">Print invoice</button></div>
    <main class="rp-print-sheet">
        <header class="rp-print-brand"><small>RESOURCE RESERVATION SYSTEM</small><h1>Invoice Preview</h1></header>
        <section class="rp-print-content">
            <div class="rp-print-watermark" aria-hidden="true">DRAFT</div>
            <div class="rp-print-meta">
                <div><span>Reservation reference</span><strong>{{ $reservation->reference }}</strong></div>
                <div><span>Reservation date</span><strong>{{ $reservation->reservation_date?->format('d M Y') }}</strong></div>
                <div><span>Invoice number</span><strong>DRAFT-{{ $reservation->reference }}</strong></div>
                <div><span>Issue date</span><strong>{{ $issueDate->format('d M Y') }}</strong></div>
                <div><span>Resource</span><strong>{{ $reservation->resourceNames() ?: 'Resource reservation' }}</strong></div>
                <div><span>Bill to</span><strong>{{ $reservation->requester?->name ?? $reservation->createdBy?->name ?? 'Reservation user' }}</strong></div>
                <div><span>Email</span><strong>{{ $reservation->requester?->email ?? $reservation->createdBy?->email ?? 'Not available' }}</strong></div>
            </div>
            <div class="rp-print-lines">
                @foreach($breakdown['lines'] as $line)
                    <div class="rp-print-line"><span>{{ $line['resource'] }} · base fee includes first {{ number_format($line['base_duration_hours'], 2) }} hrs</span><strong>LKR {{ number_format($line['base_fee'], 2) }}</strong></div>
                    <div class="rp-print-line"><span>{{ $line['resource'] }} · {{ number_format($line['extra_hours'], 2) }} extra hrs × LKR {{ number_format($line['extra_hourly_rate'], 2) }}/hr</span><strong>LKR {{ number_format($line['extra_hour_fee'], 2) }}</strong></div>
                @endforeach
                <div class="rp-print-line"><span>Discount ({{ number_format($breakdown['discount_percent'], 2) }}%)</span><strong>− LKR {{ number_format($breakdown['discount'], 2) }}</strong></div>
                <div class="rp-print-line"><span>Tax ({{ $breakdown['tax_percent'] === null ? 'per resource rate' : number_format($breakdown['tax_percent'], 3).'%' }})</span><strong>LKR {{ number_format($breakdown['tax_fee'], 2) }}</strong></div>
            </div>
            <div class="rp-print-total"><span>TOTAL AMOUNT DUE</span><strong>LKR {{ number_format($breakdown['total'], 2) }}</strong></div>
            @unless($breakdown['has_rate'])<div class="rp-print-pending">Rate details are not available for this resource and reservation date. Finance must configure an applicable rate before collecting payment.</div>@endunless
            <div class="rp-print-pending">Online payment status: Pending. This draft is not a tax invoice until issued by Finance.</div>
        </section>
    </main>
</body>
</html>
