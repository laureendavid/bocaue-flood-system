<section id="page-evacuation-center" class="page active" aria-labelledby="evac-heading">
  <header class="page-header">
    <h2 id="evac-heading">Evacuation Centers</h2>
  </header>

  <div class="toolbar">
    <div class="search-box">
      <span class="material-symbols-outlined">search</span>
      <input type="search" id="evac-search" placeholder="Search Centers" aria-label="Search evacuation centers" />
    </div>
    <button class="btn-filter" aria-label="Filter centers">
      <span class="material-symbols-outlined" style="font-size:16px">filter_alt</span>
      Filter
    </button>
  </div>

  <div class="table-wrap">
    <table aria-label="Evacuation centers table">
      <thead>
        <tr>
          <th>Center Name</th>
          <th>Occupancy</th>
          <th>Status</th>
          <th class="col-center">Action</th>
        </tr>
      </thead>
      <tbody id="evac-table-body">
        <tr class="empty-row"><td colspan="4">No evacuation centers available.</td></tr>
      </tbody>
    </table>
  </div>
</section>