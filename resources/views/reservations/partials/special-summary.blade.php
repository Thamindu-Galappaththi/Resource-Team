@if($reservation->special_approval)
    <div class="{{ $prefix }}-detail-row">
        <span class="{{ $prefix }}-detail-label">Special approval</span>
        <span class="{{ $prefix }}-detail-value"><span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><i class="ti ti-shield-lock me-1"></i>Sent for special approval</span></span>
    </div>
    <div class="{{ $prefix }}-detail-row">
        <span class="{{ $prefix }}-detail-label">Supporting documents</span>
        <span class="{{ $prefix }}-detail-value">
            @forelse($reservation->documents as $document)
                <a href="{{ $document->downloadUrl() }}" target="_blank" rel="noopener" class="d-block text-decoration-none"><i class="ti ti-file-text me-1"></i>{{ $document->original_name }} <span class="text-muted small">({{ $document->sizeLabel() }})</span></a>
            @empty
                —
            @endforelse
        </span>
    </div>
@endif
