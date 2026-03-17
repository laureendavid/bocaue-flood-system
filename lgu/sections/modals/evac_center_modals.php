<!-- ===== MODAL: Add Evacuee ===== -->
<div id="modal-add-evacuee" class="modal-overlay" aria-modal="true" role="dialog">
    <div class="modal">
        <div class="modal-header">
            <h3>Add Evacuee</h3>
            <button class="modal-close" data-modal="modal-add-evacuee" aria-label="Close">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="add-evacuee-center-id" />
            <div class="form-group">
                <label for="evacuee-rep">Representative Name</label>
                <input type="text" id="evacuee-rep" class="form-input" placeholder="e.g. Juan dela Cruz" />
            </div>
            <div class="form-group">
                <label for="evacuee-count">Number of People</label>
                <input type="number" id="evacuee-count" class="form-input" placeholder="e.g. 5" min="1" />
            </div>
            <div class="form-group">
                <label for="evacuee-contact">Contact Number</label>
                <input type="text" id="evacuee-contact" class="form-input" placeholder="e.g. 09XX-XXX-XXXX" />
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel modal-close" data-modal="modal-add-evacuee">Cancel</button>
            <button class="btn-save" id="confirm-add-evacuee">Save Changes</button>
        </div>
    </div>
</div>

<!-- ===== MODAL: Remove Evacuee ===== -->
<div id="modal-remove-evacuee" class="modal-overlay" aria-modal="true" role="dialog">
    <div class="modal modal--wide">
        <div class="modal-header">
            <h3>Remove Evacuee</h3>
            <button class="modal-close" data-modal="modal-remove-evacuee" aria-label="Close">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="remove-evacuee-center-id" />
            <div class="toolbar" style="margin-bottom:12px;">
                <div class="search-box">
                    <span class="material-symbols-outlined">search</span>
                    <input type="search" id="evacuee-search" placeholder="Search by Name" />
                </div>
                <button class="btn-delete-confirm" id="btn-remove-all-evacuees">Remove All</button>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Representative</th>
                            <th>No. of People</th>
                            <th>Contact</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="evacuee-list-tbody">
                        <tr class="empty-row">
                            <td colspan="4">No evacuees found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel modal-close" data-modal="modal-remove-evacuee">Close</button>
        </div>
    </div>
</div>

<!-- ===== MODAL: Edit Center ===== -->
<div id="modal-edit-center" class="modal-overlay" aria-modal="true" role="dialog">
    <div class="modal">
        <div class="modal-header">
            <h3>Edit Center</h3>
            <button class="modal-close" data-modal="modal-edit-center" aria-label="Close">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="edit-center-id" />
            <div class="form-group">
                <label for="edit-center-name">Center Name</label>
                <input type="text" id="edit-center-name" class="form-input" />
            </div>
            <div class="form-group">
                <label for="edit-center-address">Address</label>
                <input type="text" id="edit-center-address" class="form-input" />
            </div>
            <div class="form-group">
                <label for="edit-center-capacity">Capacity</label>
                <input type="number" id="edit-center-capacity" class="form-input" min="1" />
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel modal-close" data-modal="modal-edit-center">Cancel</button>
            <button class="btn-save" id="confirm-edit-center">Save Changes</button>
        </div>
    </div>
</div>

<!-- ===== MODAL: Delete Center ===== -->
<div id="modal-delete-center" class="modal-overlay" aria-modal="true" role="dialog">
    <div class="modal">
        <div class="modal-header">
            <h3>Delete Center</h3>
            <button class="modal-close" data-modal="modal-delete-center" aria-label="Close">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="del-center-id" />
            <p>Are you sure you want to delete <strong id="del-center-name"></strong>? This cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel modal-close" data-modal="modal-delete-center">Cancel</button>
            <button class="btn-delete-confirm" id="confirm-delete-center">Delete</button>
        </div>
    </div>
</div>