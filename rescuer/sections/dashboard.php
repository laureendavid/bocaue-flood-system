<style>
  /* ===== RESCUER DASHBOARD STAT CARDS ===== */
  #page-dashboard .dashboard-stats-row {
    display: grid;
    grid-template-columns: 3fr 2fr;
    gap: 16px;
    margin-bottom: 20px;
  }

  #page-dashboard .dashboard-stats-row .card:nth-child(1) {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border: none;
  }

  #page-dashboard .dashboard-stats-row .card:nth-child(2) {
    background: linear-gradient(135deg, #22c55e, #15803d);
    border: none;
  }

  #page-dashboard .dashboard-stats-row .card-section-label {
    color: rgba(255, 255, 255, 0.85);
    font-size: 0.82rem;
    margin-bottom: 16px;
  }

  #page-dashboard .rescue-stat-label {
    color: rgba(255, 255, 255, 0.75);
  }

  #page-dashboard .rescue-stat-value {
    color: white;
  }

  #page-dashboard .rescue-stat-item {
    border-left: 2px solid rgba(255, 255, 255, 0.3);
    padding: 8px 20px;
  }

  #page-dashboard .rescue-stat-item:first-child {
    border-left: none;
    padding-left: 0;
  }

  /* ===== BOTTOM ROW CARDS ===== */
  #page-dashboard .dashboard-bottom-row .card {
    display: flex;
    flex-direction: column;
    overflow: hidden;
    padding: 0;
  }

  #page-dashboard .dashboard-bottom-row .card-section-label {
    flex-shrink: 0;
    padding: 14px 20px;
    margin-bottom: 0;
    background: var(--sidebar-bg);
    color: #1e293b;
    font-weight: 700;
    border-bottom: none;
  }

  #page-dashboard .dashboard-bottom-row .hotline-district-list,
  #page-dashboard .dashboard-bottom-row .evac-progress-list {
    flex: 1;
    overflow-y: auto;
    padding: 12px 20px;
    min-height: 0;
    max-height: calc(90vh - 380px);
  }

  /* ===== KEEP SIDE BY SIDE ON MOBILE ===== */
  @media (max-width: 640px) {
    #page-dashboard .dashboard-stats-row {
      grid-template-columns: 1fr 1fr !important;
      gap: 10px;
    }

    #page-dashboard .rescue-stats-grid {
      grid-template-columns: 1fr !important;
    }

    #page-dashboard .rescue-stat-item {
      border-left: none;
      border-top: 1px solid rgba(255, 255, 255, 0.3);
      padding: 8px 0;
    }

    #page-dashboard .rescue-stat-item:first-child {
      border-top: none;
    }

    #page-dashboard .rescue-stat-value {
      font-size: 2rem;
    }

    #page-dashboard .activity-stats-grid {
      grid-template-columns: 1fr 1fr !important;
    }
  }
</style>

<section id="page-dashboard" class="page active" aria-labelledby="dashboard-heading">
  <header class="page-header">
    <h2 id="dashboard-heading">Dashboard</h2>
  </header>

  <div class="dashboard-stats-row">
    <article class="card" aria-labelledby="rescue-status-heading">
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

    <article class="card" aria-labelledby="your-activity-heading">
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

  <div class="dashboard-bottom-row">
    <section class="card dashboard-card-stretch" aria-labelledby="dash-hotlines-heading">
      <h3 id="dash-hotlines-heading" class="card-section-label">Hotlines</h3>
      <ul class="hotline-district-list" id="dash-hotlines-list" aria-label="Emergency hotlines by district">
        <li class="empty-state-inline">No hotlines available.</li>
      </ul>
    </section>

    <section class="card dashboard-card-stretch" aria-labelledby="dash-evac-heading">
      <h3 id="dash-evac-heading" class="card-section-label">Evacuation Centers</h3>
      <ul class="evac-progress-list" id="dash-evac-list" aria-label="Evacuation center occupancy">
        <li class="empty-state-inline">No evacuation centers available.</li>
      </ul>
    </section>
  </div>
</section>