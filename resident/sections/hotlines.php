<?php
/* =============================================================
   sections/hotlines.php
   Included by main.php when ?page=hotlines
   Requires: assets/css/hotlines.css
             assets/js/hotlines.js
             api/fetch-hotlines.php  (your existing file)
   ============================================================= */
// No PHP DB queries needed here — the JS fetches from api/fetch-hotlines.php
?>

<section id="page-hotlines" class="page active">

  <div id="page-content-hotlines">

    <!-- Title -->
    <div class="hl-title-area">
      <div class="hl-page-title">Hotlines</div>
    </div>

    <!-- Tagline banner -->
    <div class="hl-tagline">
      <div class="tagline-icon-wrap">
        <span class="material-symbols-outlined">wifi_calling_3</span>
      </div>
      <div class="tagline-text">
        <div class="tagline-eyebrow">Bocaue, Bulacan</div>
        <div class="tagline-heading">Call trusted emergency contacts instantly</div>
      </div>
    </div>

    <!-- Body -->
    <div class="hl-body">
      <div class="hl-inner">

        <!-- Error banner (shown by JS on fetch failure) -->
        <div class="hl-error-banner" id="hl-error"></div>

        <!-- Search -->
        <div class="hl-search">
          <span class="material-symbols-outlined">search</span>
          <input
            type="text"
            id="hl-search-input"
            placeholder="Search hotlines or barangay…"
            autocomplete="off"
          />
        </div>

        <!-- Category tabs -->
        <div class="hl-tabs">
          <button class="hl-tab active" data-cat="all">All</button>
          <button class="hl-tab" data-cat="emergency">Emergency</button>
          <button class="hl-tab" data-cat="medical">Medical</button>
          <button class="hl-tab" data-cat="police">Police</button>
          <button class="hl-tab" data-cat="lgu">LGU / Barangay</button>
          <button class="hl-tab" data-cat="rescue">Rescue</button>
        </div>

        <!-- Loading spinner -->
        <div class="hl-loading" id="hl-loading">
          <div class="hl-spinner"></div>
          Loading hotlines…
        </div>

        <!-- Hotline cards — populated by hotlines.js -->
        <div class="hl-list" id="hl-list"></div>

        <!-- Empty state -->
        <div class="hl-no-results" id="hl-no-results">No hotlines found.</div>

      </div>
    </div>

  </div>

</section>