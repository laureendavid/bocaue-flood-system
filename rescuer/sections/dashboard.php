<section id="page-dashboard" class="page active" aria-labelledby="dashboard-heading">

  <header class="page-header">
    <h2 id="dashboard-heading">Dashboard</h2>
  </header>

  <!-- ===== STAT CARDS — matches LGU stat-card pattern ===== -->
  <div class="rdb-stats">

    <article class="rdb-stat stat--rescue">
      <p class="rdb-stat__label">Residents Needing Rescue</p>
      <span class="rdb-stat__value" id="stat-needing">—</span>
    </article>

    <article class="rdb-stat stat--inprogress">
      <p class="rdb-stat__label">Rescues in Progress</p>
      <span class="rdb-stat__value" id="stat-inprogress">—</span>
    </article>

    <article class="rdb-stat stat--rescued">
      <p class="rdb-stat__label">Residents Rescued</p>
      <span class="rdb-stat__value" id="stat-rescued">—</span>
    </article>

  </div>

  <!-- ===== BOTTOM ROW ===== -->
  <div class="rdb-bottom">

    <!-- Evacuation Centers — with progress bars like LGU -->
    <section class="rdb-panel rdb-panel--evac" aria-labelledby="dash-evac-heading">
      <div class="rdb-panel__header-row">
        <h3 class="rdb-panel__header" id="dash-evac-heading">Evacuation Centers</h3>
        <span class="rdb-panel__hint">Click a center to view its location on the map.</span>
      </div>
      <div class="rdb-panel__body rdb-panel__body--evac" id="dash-evac-list">
        <p class="rdb-empty">No evacuation centers available.</p>
      </div>
    </section>

    <!-- Hotlines -->
    <section class="rdb-panel" aria-labelledby="dash-hotlines-heading">
      <h3 class="rdb-panel__header" id="dash-hotlines-heading">Hotlines</h3>
      <div class="rdb-panel__body" id="dash-hotlines-list">
        <p class="rdb-empty">No hotlines available.</p>
      </div>
    </section>

  </div>

</section>