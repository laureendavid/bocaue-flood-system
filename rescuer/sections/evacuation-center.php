<section id="page-evacuation-center" class="page active" aria-labelledby="evac-heading">
  <header class="page-header">
    <h2 id="evac-heading">Evacuation Centers</h2>
  </header>

  <div class="toolbar">
    <div class="search-box">
      <span class="material-symbols-outlined">search</span>
      <input type="search" id="evac-search" placeholder="Search Centers" aria-label="Search evacuation centers" />
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