@php
    $specialChecked = (bool) old('special_approval', $reservation->special_approval ?? false);
    $existingDocuments = isset($reservation) ? $reservation->documents : collect();
    $documentError = $errors->first('special_documents') ?: collect($errors->getMessages())
        ->filter(fn ($messages, $key) => str_starts_with($key, 'special_documents.'))
        ->flatten()
        ->first();
@endphp
<div class="rv-special" data-special-approval>
    <div class="form-check rv-special-check">
        <input type="hidden" name="special_approval" value="0">
        <input class="form-check-input" type="checkbox" id="special_approval" name="special_approval" value="1" @checked($specialChecked)>
        <label class="form-check-label" for="special_approval">
            <span class="rv-special-label">Send for special approval</span>
            <span class="rv-special-hint">Tick this when the request needs an exception. It goes to Special approvals instead of the normal approval queue.</span>
        </label>
    </div>

    <div class="rv-special-docs" data-special-docs @if(! $specialChecked) hidden @endif>
        <label class="form-label" for="special_documents">Supporting documents <span class="text-muted">(optional)</span></label>
        <input type="file" class="form-control @if($documentError) is-invalid @endif" id="special_documents" name="special_documents[]" multiple
            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" data-max-files="5" data-max-kb="5120">
        <div class="invalid-feedback @if($documentError) d-block @endif" data-special-docs-error>{{ $documentError }}</div>
        <p class="rv-hint mb-0 mt-2">Attach proof such as an approval letter or email. Up to 5 files, 5 MB each (PDF, Word, JPG, PNG).</p>
        <ul class="rv-doc-list" data-special-docs-picked hidden></ul>

        @if($existingDocuments->isNotEmpty())
            <p class="rv-doc-heading">Already attached</p>
            <ul class="rv-doc-list">
                @foreach($existingDocuments as $document)
                    <li>
                        <i class="ti ti-file-text" aria-hidden="true"></i>
                        <a href="{{ $document->downloadUrl() }}" target="_blank" rel="noopener">{{ $document->original_name }}</a>
                        <span class="text-muted">{{ $document->sizeLabel() }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
