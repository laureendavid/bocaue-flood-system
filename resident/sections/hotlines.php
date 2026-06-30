<?php
/**
 * Hotlines page — data loaded dynamically via ../api/fetch_hotlines.php (resident.js).
 * Included by main.php when ?page=hotlines
 */
?>

<section id="page-hotlines" class="page active">

  <div id="page-content-hotlines">

    <div class="hl-title-area">
      <div class="hl-page-title">Hotlines</div>
    </div>

    <div class="hl-tagline">
      <div class="tagline-icon-wrap">
        <span class="material-symbols-outlined">wifi_calling_3</span>
      </div>
      <div class="tagline-text">
        <div class="tagline-eyebrow">Bocaue, Bulacan</div>
        <div class="tagline-heading">Call trusted emergency contacts instantly</div>
      </div>
    </div>

    <div class="hl-body">
      <div class="hl-inner">

        <div class="hl-error-banner" id="hl-error" style="display:none;"></div>

        <div class="hl-search">
          <span class="material-symbols-outlined">search</span>
          <input
            type="text"
            id="hl-search-input"
            placeholder="Search hotlines or barangay…"
            autocomplete="off"
          />
        </div>

        <div class="hl-tabs">
          <button class="hl-tab active" data-cat="all">All</button>
          <button class="hl-tab" data-cat="emergency">Emergency</button>
          <button class="hl-tab" data-cat="medical">Medical</button>
          <button class="hl-tab" data-cat="police">Police</button>
          <button class="hl-tab" data-cat="lgu">LGU / Barangay</button>
          <button class="hl-tab" data-cat="rescue">Rescue</button>
        </div>

        <div class="hl-loading" id="hl-loading">
          <div class="hl-spinner"></div>
          Loading hotlines…
        </div>

        <div class="hl-list" id="hl-list"></div>

        <div class="hl-no-results" id="hl-no-results" style="display:none;">
          No hotlines found in the database.
        </div>

      </div>
    </div>

  </div>

</section>
