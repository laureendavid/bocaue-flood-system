<?php
/* =============================================================
   sections/safety-centers.php
   Included by main.php when ?page=safety-centers
   Requires: assets/css/safety-centers.css
             assets/js/safety-centers.js
             api/fetch-safety-centers.php
   No direct DB queries here — JS fetches from the API.
   ============================================================= */
?>

<!-- Leaflet CSS — only loads on this page -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css"/>

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

    <!-- Body -->
    <div class="sc-body">
      <div class="sc-container">

        <!-- Leaflet map -->
        <div id="safety-map"></div>

        <!-- Search -->
        <div class="sc-search-wrap">
          <div class="sc-search">
            <span class="material-symbols-outlined">search</span>
            <input
              type="text"
              id="sc-search-input"
              placeholder="Search by barangay or center name…"
              autocomplete="off"
            />
          </div>
        </div>

        <!-- Error banner -->
        <div class="sc-error" id="sc-error"></div>

        <!-- Loading spinner -->
        <div class="sc-loading" id="sc-loading">
          <div class="sc-spinner"></div>
          Loading safety centers…
        </div>

        <!-- Center cards — populated by safety-centers.js -->
        <div class="sc-list" id="sc-list"></div>

        <!-- Empty state -->
        <div class="sc-no-results" id="sc-no-results">No centers found.</div>

      </div>
    </div>

  </div>

</section>

<!-- Leaflet JS + page script — only loads on this page -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script src="assets/js/safety-centers.js"></script>