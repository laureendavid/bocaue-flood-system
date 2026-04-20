<section id="page-report-verification" class="page active" aria-labelledby="rv-heading">
  <header class="page-header">
    <h2 id="rv-heading">Report Verification</h2>
  </header>

  <div class="toolbar">
    <div class="search-box">
      <span class="material-symbols-outlined">search</span>
      <input
        type="search"
        id="report-search"
        placeholder="Search reports by resident, location, rescue details, or description"
        aria-label="Search reports"
      />
    </div>

    <label for="status-filter" class="report-filter-label">
      <span class="material-symbols-outlined" style="font-size:16px">filter_list</span>
      Status:
    </label>
    <select id="status-filter" class="btn-filter report-status-filter" aria-label="Filter reports by status">
      <option value="all">All</option>
      <option value="Pending" selected>Pending</option>
      <option value="Approved">Approved</option>
      <option value="Rejected">Rejected</option>
    </select>
  </div>

  <div class="table-wrap">
    <table aria-label="Report verification table">
      <thead>
        <tr>
          <th>Resident</th>
          <th>Location / Barangay</th>
          <th>Description</th>
          <th>Rescue People Count</th>
          <th>Rescue Description</th>
          <th>Image</th>
          <th>Date Submitted</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="report-verification-body">
        <?php include '../includes/fetch_reports.php'; ?>
      </tbody>
    </table>
  </div>

  <div id="report-action-modal" class="lgu-modal-backdrop" aria-hidden="true">
    <div class="lgu-modal-card" role="dialog" aria-modal="true" aria-labelledby="report-action-modal-title">
      <h3 id="report-action-modal-title">Confirm Action</h3>
      <p id="report-action-modal-message">Are you sure you want to continue?</p>
      <div class="lgu-modal-actions">
        <button type="button" class="btn-filter" id="report-action-cancel">Cancel</button>
        <button type="button" class="btn-add" id="report-action-confirm">Confirm</button>
      </div>
    </div>
  </div>

  <div id="report-image-lightbox" class="lgu-lightbox-backdrop" aria-hidden="true">
    <button type="button" class="lgu-lightbox-close" id="report-image-close" aria-label="Close image preview">
      <span class="material-symbols-outlined">close</span>
    </button>
    <img id="report-image-preview" src="" alt="Report preview" />
  </div>
</section>
