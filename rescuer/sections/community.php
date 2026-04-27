<section id="page-community" class="page active" aria-labelledby="community-heading">

  <style>
    /* =========================
       COMMUNITY GRID
    ========================= */
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

    /* =========================
       POST CARD
    ========================= */
    .post-card {
      background: #fff;
      border-radius: 12px;
      padding: 15px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
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
    }

    .post-card__name {
      font-weight: 600;
      font-size: 14px;
    }

    .post-card__meta {
      font-size: 12px;
      color: #777;
    }

    .post-card__image {
      width: 100%;
      border-radius: 10px;
      margin-top: 10px;
    }

    .post-card__description {
      font-size: 14px;
      line-height: 1.5;
    }

    /* =========================
       MAP BUTTON
    ========================= */
    .btn-map {
      background: #3498db;
      color: white;
      border: none;
      padding: 5px 10px;
      border-radius: 6px;
      cursor: pointer;
      margin-left: auto;
    }

    .btn-map:hover {
      background: #2980b9;
    }

    /* =========================
       FEED
    ========================= */
    #feed-loading,
    #feed-end {
      text-align: center;
      padding: 10px;
      color: #777;
    }

    /* =========================
       FOOTER FIX (LGU STYLE)
    ========================= */
    .post-card__footer {
      margin-top: 12px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
    }

    .post-card__footer-left {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .post-card__footer-right {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    /* =========================
       BADGES
    ========================= */
    .badge--danger {
      background: #e74c3c;
      color: #fff;
      padding: 5px 10px;
      border-radius: 20px;
    }

    .badge--warning {
      background: #f39c12;
      color: #fff;
      padding: 5px 10px;
      border-radius: 20px;
    }

    .badge--success {
      background: #2ecc71;
      color: #fff;
      padding: 5px 10px;
      border-radius: 20px;
    }

    .badge--neutral {
      background: #bdc3c7;
      color: #2c3e50;
      padding: 5px 10px;
      border-radius: 20px;
    }

    /* =========================
       MAP MODAL (SMALL FIXED)
    ========================= */
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
    }

    /* FULL MAP */
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
      padding: 10px;
      background: #2c3e50;
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
      background: #2c3e50;
      color: white;
      border: none;
      padding: 8px;
      border-radius: 8px;
      width: 100%;
      cursor: pointer;
    }

    /* RESPONSIVE */
    @media (max-width:768px) {
      .community-grid {
        grid-template-columns: 1fr;
      }

      .map-modal-content {
        width: 90%;
        height: 400px;
      }
    }
  </style>

  <header class="page-header">
    <h2 id="community-heading">Community</h2>
  </header>

  <div class="community-grid">

    <!-- LEFT -->
    <aside class="announcements-sidebar" style="max-height:75vh; overflow-y:auto;">
      <h3 class="community-section-title">Announcements</h3>
      <?php include '../includes/fetch_commAnnouncement.php'; ?>
    </aside>

    <!-- RIGHT -->
    <div class="community-column">

      <h3 class="community-section-title">Community Posts</h3>

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
      <button id="open-full-map" class="btn-full-map">
        Open Full Screen Map 🗺️
      </button>
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
    let page = 1;
    let loading = false;
    let hasMore = true;

    const feed = document.getElementById("feed-container");
    const loadingEl = document.getElementById("feed-loading");
    const endEl = document.getElementById("feed-end");

    let mapInstance = null;
    let fullMapInstance = null;

    let lastLat, lastLng, lastName;

    function loadFeed() {
      if (loading || !hasMore) return;

      loading = true;

      fetch(`../includes/fetch_communityReports.php?page=${page}`)
        .then(res => res.text())
        .then(data => {

          if (data.trim() === "") {
            hasMore = false;
            loadingEl.style.display = "none";
            endEl.style.display = "block";
            return;
          }

          feed.insertAdjacentHTML("beforeend", data);

          page++;
          loading = false;
        });
    }

    loadFeed();

    const observer = new IntersectionObserver(entries => {
      if (entries[0].isIntersecting) loadFeed();
    }, { rootMargin: "200px" });

    observer.observe(loadingEl);

    document.addEventListener("click", function (e) {

      if (e.target.classList.contains("btn-map")) {

        lastLat = parseFloat(e.target.dataset.lat);
        lastLng = parseFloat(e.target.dataset.lng);
        lastName = e.target.dataset.name;

        document.getElementById("map-modal").style.display = "flex";

        setTimeout(() => {

          if (mapInstance) mapInstance.remove();

          mapInstance = L.map('map').setView([lastLat, lastLng], 15);

          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
          }).addTo(mapInstance);

          L.marker([lastLat, lastLng])
            .addTo(mapInstance)
            .bindPopup(lastName)
            .openPopup();

        }, 150);
      }
    });

    document.getElementById("open-full-map").onclick = function () {

      document.getElementById("map-modal").style.display = "none";
      document.getElementById("full-map-modal").style.display = "block";

      setTimeout(() => {

        if (fullMapInstance) fullMapInstance.remove();

        fullMapInstance = L.map('full-map').setView([lastLat, lastLng], 15);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          attribution: '&copy; OpenStreetMap'
        }).addTo(fullMapInstance);

        L.marker([lastLat, lastLng])
          .addTo(fullMapInstance)
          .bindPopup(lastName)
          .openPopup();

      }, 150);
    };

    document.getElementById("close-map").onclick = () => {
      document.getElementById("map-modal").style.display = "none";
    };

    document.getElementById("close-full-map").onclick = () => {
      document.getElementById("full-map-modal").style.display = "none";
    };
  </script>

</section>