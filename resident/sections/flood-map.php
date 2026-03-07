<section id="page-flood-map" class="page active">

  <div id="page-content">
    <div class="map-page-card">
      <h2>Flood Map</h2>

      <!-- Search bar -->
      <div class="map-search-bar">
        <span class="material-symbols-outlined">search</span>
        <input
          type="text"
          id="map-search"
          placeholder="Search location... (press Enter)"
          autocomplete="off"
        />
        <button class="filter-toggle-btn" id="filter-toggle-btn" title="Toggle Filters" aria-label="Toggle Filters">
          <span class="material-symbols-outlined">filter_list</span>
        </button>
      </div>

      <!-- Filter buttons -->
      <div class="filter-bar" id="filter-bar">
        <button class="filter-btn impassable active" data-filter="impassable">
          <span class="dot"></span> Impassable
        </button>
        <button class="filter-btn limited active" data-filter="limited">
          <span class="dot"></span> Limited Access
        </button>
        <button class="filter-btn passable active" data-filter="passable">
          <span class="dot"></span> Passable
        </button>
      </div>

      <!-- Map container -->
      <div id="flood-map"></div>

      <!-- Legend -->
      <div class="map-legend">
        <div class="legend-item">
          <div class="legend-line" style="background:#dc2626;"></div> Impassable Road
        </div>
        <div class="legend-item">
          <div class="legend-line" style="background:#f59e0b;"></div> Limited Access
        </div>
        <div class="legend-item">
          <div class="legend-line" style="background:#22c55e;"></div> Passable Road
        </div>
        <div class="legend-item">
          <div class="legend-dot" style="background:#dc2626;"></div> Severe Flood Report
        </div>
        <div class="legend-item">
          <div class="legend-dot" style="background:#f59e0b;"></div> Moderate Flood Report
        </div>
      </div>
    </div>
  </div>

  <!-- Leaflet CSS + JS (only loaded on this page) -->
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css"
  />
  <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
  <script src="assets/js/flood-map.js"></script>

</section>