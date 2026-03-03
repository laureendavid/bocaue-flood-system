<section id="page-dashboard" class="page active" aria-labelledby="dashboard-heading">
  <header class="page-header">
    <h2 id="dashboard-heading">Dashboard</h2>
  </header>

  <!-- Stat Cards -->
  <div class="dashboard-stats">
    <article class="stat-card">
      <p class="stat-label">Total of Pending Reports</p>
      <strong class="stat-value">—</strong>
    </article>
    <article class="stat-card">
      <p class="stat-label">Total of Approved Reports</p>
      <strong class="stat-value">—</strong>
    </article>
  </div>

  <!-- Evacuation Centers + Hotlines -->
  <div class="dashboard-evac-row">
    <section aria-labelledby="evac-heading" class="card">
      <header class="card-header">
        <h3 id="evac-heading" class="card-title">Evacuation Centers</h3>
        <button class="btn-link">View Map View</button>
      </header>
      <ul class="evac-list" aria-label="Evacuation center capacity">
        <li class="placeholder-text">No evacuation centers to display.</li>
      </ul>
    </section>

    <section aria-labelledby="hotlines-heading" class="card">
      <h3 id="hotlines-heading" class="card-title card-title--mb">Hotlines</h3>
      <ul style="list-style:none" aria-label="Emergency hotlines">
        <li class="placeholder-text">No hotlines to display.</li>
      </ul>
    </section>
  </div>

  <!-- Announcements + Flood Map -->
  <div class="dashboard-bottom-row">
    <section aria-labelledby="announcements-heading" class="card">
      <header class="card-header">
        <h3 id="announcements-heading" class="card-title">Announcements</h3>
        <button class="btn-icon-add" aria-label="Add announcement">
          <span class="material-symbols-outlined" style="font-size:18px">add</span>
        </button>
      </header>
      <ul style="list-style:none" aria-label="Announcements list">
        <li class="placeholder-text">No announcements to display.</li>
      </ul>
    </section>

    <section aria-labelledby="map-heading" class="card map-card--dashboard">
      <h3 id="map-heading" class="card-title card-title--mb">Flood Monitoring Map</h3>
      <div class="map-inset-placeholder">
        <p class="placeholder-text" style="padding:0">Map will render here.</p>
      </div>
    </section>
  </div>
</section>
