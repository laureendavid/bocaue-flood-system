<section id="page-community" class="page active" aria-labelledby="community-heading">

  <style>
    .community-grid {
      display: grid;
      grid-template-columns: 320px 1fr;
      gap: 20px;
      padding: 20px;
    }

    .community-column {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    /* =============================================
       FILTER STACK
    ============================================= */
    .community-filter-stack {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .comm-filter-bar {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
      padding: 10px 14px;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
    }

    .comm-filter-label {
      font-size: 0.7rem;
      font-weight: 600;
      color: #64748b;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      white-space: nowrap;
      margin-right: 4px;
      display: flex;
      align-items: center;
      gap: 4px;
      flex-shrink: 0;
    }

    .comm-filter-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 13px;
      border-radius: 8px;
      border: 1.5px solid #e2e8f0;
      background: #fff;
      color: #475569;
      font-size: 0.76rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.15s ease;
      white-space: nowrap;
    }

    .comm-filter-btn:hover:not(.active) {
      background: #f1f5f9;
      border-color: #94a3b8;
    }

    .comm-filter-btn.active {
      color: #fff;
    }

    .comm-filter-sep {
      color: #cbd5e1;
      font-size: 1rem;
      margin: 0 2px;
      flex-shrink: 0;
    }

    .comm-date-wrap {
      display: flex;
      align-items: center;
      gap: 6px;
      flex-wrap: wrap;
    }

    .comm-date-wrap label {
      font-size: 0.72rem;
      color: #94a3b8;
      white-space: nowrap;
    }

    .comm-date-wrap input[type="date"] {
      padding: 5px 9px;
      border-radius: 8px;
      font-size: 0.76rem;
      border: 1.5px solid #e2e8f0;
      background: #fff;
      color: #1e293b;
      cursor: pointer;
      font-family: inherit;
    }

    .comm-date-wrap input[type="date"]:focus {
      outline: none;
      border-color: #3b82f6;
    }

    .comm-apply-btn {
      padding: 5px 13px;
      border-radius: 8px;
      font-size: 0.76rem;
      font-weight: 600;
      border: 1.5px solid #2563eb;
      background: #3b82f6;
      color: #fff;
      cursor: pointer;
      transition: background 0.15s;
    }

    .comm-apply-btn:hover {
      background: #2563eb;
    }

    .comm-clear-btn {
      padding: 5px 10px;
      border-radius: 8px;
      font-size: 0.72rem;
      font-weight: 600;
      border: 1.5px solid #e2e8f0;
      background: transparent;
      color: #64748b;
      cursor: pointer;
      transition: background 0.15s;
    }

    .comm-clear-btn:hover {
      background: #f1f5f9;
    }

    .status-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      flex-shrink: 0;
    }

    .comm-active-info {
      display: none;
      align-items: center;
      gap: 6px;
      font-size: 0.72rem;
      color: #1e40af;
      padding: 5px 12px;
      background: #eff6ff;
      border: 1px solid #bfdbfe;
      border-radius: 8px;
    }

    .comm-active-info.show {
      display: inline-flex;
    }

    #comm-no-results {
      display: none;
      text-align: center;
      padding: 32px 16px;
      color: #94a3b8;
      font-size: 0.9rem;
    }

    /* POST CARD */
    .post-card {
      background: #fff;
      border-radius: 12px;
      padding: 15px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
      border: 1px solid #e8edf5;
      transition: box-shadow 0.2s;
    }

    .post-card:hover {
      box-shadow: 0 4px 18px rgba(0, 0, 0, 0.12);
    }

    .post-card__header {
      display: flex;
      align-items: center;
      margin-bottom: 10px;
    }

    .post-card__user {
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .post-card__avatar {
      width: 45px;
      height: 45px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #dbeafe;
    }

    .post-card__avatar--initials {
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
      font-weight: 700;
      color: #fff;
      flex-shrink: 0;
    }

    .post-card__user-info {
      display: flex;
      flex-direction: column;
      gap: 2px;
    }

    .post-card__name {
      font-weight: 600;
      font-size: 14px;
      color: #0f1f40;
    }

    .post-card__meta {
      font-size: 12px;
      color: #777;
    }

    .post-card__body {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .post-card__body--with-image {
      flex-direction: row;
      align-items: flex-start;
      gap: 14px;
    }

    .post-card__image-wrap {
      flex-shrink: 0;
      width: 160px;
      height: 110px;
      border-radius: 8px;
      overflow: hidden;
      background: #f3f4f6;
    }

    .post-card__image {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .post-card__content {
      flex: 1;
      min-width: 0;
    }

    .post-card__description {
      font-size: 0.875rem;
      color: #374151;
      line-height: 1.55;
      margin: 0 0 10px 0;
    }

    .post-card__tags {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
      margin-top: 4px;
    }

    .post-tag {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-size: 0.78rem;
      font-weight: 500;
      padding: 3px 9px;
      border-radius: 20px;
      border: 1px solid transparent;
    }

    .post-tag--water {
      background: #eff6ff;
      color: #1d4ed8;
      border-color: #bfdbfe;
    }

    .severity--passable {
      background: #f0fdf4;
      color: #15803d;
      border-color: #bbf7d0;
    }

    .severity--limited {
      background: #fffbeb;
      color: #b45309;
      border-color: #fde68a;
    }

    .severity--impassable {
      background: #fef2f2;
      color: #b91c1c;
      border-color: #fecaca;
    }

    .severity--neutral {
      background: #f1f5f9;
      color: #475569;
    }

    .post-card__footer {
      margin-top: 14px;
      padding-top: 12px;
      border-top: 1px solid #f0f4ff;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
    }

    .post-card__map-btns {
      display: flex;
      gap: 8px;
      align-items: center;
      margin-left: auto;
    }

    .rescue-badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 5px 14px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
    }

    .badge--danger {
      background: #fee2e2;
      color: #991b1b;
    }

    .badge--warning {
      background: #fef3c7;
      color: #92400e;
    }

    .badge--success {
      background: #dcfce7;
      color: #166534;
    }

    .badge--neutral {
      background: #f1f5f9;
      color: #475569;
    }

    .btn-map {
      background: #3498db;
      color: white;
      border: none;
      padding: 6px 12px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 13px;
      font-weight: 500;
      transition: background 0.18s;
    }

    .btn-map:hover {
      background: #2980b9;
    }

    .btn-gmaps {
      background: #fff;
      color: #1e40af;
      border: 1.5px solid #bfdbfe;
      padding: 5px 11px;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 500;
      text-decoration: none;
      transition: background 0.15s;
      display: inline-flex;
      align-items: center;
    }

    .btn-gmaps:hover {
      background: #eff6ff;
    }

    #feed-loading,
    #feed-end {
      text-align: center;
      padding: 10px;
      color: #777;
      font-size: 13px;
    }

    /* MAP MODAL */
    .map-modal {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.6);
      z-index: 9999;
      align-items: center;
      justify-content: center;
    }

    .map-modal-content {
      background: #fff;
      width: 60%;
      max-width: 700px;
      height: 450px;
      border-radius: 12px;
      padding: 10px;
      position: relative;
      display: flex;
      flex-direction: column;
    }

    #map {
      flex: 1;
      width: 100%;
      border-radius: 10px;
    }

    #close-map {
      position: absolute;
      right: 12px;
      top: 8px;
      font-size: 26px;
      cursor: pointer;
      color: #374151;
    }

    .full-map-modal {
      display: none;
      position: fixed;
      inset: 0;
      background: #fff;
      z-index: 10000;
    }

    .full-map-header {
      display: flex;
      justify-content: space-between;
      padding: 10px 16px;
      background: #0b1f47;
      color: white;
    }

    #full-map {
      width: 100%;
      height: calc(100% - 50px);
    }

    .close-full-map {
      cursor: pointer;
      font-size: 24px;
    }

    .btn-full-map {
      margin-top: 10px;
      background: #0b1f47;
      color: white;
      border: none;
      padding: 8px;
      border-radius: 8px;
      width: 100%;
      cursor: pointer;
      font-size: 13px;
    }

    @media (max-width: 768px) {
      .community-grid {
        grid-template-columns: 1fr;
      }

      .map-modal-content {
        width: 90%;
        height: 400px;
      }

      .comm-filter-bar {
        gap: 6px;
      }
    }
  </style>

  <header class="page-header">
    <h2 id="community-heading">Community</h2>
  </header>

  <div class="community-grid">

    <!-- LEFT: Announcements -->
    <aside class="announcements-sidebar" style="max-height:75vh; overflow-y:auto;">
      <h3 class="community-section-title">Announcements</h3>
      <?php include '../includes/fetch_commAnnouncement.php'; ?>
    </aside>

    <!-- RIGHT: Feed -->
    <div class="community-column">
      <h3 class="community-section-title">Community Posts</h3>

      <!-- ── FILTER STACK ── -->
      <div class="community-filter-stack">

        <!-- Date filter bar -->
        <div class="comm-filter-bar" id="comm-date-bar">
          <span class="comm-filter-label">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
              <line x1="16" y1="2" x2="16" y2="6" />
              <line x1="8" y1="2" x2="8" y2="6" />
              <line x1="3" y1="10" x2="21" y2="10" />
            </svg>
            Date
          </span>
          <button class="comm-filter-btn active" data-date-preset="all"
            style="background:#3b82f6;border-color:#2563eb;color:#fff;">All time</button>
          <button class="comm-filter-btn" data-date-preset="today">Today</button>
          <button class="comm-filter-btn" data-date-preset="7">Last 7 days</button>
          <button class="comm-filter-btn" data-date-preset="30">Last 30 days</button>
          <span class="comm-filter-sep">|</span>
          <div class="comm-date-wrap">
            <label for="comm-date-from">From</label>
            <input type="date" id="comm-date-from" />
            <label for="comm-date-to">to</label>
            <input type="date" id="comm-date-to" />
            <button class="comm-apply-btn" id="comm-date-apply">Apply</button>
            <button class="comm-clear-btn" id="comm-date-clear" style="display:none;">Clear</button>
          </div>
        </div>

        <!-- Active date pill -->
        <div class="comm-active-info" id="comm-date-info"></div>

        <!-- Status filter bar -->
        <div class="comm-filter-bar" id="comm-status-bar">
          <span class="comm-filter-label">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none"
              stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="12" r="10" />
              <line x1="12" y1="8" x2="12" y2="12" />
              <line x1="12" y1="16" x2="12.01" y2="16" />
            </svg>
            Status
          </span>
          <button class="comm-filter-btn active" data-status="all"
            style="background:#3b82f6;border-color:#2563eb;color:#fff;">
            <span style="font-size:14px;line-height:1;">⊞</span> All
          </button>
          <button class="comm-filter-btn" data-status="Rescue Needed">
            <span class="status-dot" style="background:#ef4444;"></span> Rescue Needed
          </button>
          <button class="comm-filter-btn" data-status="Being Rescued">
            <span class="status-dot" style="background:#eab308;"></span> Being Rescued
          </button>
          <button class="comm-filter-btn" data-status="Rescued">
            <span class="status-dot" style="background:#22c55e;"></span> Rescued
          </button>
          <button class="comm-filter-btn" data-status="Not Required">
            <span class="status-dot" style="background:#94a3b8;"></span> Not Required
          </button>
          <span id="comm-report-count"
            style="margin-left:auto;font-size:0.73rem;color:#94a3b8;font-weight:500;white-space:nowrap;"></span>
        </div>

      </div>
      <!-- ── END FILTER STACK ── -->

      <div id="comm-no-results">
        <p>No reports match the selected filters.</p>
      </div>

      <div id="feed-container"></div>
      <div id="feed-loading">Loading...</div>
      <div id="feed-end" style="display:none;">No more posts</div>
    </div>

  </div>

  <!-- MAP MODAL -->
  <div id="map-modal" class="map-modal">
    <div class="map-modal-content">
      <span id="close-map">&times;</span>
      <h3>Report Location</h3>
      <div id="map"></div>
      <button id="open-full-map" class="btn-full-map">Open Full Screen Map 🗺️</button>
    </div>
  </div>

  <!-- FULL MAP -->
  <div id="full-map-modal" class="full-map-modal">
    <div class="full-map-header">
      <h3>Flood Location Map</h3>
      <span class="close-full-map" id="close-full-map">&times;</span>
    </div>
    <div id="full-map"></div>
  </div>

  <!-- LEAFLET -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

  <script>
    (function () {

      /* ═══════════════════════════════════════════════
         FILTER STATE
      ═══════════════════════════════════════════════ */
      var activeDatePreset = 'all';
      var activeDateFrom = null;
      var activeDateTo = null;
      var activeStatus = 'all';

      /* ═══════════════════════════════════════════════
         DATE HELPERS
      ═══════════════════════════════════════════════ */
      function fmtDate(d) {
        if (!d) return '';
        var parts = d.split('-');
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return months[parseInt(parts[1]) - 1] + ' ' + parseInt(parts[2]) + ', ' + parts[0];
      }

      function cardPassesDate(card) {
        if (activeDatePreset === 'all' && !activeDateFrom && !activeDateTo) return true;
        var raw = card.getAttribute('data-created-at');
        if (!raw) return false;
        var cardDate = new Date(raw + 'T00:00:00');
        var today = new Date(); today.setHours(0, 0, 0, 0);

        if (activeDatePreset === 'today') return cardDate.getTime() === today.getTime();
        if (activeDatePreset === '7') {
          var c7 = new Date(today); c7.setDate(c7.getDate() - 6);
          return cardDate >= c7;
        }
        if (activeDatePreset === '30') {
          var c30 = new Date(today); c30.setDate(c30.getDate() - 29);
          return cardDate >= c30;
        }
        if (activeDateFrom && cardDate < new Date(activeDateFrom + 'T00:00:00')) return false;
        if (activeDateTo && cardDate > new Date(activeDateTo + 'T00:00:00')) return false;
        return true;
      }

      function cardPassesStatus(card) {
        if (activeStatus === 'all') return true;
        return card.getAttribute('data-rescue-status') === activeStatus;
      }

      function applyFilters() {
        var cards = document.querySelectorAll('#feed-container .post-card');
        var visible = 0;
        cards.forEach(function (card) {
          var show = cardPassesDate(card) && cardPassesStatus(card);
          card.style.display = show ? '' : 'none';
          if (show) visible++;
        });

        var noResults = document.getElementById('comm-no-results');
        noResults.style.display = visible === 0 && !hasMore ? 'block' : 'none';

        var countEl = document.getElementById('comm-report-count');
        if (countEl) countEl.textContent = visible + ' report' + (visible !== 1 ? 's' : '');
      }

      /* ═══════════════════════════════════════════════
         DATE BAR LOGIC
      ═══════════════════════════════════════════════ */
      var dateBar = document.getElementById('comm-date-bar');
      var dateInfo = document.getElementById('comm-date-info');

      function updateDateInfo() {
        if (activeDatePreset === 'all') { dateInfo.className = 'comm-active-info'; return; }
        var msg = '';
        if (activeDatePreset === 'today') msg = "Showing today's reports only";
        else if (activeDatePreset === '7') msg = 'Showing reports from the last 7 days';
        else if (activeDatePreset === '30') msg = 'Showing reports from the last 30 days';
        else if (activeDateFrom && activeDateTo) msg = fmtDate(activeDateFrom) + ' – ' + fmtDate(activeDateTo);
        else if (activeDateFrom) msg = 'From ' + fmtDate(activeDateFrom) + ' onwards';
        else if (activeDateTo) msg = 'Up to ' + fmtDate(activeDateTo);
        dateInfo.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg> ' + msg;
        dateInfo.className = 'comm-active-info show';
      }

      function setDatePresetUI(preset) {
        dateBar.querySelectorAll('[data-date-preset]').forEach(function (btn) {
          var isActive = btn.getAttribute('data-date-preset') === preset;
          btn.classList.toggle('active', isActive);
          btn.style.background = isActive ? '#3b82f6' : '#fff';
          btn.style.borderColor = isActive ? '#2563eb' : '#e2e8f0';
          btn.style.color = isActive ? '#fff' : '#475569';
        });
      }

      dateBar.querySelectorAll('[data-date-preset]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          activeDatePreset = this.getAttribute('data-date-preset');
          activeDateFrom = null; activeDateTo = null;
          document.getElementById('comm-date-from').value = '';
          document.getElementById('comm-date-to').value = '';
          document.getElementById('comm-date-clear').style.display = 'none';
          setDatePresetUI(activeDatePreset);
          updateDateInfo();
          applyFilters();
        });
      });

      document.getElementById('comm-date-apply').addEventListener('click', function () {
        var from = document.getElementById('comm-date-from').value;
        var to = document.getElementById('comm-date-to').value;
        if (!from && !to) return;
        activeDatePreset = 'custom';
        activeDateFrom = from || null;
        activeDateTo = to || null;
        setDatePresetUI('');
        document.getElementById('comm-date-clear').style.display = '';
        updateDateInfo();
        applyFilters();
      });

      document.getElementById('comm-date-clear').addEventListener('click', function () {
        activeDatePreset = 'all'; activeDateFrom = null; activeDateTo = null;
        document.getElementById('comm-date-from').value = '';
        document.getElementById('comm-date-to').value = '';
        this.style.display = 'none';
        setDatePresetUI('all');
        updateDateInfo();
        applyFilters();
      });

      /* ═══════════════════════════════════════════════
         STATUS BAR LOGIC
      ═══════════════════════════════════════════════ */
      var statusColors = {
        'all': { bg: '#3b82f6', border: '#2563eb' },
        'Rescue Needed': { bg: '#ef4444', border: '#dc2626' },
        'Being Rescued': { bg: '#eab308', border: '#ca8a04' },
        'Rescued': { bg: '#22c55e', border: '#16a34a' },
        'Not Required': { bg: '#94a3b8', border: '#64748b' },
      };

      var statusBar = document.getElementById('comm-status-bar');

      function setStatusUI(status) {
        statusBar.querySelectorAll('[data-status]').forEach(function (btn) {
          var isActive = btn.getAttribute('data-status') === status;
          btn.classList.toggle('active', isActive);
          var colors = statusColors[btn.getAttribute('data-status')] || statusColors['all'];
          btn.style.background = isActive ? colors.bg : '#fff';
          btn.style.borderColor = isActive ? colors.border : '#e2e8f0';
          btn.style.color = isActive ? '#fff' : '#475569';
          var dot = btn.querySelector('.status-dot');
          if (dot) dot.style.background = isActive ? 'rgba(255,255,255,0.85)' : colors.bg;
        });
      }

      statusBar.querySelectorAll('[data-status]').forEach(function (btn) {
        btn.addEventListener('click', function () {
          activeStatus = this.getAttribute('data-status');
          setStatusUI(activeStatus);
          /* Reset + reload feed so server also filters */
          feedPage = 1; hasMore = true; loading = false;
          feed.innerHTML = '';
          loadingEl.style.display = 'block';
          endEl.style.display = 'none';
          loadFeed();
        });
      });

      /* ═══════════════════════════════════════════════
         INFINITE SCROLL FEED
      ═══════════════════════════════════════════════ */
      var feedPage = 1;
      var loading = false;
      var hasMore = true;
      var feed = document.getElementById('feed-container');
      var loadingEl = document.getElementById('feed-loading');
      var endEl = document.getElementById('feed-end');

      function loadFeed() {
        if (loading || !hasMore) return;
        loading = true;

        var url = '../includes/fetch_communityReports.php?page=' + feedPage;
        if (activeStatus !== 'all') url += '&status=' + encodeURIComponent(activeStatus);

        fetch(url)
          .then(function (r) { return r.text(); })
          .then(function (html) {
            if (!html.trim()) {
              hasMore = false;
              loadingEl.style.display = 'none';
              endEl.style.display = 'block';
              applyFilters();
              return;
            }
            feed.insertAdjacentHTML('beforeend', html);
            feedPage++;
            loading = false;
            applyFilters();
          });
      }

      loadFeed();

      new IntersectionObserver(
        function (e) { if (e[0].isIntersecting) loadFeed(); },
        { rootMargin: '200px' }
      ).observe(loadingEl);

      /* ═══════════════════════════════════════════════
         MAP
      ═══════════════════════════════════════════════ */
      var mapInstance = null, fullMapInstance = null;
      var lastLat, lastLng, lastName;

      document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-map');
        if (!btn) return;
        lastLat = parseFloat(btn.dataset.lat);
        lastLng = parseFloat(btn.dataset.lng);
        lastName = btn.dataset.name;
        document.getElementById('map-modal').style.display = 'flex';
        setTimeout(function () {
          if (mapInstance) mapInstance.remove();
          mapInstance = L.map('map').setView([lastLat, lastLng], 15);
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            { attribution: '&copy; OpenStreetMap' }).addTo(mapInstance);
          L.marker([lastLat, lastLng]).addTo(mapInstance).bindPopup(lastName).openPopup();
        }, 150);
      });

      document.getElementById('open-full-map').onclick = function () {
        document.getElementById('map-modal').style.display = 'none';
        document.getElementById('full-map-modal').style.display = 'block';
        setTimeout(function () {
          if (fullMapInstance) fullMapInstance.remove();
          fullMapInstance = L.map('full-map').setView([lastLat, lastLng], 15);
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            { attribution: '&copy; OpenStreetMap' }).addTo(fullMapInstance);
          L.marker([lastLat, lastLng]).addTo(fullMapInstance).bindPopup(lastName).openPopup();
        }, 150);
      };

      document.getElementById('close-map').onclick = function () {
        document.getElementById('map-modal').style.display = 'none';
      };
      document.getElementById('close-full-map').onclick = function () {
        document.getElementById('full-map-modal').style.display = 'none';
      };

      /* ═══════════════════════════════════════════════
         INITIAL UI STATE
      ═══════════════════════════════════════════════ */
      setStatusUI('all');

    })();
  </script>

</section>