<!-- Edit Announcement Modal -->
<div id="modal-edit-announcement" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h3>Edit Announcement</h3>
            <button class="modal-close" data-modal="modal-edit-announcement">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="edit-announcement-id" />
            <div class="form-group">
                <label>Title</label>
                <input type="text" id="edit-announce-title" class="form-input" />
            </div>
            <div class="form-group">
                <label>Message</label>
                <textarea id="edit-announce-message" class="form-textarea"></textarea>
            </div>
            <div class="form-group">
                <label>Target Area</label>
                <select id="edit-announce-area" class="form-select">
                    <option value="" disabled>Select Target Area</option>
                    <option>All Barangays</option>
                    <option>Antipona</option>
                    <option>Bagumbayan</option>
                    <option>Bambang</option>
                    <option>Batia</option>
                    <option>Biñang 1st</option>
                    <option>Biñang 2nd</option>
                    <option>Bolacan</option>
                    <option>Bundukan</option>
                    <option>Bunlo</option>
                    <option>Caingin</option>
                    <option>Duhat</option>
                    <option>Igulot</option>
                    <option>Lolomboy</option>
                    <option>Poblacion</option>
                    <option>Sulucan</option>
                    <option>Taal</option>
                    <option>Tambobong</option>
                    <option>Turo</option>
                    <option>Wakas</option>
                </select>
            </div>
            <div class="form-group">
                <label>Expiry Date</label>
                <input type="date" id="edit-announce-expiry" class="form-input" />
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel modal-close" data-modal="modal-edit-announcement">Cancel</button>
            <button class="btn-save" id="confirm-edit-announcement">
                <span class="material-symbols-outlined">save</span>
                Save Changes
            </button>
        </div>
    </div>
</div>

<!-- Delete Announcement Modal -->
<div id="modal-delete-announcement" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h3>Delete Announcement</h3>
            <button class="modal-close" data-modal="modal-delete-announcement">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="del-announcement-id" />
            <div
                style="display:flex; flex-direction:column; align-items:center; gap:12px; padding:8px 0; text-align:center;">
                <span class="material-symbols-outlined" style="font-size:48px; color:#ef4444;">delete</span>
                <p style="font-size:0.95rem; color:var(--text-dark);">
                    Are you sure you want to delete <strong id="del-announcement-title"></strong>?
                </p>
                <p style="font-size:0.82rem; color:var(--text-muted);">This action cannot be undone.</p>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel modal-close" data-modal="modal-delete-announcement">Cancel</button>
            <button class="btn-delete-confirm" id="confirm-delete-announcement">
                <span class="material-symbols-outlined">delete</span>
                Delete
            </button>
        </div>
    </div>
</div>