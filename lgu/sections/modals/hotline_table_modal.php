<!-- Edit Hotline Modal -->
<div id="modal-edit-hotline" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h3>Edit Hotline</h3>
            <button class="modal-close" data-modal="modal-edit-hotline">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="edit-hotline-id" />
            <div class="form-group">
                <label>Barangay</label>
                <input type="text" id="edit-hotline-barangay" class="form-input" readonly />
            </div>
            <div class="form-group">
                <label>Hotline Name</label>
                <input type="text" id="edit-hotline-name" class="form-input" />
            </div>
            <div class="form-group">
                <label>Contact Number</label>
                <input type="text" id="edit-hotline-number" class="form-input" />
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel modal-close" data-modal="modal-edit-hotline">Cancel</button>
            <button class="btn-save" id="confirm-edit-hotline">
                <span class="material-symbols-outlined">save</span>
                Save Changes
            </button>
        </div>
    </div>
</div>

<!-- Delete Hotline Modal -->
<div id="modal-delete-hotline" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h3>Delete Hotline</h3>
            <button class="modal-close" data-modal="modal-delete-hotline">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="del-hotline-id" />
            <div
                style="display:flex; flex-direction:column; align-items:center; gap:12px; padding:8px 0; text-align:center;">
                <span class="material-symbols-outlined" style="font-size:48px; color:#ef4444;">delete</span>
                <p style="font-size:0.95rem; color:var(--text-dark);">
                    Are you sure you want to delete <strong id="del-hotline-name"></strong>?
                </p>
                <p style="font-size:0.82rem; color:var(--text-muted);">This action cannot be undone.</p>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel modal-close" data-modal="modal-delete-hotline">Cancel</button>
            <button class="btn-delete-confirm" id="confirm-delete-hotline">
                <span class="material-symbols-outlined">delete</span>
                Delete
            </button>
        </div>
    </div>
</div>