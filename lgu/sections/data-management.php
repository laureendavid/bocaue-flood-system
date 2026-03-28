<section id="page-data-management" class="page active" aria-labelledby="dman-heading">
  <header class="page-header">
    <h2 id="dman-heading">Data Management</h2>
  </header>

  <div class="dm-tabs" role="tablist" aria-label="Data management sections">
    <button class="dm-tab active" data-tab="hotlines" role="tab" aria-selected="true"
      aria-controls="dm-hotlines">Hotlines</button>
    <button class="dm-tab" data-tab="evacuation-centers" role="tab" aria-selected="false"
      aria-controls="dm-evac">Evacuation Centers</button>
    <button class="dm-tab" data-tab="announcement" role="tab" aria-selected="false"
      aria-controls="dm-announce">Announcement</button>
  </div>

  <!-- Panel: Hotlines -->
  <div id="dm-hotlines" class="dm-panel" data-panel="hotlines" role="tabpanel">
    <div class="toolbar">
      <div class="search-box">
        <span class="material-symbols-outlined">search</span>
        <input type="search" id="hotline-search" placeholder="Search Hotlines" aria-label="Search hotlines" />
      </div>
      <div class="filter-wrapper">
        <button class="btn-filter" id="hotline-filter-btn">
          <span class="material-symbols-outlined" style="font-size:16px">filter_list</span>
          Filter by Barangay and Hotline Name
        </button>
        <div class="filter-dropdown" id="hotline-filter-dropdown" style="display:none;">
          <p class="filter-label">Filter by Barangay</p>
          <div id="hotline-barangay-filters"></div>
          <p class="filter-label" style="margin-top:10px;">Filter by Hotline Name</p>
          <div id="hotline-name-filters"></div>
        </div>
      </div>
      <button class="btn-add" id="btn-add-hotline">
        <span class="material-symbols-outlined">add</span>
        Add Hotline
      </button>
    </div>
    <div class="table-wrap">
      <table aria-label="Hotlines table">
        <thead>
          <tr>
            <th>Barangay</th>
            <th>Hotline Name</th>
            <th>Contact Number</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody id="hotline-tbody">
          <?php include('../includes/fetch_hotlines_table.php'); ?>
        </tbody>
      </table>
      <div id="hotline-empty" style="display:none;" class="empty-row">
        <p>No hotlines match your search.</p>
      </div>
    </div>
  </div>


  <!-- Panel: Evacuation Centers -->
  <div id="dm-evac" class="dm-panel" data-panel="evacuation-centers" role="tabpanel" style="display:none">
    <div class="toolbar">
      <div class="search-box">
        <span class="material-symbols-outlined">search</span>
        <input type="search" placeholder="Search Centers" aria-label="Search evacuation centers" />
      </div>
      <button class="btn-add" id="btn-add-center">
        <span class="material-symbols-outlined">add</span>
        Add Center
      </button>
    </div>
    <div class="table-wrap">
      <table aria-label="Evacuation centers table">
        <thead>
          <tr>
            <th>Center Name</th>
            <th>Occupancy</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php include '../includes/fetch_evac_centers.php'; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Panel: Announcement -->
  <div id="dm-announce" class="dm-panel" data-panel="announcement" role="tabpanel" style="display:none">
    <div class="toolbar">
      <div class="search-box">
        <span class="material-symbols-outlined">search</span>
        <input type="search" id="announcement-search" placeholder="Search Announcement"
          aria-label="Search announcements" />
      </div>
      <button class="btn-filter" id="btn-view-archives">
        <span class="material-symbols-outlined" style="font-size:16px">inventory_2</span>
        View Archives
      </button>
      <button class="btn-add" id="btn-add-announcement">
        <span class="material-symbols-outlined">add</span>
        Add Announcement
      </button>
    </div>
    <div class="table-wrap">
      <table aria-label="Announcements table">
        <thead>
          <tr>
            <th>Title</th>
            <th>Message</th>
            <th>Target Area</th>
            <th>Expiry Date</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody id="announcement-tbody">
          <?php include('../includes/fetch_announcements.php'); ?>
        </tbody>
      </table>
      <div id="announcement-empty" style="display:none;" class="empty-row">
        <p>No announcements match your search.</p>
      </div>
    </div>
  </div>