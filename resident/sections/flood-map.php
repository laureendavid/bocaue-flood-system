<section id="page-flood-map" class="page active">

  <div id="page-content">
    <div class="map-page-card">
      <h2>Flood Map</h2>

      <div class="map-search-bar">
        <span class="material-symbols-outlined">search</span>
        <input
          type="text"
          id="map-search"
          placeholder="Search location in Bocaue… (press Enter)"
          autocomplete="off"
        />
        <button class="filter-toggle-btn" id="filter-toggle-btn" title="Toggle Filters" aria-label="Toggle Filters">
          <span class="material-symbols-outlined">filter_list</span>
        </button>
      </div>

      <div class="filter-bar" id="filter-bar">
        <button class="filter-btn active" data-filter="all">All</button>
        <button class="filter-btn passable active" data-filter="1">
          <span class="dot"></span> Passable
        </button>
        <button class="filter-btn limited active" data-filter="2">
          <span class="dot"></span> Limited Access
        </button>
        <button class="filter-btn impassable active" data-filter="3">
          <span class="dot"></span> Impassable
        </button>
      </div>

      <div id="flood-map" class="resident-leaflet-map"></div>

      <div class="map-legend">
        <div class="legend-item">
          <div class="legend-dot" style="background:#22c55e;"></div> Passable (severity 1)
        </div>
        <div class="legend-item">
          <div class="legend-dot" style="background:#eab308;"></div> Limited Access (severity 2)
        </div>
        <div class="legend-item">
          <div class="legend-dot" style="background:#ef4444;"></div> Impassable (severity 3)
        </div>
        <div class="legend-item">
          <span class="material-symbols-outlined" style="font-size:14px;color:#64748b;">info</span>
          Approved flood reports from the database
        </div>
      </div>
    </div>
  </div>

</section>
