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
        <p class="section-note">
          Click a center to view its location on the map.
        </p>
      </header>
      <div class="card-scroll">
        <table>
          <tbody id="evac-monitor-tbody">
            <tr class="empty-row">
              <td colspan="3">Loading evacuation centers...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <section aria-labelledby="hotlines-heading" class="card">
      <h3 id="hotlines-heading" class="card-title card-soft-header">Hotlines</h3>
      <div id="hotlines-list" class="card-scroll" aria-label="Emergency hotlines">
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
      <h3 id="map-heading" class="card-title card-soft-header">Flood Monitoring Map</h3>
      <div class="map-inset-placeholder">
        <p class="placeholder-text">Map will render here.</p>
      </div>
    </section>
  </div>
</section>