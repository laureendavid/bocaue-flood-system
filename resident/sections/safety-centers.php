<?php
/* sections/safety-centers.php — map data via api/fetch-safety-centers.php */
?>

<section id="page-safety-centers" class="page active">

  <div id="page-content-safety">

    <div class="sc-title-area">
      <div class="sc-page-title">Safety Centers</div>
    </div>

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
              <p>Tap a pin for details. Centers and markers load from the database.</p>
            </div>
            <p class="sc-map-notice" id="sc-map-notice" style="display:none;" role="status"></p>
            <div id="safety-map" class="resident-leaflet-map" aria-label="Safety centers map"></div>
          </section>
        </div>
      </div>
    </div>

  </div>

</section>

<div id="sc-center-modal" class="sc-modal" aria-hidden="true" role="dialog" aria-labelledby="sc-modal-title">
  <div class="sc-modal-backdrop" id="sc-modal-backdrop"></div>
  <div class="sc-modal-dialog">
    <button type="button" class="sc-modal-close" id="sc-modal-close" aria-label="Close details">
      <span class="material-symbols-outlined">close</span>
    </button>

    <div class="sc-modal-header">
      <div class="sc-modal-icon" id="sc-modal-status-icon">
        <span class="material-symbols-outlined">home_work</span>
      </div>
      <div class="sc-modal-heading">
        <h3 id="sc-modal-title">Safety Center</h3>
        <p id="sc-modal-subtitle"></p>
      </div>
      <span class="sc-modal-status-badge" id="sc-modal-status-badge">Available</span>
    </div>

    <div class="sc-modal-body">
      <div class="sc-modal-row">
        <span class="material-symbols-outlined">location_on</span>
        <div>
          <span class="sc-modal-label">Address</span>
          <p id="sc-modal-address">—</p>
        </div>
      </div>
      <div class="sc-modal-row">
        <span class="material-symbols-outlined">call</span>
        <div>
          <span class="sc-modal-label">Contact Number</span>
          <p id="sc-modal-contact">—</p>
        </div>
      </div>
      <div class="sc-modal-row" id="sc-modal-hours-row">
        <span class="material-symbols-outlined">schedule</span>
        <div>
          <span class="sc-modal-label">Operating Hours</span>
          <p id="sc-modal-hours">—</p>
        </div>
      </div>
      <div class="sc-modal-row sc-modal-row--block" id="sc-modal-description-row">
        <span class="material-symbols-outlined">info</span>
        <div>
          <span class="sc-modal-label">Additional Information</span>
          <p id="sc-modal-description">—</p>
        </div>
      </div>
      <div class="sc-modal-stats">
        <div class="sc-modal-stat">
          <span class="sc-modal-label">Capacity</span>
          <strong id="sc-modal-capacity">0</strong>
        </div>
        <div class="sc-modal-stat">
          <span class="sc-modal-label">Occupancy</span>
          <strong id="sc-modal-occupancy">0</strong>
        </div>
        <div class="sc-modal-stat">
          <span class="sc-modal-label">Availability</span>
          <strong id="sc-modal-availability">0%</strong>
        </div>
      </div>
      <div class="sc-modal-capacity-bar">
        <div class="sc-modal-capacity-fill" id="sc-modal-capacity-fill"></div>
      </div>
      <p class="sc-modal-distance" id="sc-modal-distance"></p>
    </div>

    <div class="sc-modal-footer">
      <a href="#" class="sc-modal-btn sc-modal-btn--call" id="sc-modal-call-btn" style="display:none;">
        <span class="material-symbols-outlined">call</span>
        Call Center
      </a>
      <button type="button" class="sc-modal-btn sc-modal-btn--secondary" id="sc-modal-map-btn">
        <span class="material-symbols-outlined">map</span>
        Show on Map
      </button>
    </div>
  </div>
</div>
