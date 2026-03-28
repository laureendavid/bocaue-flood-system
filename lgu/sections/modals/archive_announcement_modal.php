<!-- Archive Announcements Modal -->
<div id="modal-archived-announcements" class="modal-overlay">
    <div class="modal" style="max-width: 780px; width: 95%;">
        <div class="modal-header">
            <h3 style="display:flex; align-items:center; gap:8px;">
                <span class="material-symbols-outlined" style="font-size:22px;">inventory_2</span>
                Archived Announcements
            </h3>
            <button class="modal-close" data-modal="modal-archived-announcements">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <div class="modal-body" style="padding: 16px 20px;">

            <!-- Loading -->
            <div id="archive-loading" style="text-align:center; padding: 40px; color: var(--text-muted);">
                <span class="material-symbols-outlined"
                    style="font-size:36px; display:block; margin-bottom:8px;">hourglass_empty</span>
                <p style="font-size:0.9rem;">Loading archives...</p>
            </div>

            <!-- Empty -->
            <div id="archive-empty" style="display:none; text-align:center; padding: 40px; color: var(--text-muted);">
                <span class="material-symbols-outlined"
                    style="font-size:52px; display:block; margin-bottom:10px; opacity:0.4;">inventory_2</span>
                <p style="font-size:0.95rem; font-weight:500;">No archived announcements found.</p>
                <p style="font-size:0.82rem; margin-top:4px; opacity:0.7;">Expired announcements will appear here.</p>
            </div>

            <!-- Search -->
            <div id="archive-table-wrap" style="display:none;">
                <div style="margin-bottom: 12px;">
                    <div class="search-box">
                        <span class="material-symbols-outlined">search</span>
                        <input type="search" id="archive-search" placeholder="Search Archives"
                            aria-label="Search archives" />
                    </div>
                </div>

                <!-- Table -->
                <div style="border-radius: 8px; overflow: hidden; border: 1px solid var(--border-color, #e2e8f0);">
                    <div style="max-height: 380px; overflow-y: auto;">
                        <table aria-label="Archived announcements" style="width:100%; border-collapse:collapse;">
                            <thead>
                                <tr style="background: var(--header-bg); position: sticky; top: 0; z-index: 1;">
                                    <th
                                        style="padding:12px 16px; text-align:left; font-size:0.78rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-dark); border-bottom:1px solid var(--border-color, #e2e8f0); width:25%;">
                                        Title</th>
                                    <th
                                        style="padding:12px 16px; text-align:left; font-size:0.78rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-dark); border-bottom:1px solid var(--border-color, #e2e8f0); width:35%;">
                                        Message</th>
                                    <th
                                        style="padding:12px 16px; text-align:left; font-size:0.78rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-dark); border-bottom:1px solid var(--border-color, #e2e8f0); width:18%;">
                                        Target Area</th>
                                    <th
                                        style="padding:12px 16px; text-align:left; font-size:0.78rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-dark); border-bottom:1px solid var(--border-color, #e2e8f0); width:15%;">
                                        Expired On</th>
                                    <th
                                        style="padding:12px 16px; text-align:center; font-size:0.78rem; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:var(--text-dark); border-bottom:1px solid var(--border-color, #e2e8f0); width:7%;">
                                        Action</th>
                                </tr>
                            </thead>
                            <tbody id="archive-tbody"></tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <div class="modal-footer">
            <button class="btn-cancel modal-close" data-modal="modal-archived-announcements">Close</button>
        </div>
    </div>
</div>

<!-- Confirm Delete Archived Modal -->
<div id="modal-delete-archived" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h3>Delete Archived Announcement</h3>
            <button class="modal-close" data-modal="modal-delete-archived">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="del-archived-id" />
            <div
                style="display:flex; flex-direction:column; align-items:center; gap:12px; padding:16px 0; text-align:center;">
                <span class="material-symbols-outlined" style="font-size:52px; color:#ef4444;">delete</span>
                <p style="font-size:0.95rem; color:var(--text-dark);">
                    Are you sure you want to permanently delete
                </p>
                <strong id="del-archived-title" style="font-size:1rem; color:var(--text-dark);"></strong>
                <p style="font-size:0.82rem; color:var(--text-muted);">This action cannot be undone.</p>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-cancel modal-close" data-modal="modal-delete-archived">Cancel</button>
            <button class="btn-delete-confirm" id="confirm-delete-archived">
                <span class="material-symbols-outlined">delete</span>
                Delete Permanently
            </button>
        </div>
    </div>
</div>