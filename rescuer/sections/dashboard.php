<section id="page-dashboard" class="page active" aria-labelledby="dashboard-heading">
  <header class="page-header">
    <h2 id="dashboard-heading">Dashboard</h2>
  </header>

  <!-- ===== TOP STAT CARDS ===== -->
  <div class="dashboard-stats-row">

    <article class="card rdb-stat-card" aria-labelledby="rescue-status-heading">
      <h3 id="rescue-status-heading" class="card-section-label">Overall Rescue Status</h3>
      <div class="rescue-stats-grid" id="rescue-stats-grid">
        <div class="rescue-stat-item">
          <p class="rescue-stat-label">Residents Needing Rescue</p>
          <strong class="rescue-stat-value" id="stat-needing">—</strong>
        </div>
        <div class="rescue-stat-item">
          <p class="rescue-stat-label">Rescues in Progress</p>
          <strong class="rescue-stat-value" id="stat-inprogress">—</strong>
        </div>
        <div class="rescue-stat-item">
          <p class="rescue-stat-label">Residents Rescued</p>
          <strong class="rescue-stat-value" id="stat-rescued">—</strong>
        </div>
      </div>
    </article>

    <article class="card rdb-stat-card rdb-stat-card--green" aria-labelledby="your-activity-heading">
      <h3 id="your-activity-heading" class="card-section-label">Your Activity</h3>
      <div class="activity-stats-grid">
        <div class="rescue-stat-item">
          <p class="rescue-stat-label">Your Ongoing Rescue</p>
          <strong class="rescue-stat-value" id="stat-my-ongoing">—</strong>
        </div>
        <div class="rescue-stat-item">
          <p class="rescue-stat-label">You Have Rescued</p>
          <strong class="rescue-stat-value" id="stat-my-rescued">—</strong>
        </div>
      </div>
    </article>

  </div>

  <!-- ===== BOTTOM ROW — Hotlines & Evac Centers ===== -->
  <div class="dashboard-bottom-row">

    <section class="card rdb-bottom-card" aria-labelledby="dash-hotlines-heading">
      <h3 id="dash-hotlines-heading" class="rdb-card-header">Hotlines</h3>
      <ul class="hotline-district-list rdb-scroll-list" id="dash-hotlines-list"
        aria-label="Emergency hotlines by district">
        <li class="empty-state-inline">No hotlines available.</li>
      </ul>
    </section>

    <section class="card rdb-bottom-card" aria-labelledby="dash-evac-heading">
      <h3 id="dash-evac-heading" class="rdb-card-header">Evacuation Centers</h3>
      <ul class="evac-progress-list rdb-scroll-list" id="dash-evac-list" aria-label="Evacuation center occupancy">
        <li class="empty-state-inline">No evacuation centers available.</li>
      </ul>
    </section>

  </div>
</section>