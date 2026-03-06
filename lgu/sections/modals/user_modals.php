<!-- Change Role Modal -->
<div id="modal-change-role" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h3>Change User Role</h3>
            <button class="modal-close" data-modal="modal-change-role">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="cr-user-id" />
            <div class="form-group">
                <label>Name</label>
                <input type="text" id="cr-name" class="form-input" readonly />
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="text" id="cr-email" class="form-input" readonly />
            </div>
            <div class="form-group">
                <label>User Role</label>
                <select id="cr-role" class="form-select">
                    <option value="LGU">LGU</option>
                    <option value="Rescuer">Rescuer</option>
                    <option value="Resident">Resident</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel modal-close" data-modal="modal-change-role">Cancel</button>
            <button class="btn-save" id="confirm-change-role">Save Changes</button>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div id="modal-delete" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h3>Delete User</h3>
            <button class="modal-close" data-modal="modal-delete">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="del-user-id" />
            <p style="font-size:0.88rem; color:var(--text-dark);">
                Are you sure you want to delete <strong id="del-user-name"></strong>?
                This action cannot be undone.
            </p>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel modal-close" data-modal="modal-delete">Cancel</button>
            <button class="btn-delete-confirm" id="confirm-delete">Delete</button>
        </div>
    </div>
</div>