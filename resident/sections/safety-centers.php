<?php
/* =============================================================
   sections/safety-centers.php
   Included by main.php when ?page=safety-centers
  Requires: residentStyles.css safety centers section
            resident.js safety centers module
            api/fetch-safety-centers.php
   No direct DB queries here — JS fetches from the API.
   ============================================================= */
?>

<!-- Leaflet CSS — only loads on this page -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/MarkerCluster.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/MarkerCluster.Default.css"/>

<section id="page-safety-centers" class="page active">

  <div id="page-content-safety">

    <!-- Title -->
    <div class="sc-title-area">
      <div class="sc-page-title">Safety Centers</div>
    </div>

    <!-- Tagline banner -->
    <div class="sc-tagline">
      <div class="tagline-icon-wrap">
        <span class="material-symbols-outlined">shield_with_heart</span>
      </div>
      <div class="tagline-text">
        <div class="tagline-eyebrow">Bocaue, Bulacan</div>
        <div class="tagline-heading">Quick Access to Nearby Safety Centers</div>
      </div>
    </div>

    <div class="sc-body">
      <div class="sc-container">
        <div class="sc-layout">
          <aside class="sc-panel">
            <div class="sc-panel-head">
              <div>
                <h2 class="sc-panel-title">Evacuation Centers</h2>
                <p class="sc-panel-subtitle">Live capacity and occupancy in Bocaue, Bulacan.</p>
              </div>
              <span class="sc-count-pill" id="sc-count-pill">0 centers</span>
            </div>

            <div class="sc-search-wrap">
              <div class="sc-search">
                <span class="material-symbols-outlined">search</span>
                <input
                  type="text"
                  id="sc-search-input"
                  placeholder="Search center or address..."
                  autocomplete="off"
                />
              </div>
            </div>

            <div class="sc-error" id="sc-error"></div>

            <div class="sc-loading" id="sc-loading">
              <div class="sc-spinner"></div>
              Loading safety centers...
            </div>

            <div class="sc-list" id="sc-list"></div>

            <div class="sc-no-results" id="sc-no-results">No centers found for your search.</div>
          </aside>

          <section class="sc-map-area">
            <div class="sc-map-head">
              <h3>Interactive Map</h3>
              <p>Markers are limited within Bocaue municipal boundary.</p>
            </div>
            <div id="safety-map"></div>
          </section>
        </div>
      </div>
    </div>

  </div>

</section>

<!-- Leaflet JS + page script — only loads on this page -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.markercluster/1.5.3/leaflet.markercluster.js"></script>
