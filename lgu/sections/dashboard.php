<section id="page-dashboard" class="page active" aria-labelledby="dashboard-heading">
  <header class="page-header">
    <h2 id="dashboard-heading">Dashboard</h2>
  </header>

  <!-- Stat Cards -->
  <?php include '../includes/fetch_report_stats.php'; ?>
  <div class="dashboard-stats">
    <article class="stat-card pending">
      <p class="stat-label">Total of Pending Reports</p>
      <strong class="stat-value">
        <?= htmlspecialchars($pending_count) ?>
      </strong>
    </article>
    <article class="stat-card approved">
      <p class="stat-label">Total of Approved Reports</p>
      <strong class="stat-value">
        <?= htmlspecialchars($approved_count) ?>
      </strong>
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
        <h3 id="announcements-heading" class="card-title">Recent Announcements</h3>
      </header>
      <div class="card-scroll" aria-label="Announcements list" style="max-height: 400px; overflow-y: auto;">
        <?php $announcement_limit = 3;
        include '../includes/fetch_commAnnouncement.php'; ?>
      </div>
    </section>

  </div>
</section>