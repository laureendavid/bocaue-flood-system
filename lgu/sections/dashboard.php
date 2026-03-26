<style>
  /* ===== STAT CARDS ===== */
  #page-dashboard .dashboard-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 20px;
  }

  #page-dashboard .stat-card:nth-child(1) {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border: none;
    color: white;
  }

  #page-dashboard .stat-card:nth-child(2) {
    background: linear-gradient(135deg, #22c55e, #15803d);
    border: none;
    color: white;
  }

  #page-dashboard .stat-card .stat-label {
    color: rgba(255, 255, 255, 0.85);
  }

  #page-dashboard .stat-card .stat-value {
    color: white;
  }

  /* Keep stat cards side by side on mobile */
  @media (max-width: 640px) {
    #page-dashboard .dashboard-stats {
      grid-template-columns: 1fr 1fr !important;
      gap: 10px;
    }

    #page-dashboard .stat-card {
      padding: 16px 12px;
    }

    #page-dashboard .stat-value {
      font-size: 2rem;
    }

    #page-dashboard .dashboard-evac-row .card,
    #page-dashboard .dashboard-bottom-row .card {
      max-height: 260px;
    }
  }

  /* ===== SCROLLABLE CARDS ===== */
  #page-dashboard .dashboard-evac-row .card,
  #page-dashboard .dashboard-bottom-row .card {
    display: flex;
    flex-direction: column;
    overflow: hidden;
    padding: 0;
    max-height: 340px;
  }

  /* Sticky card headers — light mint/teal */
  #page-dashboard .card>.card-header {
    flex-shrink: 0;
    padding: 14px 20px;
    border-bottom: none;
    background: #b2dede;
    /* ← light teal from image */
    position: sticky;
    top: 0;
    z-index: 2;
  }

  #page-dashboard .card>.card-title--mb {
    flex-shrink: 0;
    padding: 14px 20px;
    margin-bottom: 0;
    border-bottom: none;
    background: #b2dede;
    /* ← same light teal */
  }

  /* White text on teal headers */
  #page-dashboard .card>.card-header .card-title,
  #page-dashboard .card>.card-header p,
  #page-dashboard .card>.card-title--mb {
    color: #1e293b;
    /* dark slate — stays legible on light teal */
    font-weight: 700;
  }

  /* Scrollable body inside cards */
  #page-dashboard .card>table,
  #page-dashboard .card>#hotlines-list,
  #page-dashboard .card>div[aria-label="Announcements list"] {
    flex: 1;
    overflow-y: auto;
    padding: 12px 20px;
    min-height: 0;
  }

  /* Map card must NOT scroll */
  #page-dashboard .map-card--dashboard {
    overflow: hidden !important;
    padding: 0 !important;
    max-height: 340px !important;
  }

  #page-dashboard .map-card--dashboard>.card-title--mb {
    background: var(--sidebar-bg);
    color: #1e293b;
    font-weight: 700;
    padding: 14px 20px;
    margin-bottom: 0;
  }
</style>
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
        <p style="font-size:0.72rem; font-style:italic; margin:0; font-weight:600;">
          Click a center to view its location on the map.
        </p>
      </header>
      <div style="flex:1; overflow-y:auto; overflow-x:auto; min-height:0;">
        <table style="width:100%; border-collapse:collapse; min-width:380px;">
          <tbody id="evac-monitor-tbody">
            <tr class="empty-row">
              <td colspan="3">Loading evacuation centers...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <section aria-labelledby="hotlines-heading" class="card">
      <h3 id="hotlines-heading" class="card-title card-title--mb">Hotlines</h3>
      <div id="hotlines-list" aria-label="Emergency hotlines">
        <p class="placeholder-text">Loading hotlines...</p>
      </div>
    </section>
  </div>

  <!-- Announcements + Flood Map -->
  <div class="dashboard-bottom-row">
    <section aria-labelledby="announcements-heading" class="card">
      <header class="card-header">
        <h3 id="announcements-heading" class="card-title">Announcements</h3>
      </header>
      <div aria-label="Announcements list">
        <?php include '../includes/fetch_commAnnouncement.php'; ?>
      </div>
    </section>

    <section aria-labelledby="map-heading" class="card map-card--dashboard">
      <h3 id="map-heading" class="card-title card-title--mb">Flood Monitoring Map</h3>
      <div class="map-inset-placeholder">
        <p class="placeholder-text" style="padding:0">Map will render here.</p>
      </div>
    </section>
  </div>
</section>