<style>
  /* Force override with ID specificity */
  #page-dashboard .dashboard-evac-row .card,
  #page-dashboard .dashboard-bottom-row .card {
    max-height: 340px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    padding: 0;
    /* remove padding from card, move it inside */
  }

  /* Card header stays fixed at top, doesn't scroll */
  #page-dashboard .dashboard-evac-row .card>.card-header,
  #page-dashboard .dashboard-bottom-row .card>.card-header {
    position: sticky;
    top: 0;
    background: var(--white);
    z-index: 2;
    flex-shrink: 0;
    padding: 16px 20px 12px;
    border-bottom: 1px solid var(--border);
  }

  /* Hotlines / map title stays fixed */
  #page-dashboard .dashboard-evac-row .card>.card-title--mb,
  #page-dashboard .dashboard-bottom-row .card>.card-title--mb {
    position: sticky;
    top: 0;
    background: var(--white);
    z-index: 2;
    flex-shrink: 0;
    padding: 16px 20px 12px;
    margin-bottom: 0;
    border-bottom: 1px solid var(--border);
  }

  /* Scrollable content area inside each card */
  #page-dashboard .dashboard-evac-row .card>table,
  #page-dashboard .dashboard-evac-row .card>#hotlines-list,
  #page-dashboard .dashboard-bottom-row .card>[aria-label="Announcements list"] {
    flex: 1;
    overflow-y: auto;
    padding: 12px 20px;
  }

  /* Map card must NOT scroll */
  #page-dashboard .map-card--dashboard {
    overflow: hidden !important;
    padding: 20px !important;
  }

  @media (max-width: 640px) {

    #page-dashboard .dashboard-evac-row .card,
    #page-dashboard .dashboard-bottom-row .card {
      max-height: 260px;
    }
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
        <p style="font-size:0.72rem; color:#64748b; font-style:italic; margin:0; font-weight:600;">Click a center to
          view its location on
          the map.</p>
      </header>
      <table style="width:100%; border-collapse:collapse;">
        <tbody id="evac-monitor-tbody">
          <tr class="empty-row">
            <td colspan="3">Loading evacuation centers...</td>
          </tr>
        </tbody>
      </table>
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