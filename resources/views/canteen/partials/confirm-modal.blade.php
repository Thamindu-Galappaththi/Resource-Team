<div class="modal fade cp-modal" id="cp-confirm-modal" tabindex="-1" aria-labelledby="cp-confirm-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="cp-confirm-form" method="POST" class="modal-content">
            @csrf
            @method('DELETE')
            <div class="modal-header">
                <span class="cp-modal-icon" id="cp-confirm-icon" aria-hidden="true"><i class="ti ti-trash"></i></span>
                <div>
                    <h2 class="modal-title fs-5" id="cp-confirm-title">Delete reservation?</h2>
                    <p class="cp-modal-sub mb-0" id="cp-confirm-sub"></p>
                </div>
                <button type="button" class="cp-modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="cp-confirm-copy">This cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Keep reservation</button>
                <button type="submit" class="btn btn-danger" id="cp-confirm-submit">Delete reservation</button>
            </div>
        </form>
    </div>
</div>
