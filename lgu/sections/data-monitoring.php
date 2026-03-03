<section id="page-data-monitoring" class="page active" aria-labelledby="dm-heading">
  <header class="page-header">
    <h2 id="dm-heading">Data Monitoring</h2>
  </header>

  <div class="monitoring-grid">
    <div class="map-card">
      <h3>Flood Monitoring Map</h3>
      <div id="flood-map" class="flood-map" style="width:100%; height:320px; border-radius:8px; margin-bottom:14px; border:1px solid var(--border);"></div>
      <div class="map-legend">
        <span class="legend-pill legend-impassable">Impassable</span>
        <span class="legend-pill legend-limited">Limited Access</span>
        <span class="legend-pill legend-passable">Passable</span>
      </div>
    </div>

    <aside class="hotlines-card" aria-labelledby="monitoring-hotlines-heading">
  <h3 id="monitoring-hotlines-heading">Hotlines</h3>
  <div id="hotlines-list">
    <p class="placeholder-text placeholder-text--padded">Loading hotlines...</p>
  </div>
</aside>
  </div>

  <section class="evac-section" aria-labelledby="evac-monitor-heading">
    <h3 id="evac-monitor-heading">Evacuation Centers</h3>
    <div class="evac-table-wrap evac-table-wrap--mt">
      <table aria-label="Evacuation center status">
        <thead>
          <tr>
            <th class="col-center">Center</th>
            <th>Capacity</th>
            <th class="col-status">Status</th>
          </tr>
        </thead>
        <tbody>
          <tr class="empty-row">
            <td colspan="3">No evacuation centers to display.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</section>