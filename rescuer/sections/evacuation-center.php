<section id="page-evacuation-center" class="page active" aria-labelledby="evac-heading">
  <header class="page-header">
    <h2 id="evac-heading">Evacuation Centers</h2>
  </header>

  <div class="toolbar">
    <div class="search-box">
      <span class="material-symbols-outlined">search</span>
      <input type="search" id="evac-search" placeholder="Search Centers" aria-label="Search evacuation centers" />
    </div>

    <div class="filter-wrapper" style="position:relative;">
      <button class="btn-filter" id="evac-filter-btn">
        <span class="material-symbols-outlined" style="font-size:16px">filter_list</span>
        Filter by Status
      </button>
      <div class="filter-dropdown" id="evac-filter-dropdown"
        style="display:none; position:absolute; top:100%; right:0; z-index:100; background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:12px 16px; min-width:180px; box-shadow:0 4px 12px rgba(0,0,0,0.1);">
        <p class="filter-label">Filter by Status</p>
        <div id="evac-status-filters" style="display:flex; flex-direction:column; gap:8px; margin-top:6px;">
          <label><input type="radio" name="evac-status" value="all" checked> All</label>
          <label><input type="radio" name="evac-status" value="available"> Available</label>
          <label><input type="radio" name="evac-status" value="near full"> Near Full</label>
          <label><input type="radio" name="evac-status" value="full"> Full</label>
        </div>
      </div>
    </div>
  </div>

  <div style="font-size:0.72rem; color:#64748b; font-style:italic; font-weight:600; margin-bottom:10px;">
    Click a center to view its location on the map.
  </div>

  <div class="table-wrap" style="max-height: calc(95vh - 200px); overflow-y: auto;">
    <table aria-label="Evacuation centers table">
      <thead>
        <tr>
          <th>Center Name</th>
          <th>Capacity</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody id="evac-table-body">
        <tr class="empty-row">
          <td colspan="3">Loading evacuation centers...</td>
        </tr>
      </tbody>
    </table>
  </div>
</section>