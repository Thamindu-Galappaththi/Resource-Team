<section class="rd-panel rws-panel" aria-labelledby="rws-title">
    <div class="rd-panel__head rws-head">
        <div>
            <h2 id="rws-title">This week’s approved bookings</h2>
            <p class="rws-sub mb-0">{{ $weekSheet['label'] }} · {{ number_format($weekSheet['count']) }} approved{{ $weekSheet['specialCount'] ? ' · '.$weekSheet['specialCount'].' special' : '' }}</p>
        </div>
        <div class="rws-tools">
            <a class="rws-btn rws-btn--pdf" href="{{ $weekSheet['pdfUrl'] }}">
                <i class="ti ti-file-type-pdf" aria-hidden="true"></i>
                Download PDF
            </a>
            <a class="rws-btn rws-btn--png" href="{{ $weekSheet['pngUrl'] }}">
                <i class="ti ti-photo" aria-hidden="true"></i>
                Download image
            </a>
        </div>
    </div>
    <div class="rd-panel__body">
        <p class="rws-copy mb-0">Use the PDF or image to share this week’s halls and labs, including lecturer, attendees, and special-approval details.</p>
    </div>
</section>
