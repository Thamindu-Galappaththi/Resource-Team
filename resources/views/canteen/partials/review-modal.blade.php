<div class="modal fade cp-modal" id="cd-review-modal" tabindex="-1" aria-labelledby="cd-review-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form id="cd-review-form" class="modal-content">
            @csrf
            <input type="hidden" name="status" id="cd-review-status" value="">
            <div class="modal-header">
                <span class="cp-modal-icon" id="cd-review-icon" aria-hidden="true"><i class="ti ti-circle-check"></i></span>
                <div>
                    <h2 class="modal-title fs-5" id="cd-review-title">Review reservation?</h2>
                    <p class="cp-modal-sub mb-0" id="cd-review-sub"></p>
                </div>
                <button type="button" class="cp-modal-close" data-bs-dismiss="modal" aria-label="Close"><i class="ti ti-x"></i></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="cd-review-copy">Please confirm this decision.</p>
                <div id="cd-review-reason-wrap" class="cd-review-reason" hidden>
                    <label class="form-label" for="cd-review-reason">Reason for rejection <span class="text-danger">*</span></label>
                    <textarea id="cd-review-reason" name="approval_comments" class="form-control" rows="3" maxlength="1000" placeholder="Explain why this request cannot go ahead"></textarea>
                    <p class="invalid-feedback d-block" id="cd-review-reason-error"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Keep pending</button>
                <button type="submit" class="btn btn-success" id="cd-review-submit">Approve reservation</button>
            </div>
        </form>
    </div>
</div>
