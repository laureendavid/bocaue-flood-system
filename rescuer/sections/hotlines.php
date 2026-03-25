<section id="page-hotlines" class="page active" aria-labelledby="hotlines-heading">
  <header class="page-header">
    <h2 id="hotlines-heading">Hotlines</h2>
  </header>

  <div class="toolbar">
    <div class="search-box">
      <span class="material-symbols-outlined">search</span>
      <input type="search" id="hotlines-search" placeholder="Search Hotlines" aria-label="Search hotlines" />
    </div>
    <button class="btn-filter" aria-label="Filter hotlines">
      <span class="material-symbols-outlined" style="font-size:16px">filter_alt</span>
      Filter
    </button>
  </div>

  <div class="table-wrap" style="max-height: calc(95vh - 200px); overflow-y: auto;">
    <table aria-label="Emergency hotlines table">
      <thead>
        <tr>
          <th>Hotline Name</th>
          <th>Barangay</th>
          <th>Contact Number</th>
          <th class="col-center">Action</th>
        </tr>
      </thead>
      <tbody id="hotlines-table-body">
        <tr class="empty-row">
          <td colspan="4">No hotlines available.</td>
        </tr>
      </tbody>
    </table>
  </div>
</section>