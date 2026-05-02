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

    .post-card__image-wrap {
      margin-bottom: 10px;
    }

    .post-card__image {
      width: 100%;
      border-radius: 10px;
      max-height: 260px;
      object-fit: cover;
    }

    .post-card__description {
      font-size: 14px;
      line-height: 1.6;
      color: #1e293b;
      margin-bottom: 10px;
    }

    .post-card__rescue-info {
      background: #fff8f0;
      border: 1px solid #fde68a;
      border-left: 4px solid #f59e0b;
      border-radius: 8px;
      padding: 9px 12px;
      margin-bottom: 10px;
      font-size: 13px;
      color: #78350f;
    }

    .rescue-info-item {
      font-weight: 600;
      display: block;
      margin-bottom: 4px;
    }

    .rescue-info-desc {
      margin: 0;
      font-size: 12px;
      color: #92400e;
      line-height: 1.5;
    }

    .post-card__tags {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
    }

    .post-tag {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-size: 12px;
      padding: 3px 10px;
      border-radius: 99px;
      font-weight: 500;
    }

    .post-tag--water {
      background: #dbeafe;
      color: #1d4ed8;
    }

    .severity--impassable {
      background: #fee2e2;
      color: #991b1b;
    }

    .severity--limited {
      background: #fef3c7;
      color: #92400e;
    }

    .severity--passable {
      background: #dcfce7;
      color: #166534;
    }

    .severity--neutral {
      background: #f1f5f9;
      color: #475569;
    }

    /* FOOTER */
    .post-card__footer {
      margin-top: 14px;
      padding-top: 12px;
      border-top: 1px solid #f0f4ff;
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
    }

    /* RESCUE BADGES */
    .rescue-badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 5px 14px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
    }

    .rescue-badge--btn {
      border: none;
      cursor: pointer;
      transition: filter 0.15s, transform 0.12s;
    }

    .rescue-badge--btn:hover {
      filter: brightness(1.1);
      transform: scale(1.03);
    }

    .rescue-badge--btn:active {
      transform: scale(0.97);
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

    /* MAP BUTTON */
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
      margin-left: auto;
    }

    .btn-map:hover {
      background: #2980b9;
    }

    /* FEED */
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

    /* =============================================
       CONFIRMATION MODAL  (shared shell)
    ============================================= */
    .confirm-backdrop {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.55);
      z-index: 10100;
      align-items: center;
      justify-content: center;
      padding: 16px;
    }

    .confirm-backdrop.open {
      display: flex;
    }

    .confirm-modal {
      background: #fff;
      border-radius: 16px;
      width: min(420px, 100%);
      box-shadow: 0 20px 50px rgba(15, 23, 42, 0.22);
      overflow: hidden;
      animation: modalIn 0.2s ease;
    }

    @keyframes modalIn {
      from {
        opacity: 0;
        transform: translateY(12px) scale(0.97);
      }

      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    .confirm-modal__header {
      padding: 18px 22px 16px;
      display: flex;
      align-items: flex-start;
      gap: 14px;
    }

    /* Colour variants set by JS */
    .confirm-modal__header.type--start {
      background: #fff1f2;
      border-bottom: 1px solid #fecdd3;
    }

    .confirm-modal__header.type--finish {
      background: #f0fdf4;
      border-bottom: 1px solid #bbf7d0;
    }

    .confirm-modal__icon {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      flex-shrink: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
    }

    .type--start .confirm-modal__icon {
      background: #fee2e2;
    }

    .type--finish .confirm-modal__icon {
      background: #dcfce7;
    }

    .confirm-modal__header-text h3 {
      margin: 0 0 3px;
      font-size: 1rem;
      font-weight: 700;
    }

    .type--start .confirm-modal__header-text h3 {
      color: #991b1b;
    }

    .type--finish .confirm-modal__header-text h3 {
      color: #166534;
    }

    .confirm-modal__header-text p {
      margin: 0;
      font-size: 0.78rem;
      color: #64748b;
    }

    .confirm-modal__body {
      padding: 18px 22px;
    }

    .confirm-modal__body p {
      font-size: 14px;
      color: #1e293b;
      line-height: 1.6;
      margin: 0;
    }

    .confirm-modal__body strong {
      color: #0f1f40;
    }

    .confirm-modal__footer {
      padding: 12px 22px 18px;
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      border-top: 1px solid #f0f4ff;
    }

    .btn-modal-cancel {
      padding: 9px 20px;
      border-radius: 9px;
      border: 1.5px solid #e2e8f0;
      background: #fff;
      color: #475569;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
    }

    .btn-modal-cancel:hover {
      background: #f8fafc;
    }

    .btn-modal-confirm {
      padding: 9px 22px;
      border-radius: 9px;
      border: none;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      min-width: 140px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      transition: filter 0.15s, transform 0.12s;
      color: #fff;
    }

    .btn-modal-confirm.type--start {
      background: #dc2626;
    }

    .btn-modal-confirm.type--finish {
      background: #16a34a;
    }

    .btn-modal-confirm:hover {
      filter: brightness(1.08);
    }

    .btn-modal-confirm:active {
      transform: scale(0.97);
    }

    .btn-modal-confirm:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      filter: none;
    }

    /* TOAST */
    .rescue-toast {
      position: fixed;
      bottom: 24px;
      right: 24px;
      background: #0f1f40;
      color: #fff;
      padding: 12px 20px;
      border-radius: 10px;
      font-size: 13px;
      font-weight: 500;
      z-index: 10200;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
      display: flex;
      align-items: center;
      gap: 8px;
      opacity: 0;
      transform: translateY(10px);
      transition: opacity 0.25s, transform 0.25s;
      pointer-events: none;
    }

    .rescue-toast.show {
      opacity: 1;
      transform: translateY(0);
    }

    .rescue-toast.toast--success {
      border-left: 4px solid #22c55e;
    }

    .rescue-toast.toast--error {
      border-left: 4px solid #ef4444;
    }

    @media (max-width: 768px) {
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

    <!-- LEFT: Announcements -->
    <aside class="announcements-sidebar" style="max-height:75vh; overflow-y:auto;">
      <h3 class="community-section-title">Announcements</h3>
      <?php include '../includes/fetch_commAnnouncement.php'; ?>
    </aside>

    <!-- RIGHT: Feed -->
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

  <!-- =============================================
       CONFIRMATION MODAL
       One modal, content swapped by JS per type
  ============================================= -->
  <div id="confirm-backdrop" class="confirm-backdrop" role="dialog" aria-modal="true"
    aria-labelledby="confirm-modal-title">
    <div class="confirm-modal">

      <div class="confirm-modal__header" id="confirm-modal-header">
        <div class="confirm-modal__icon" id="confirm-modal-icon"></div>
        <div class="confirm-modal__header-text">
          <h3 id="confirm-modal-title"></h3>
          <p id="confirm-modal-subtitle"></p>
        </div>
      </div>

      <div class="confirm-modal__body">
        <p id="confirm-modal-body"></p>
      </div>

      <div class="confirm-modal__footer">
        <button type="button" class="btn-modal-cancel" id="confirm-cancel">Cancel</button>
        <button type="button" class="btn-modal-confirm" id="confirm-ok"></button>
      </div>

    </div>
  </div>

  <!-- TOAST -->
  <div id="rescue-toast" class="rescue-toast" role="alert" aria-live="polite"></div>

  <!-- LEAFLET -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

  <script>
    (function () {

      /* ─── FEED ─── */
      let feedPage = 1, loading = false, hasMore = true;
      const feed = document.getElementById('feed-container');
      const loadingEl = document.getElementById('feed-loading');
      const endEl = document.getElementById('feed-end');

      function loadFeed() {
        if (loading || !hasMore) return;
        loading = true;
        fetch(`../includes/fetch_rescuerReports.php?page=${feedPage}`)
          .then(r => r.text())
          .then(html => {
            if (!html.trim()) {
              hasMore = false;
              loadingEl.style.display = 'none';
              endEl.style.display = 'block';
              return;
            }
            feed.insertAdjacentHTML('beforeend', html);
            feedPage++;
            loading = false;
          });
      }

      loadFeed();
      new IntersectionObserver(
        e => { if (e[0].isIntersecting) loadFeed(); },
        { rootMargin: '200px' }
      ).observe(loadingEl);

      /* ─── MAP ─── */
      let mapInstance = null, fullMapInstance = null;
      let lastLat, lastLng, lastName;

      document.addEventListener('click', e => {
        const btn = e.target.closest('.btn-map');
        if (!btn) return;
        lastLat = parseFloat(btn.dataset.lat);
        lastLng = parseFloat(btn.dataset.lng);
        lastName = btn.dataset.name;
        document.getElementById('map-modal').style.display = 'flex';
        setTimeout(() => {
          if (mapInstance) mapInstance.remove();
          mapInstance = L.map('map').setView([lastLat, lastLng], 15);
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            { attribution: '&copy; OpenStreetMap' }).addTo(mapInstance);
          L.marker([lastLat, lastLng]).addTo(mapInstance).bindPopup(lastName).openPopup();
        }, 150);
      });

      document.getElementById('open-full-map').onclick = () => {
        document.getElementById('map-modal').style.display = 'none';
        document.getElementById('full-map-modal').style.display = 'block';
        setTimeout(() => {
          if (fullMapInstance) fullMapInstance.remove();
          fullMapInstance = L.map('full-map').setView([lastLat, lastLng], 15);
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            { attribution: '&copy; OpenStreetMap' }).addTo(fullMapInstance);
          L.marker([lastLat, lastLng]).addTo(fullMapInstance).bindPopup(lastName).openPopup();
        }, 150);
      };

      document.getElementById('close-map').onclick = () => { document.getElementById('map-modal').style.display = 'none'; };
      document.getElementById('close-full-map').onclick = () => { document.getElementById('full-map-modal').style.display = 'none'; };

      /* ─── RESCUE CONFIRMATION MODAL ─── */

      /*
       * Modal content per type:
       *
       *  type = 'start'  → "Rescue Needed" → next: "Being Rescued" (status_id 3)
       *  type = 'finish' → "Being Rescued" → next: "Rescued"       (status_id 4)
       */
      const MODAL_CONTENT = {
        start: {
          icon: '🚨',
          title: 'Start Rescue Operation',
          subtitle: 'Rescue Needed → Being Rescued',
          bodyFn: reporter => `Are you sure you want to mark <strong>${reporter}</strong>'s report as <strong>Being Rescued</strong>? This means a rescue team is now on the way.`,
          btnLabel: '🚑 Yes, Start Rescue',
          btnClass: 'type--start',
          headerClass: 'type--start',
        },
        finish: {
          icon: '✅',
          title: 'Complete Rescue',
          subtitle: 'Being Rescued → Rescued',
          bodyFn: reporter => `Confirm that <strong>${reporter}</strong> has been successfully <strong>Rescued</strong>. This action cannot be undone.`,
          btnLabel: '✅ Yes, Mark as Rescued',
          btnClass: 'type--finish',
          headerClass: 'type--finish',
        }
      };

      const backdrop = document.getElementById('confirm-backdrop');
      const header = document.getElementById('confirm-modal-header');
      const iconEl = document.getElementById('confirm-modal-icon');
      const titleEl = document.getElementById('confirm-modal-title');
      const subtitleEl = document.getElementById('confirm-modal-subtitle');
      const bodyEl = document.getElementById('confirm-modal-body');
      const cancelBtn = document.getElementById('confirm-cancel');
      const okBtn = document.getElementById('confirm-ok');
      const toast = document.getElementById('rescue-toast');

      let activeReportId = null;
      let activeNextId = null;
      let activeBadgeBtn = null;

      /* Open modal when a clickable badge is clicked */
      document.addEventListener('click', e => {
        const badge = e.target.closest('.rescue-badge--btn');
        if (!badge) return;

        const modalType = badge.dataset.modalType;
        const content = MODAL_CONTENT[modalType];
        if (!content) return;

        activeReportId = badge.dataset.reportId;
        activeNextId = badge.dataset.nextStatusId;
        activeBadgeBtn = badge;

        /* Populate modal */
        header.className = `confirm-modal__header ${content.headerClass}`;
        iconEl.textContent = content.icon;
        titleEl.textContent = content.title;
        subtitleEl.textContent = content.subtitle;
        bodyEl.innerHTML = content.bodyFn(badge.dataset.reporter);
        okBtn.textContent = content.btnLabel;
        okBtn.className = `btn-modal-confirm ${content.btnClass}`;
        okBtn.disabled = false;

        backdrop.classList.add('open');
      });

      function closeModal() {
        backdrop.classList.remove('open');
        activeReportId = activeNextId = activeBadgeBtn = null;
      }

      cancelBtn.onclick = closeModal;
      backdrop.addEventListener('click', e => { if (e.target === backdrop) closeModal(); });
      document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

      /* Confirm → POST → update badge in DOM */
      okBtn.onclick = function () {
        okBtn.disabled = true;
        okBtn.textContent = 'Updating…';

        const body = new FormData();
        body.append('report_id', activeReportId);
        body.append('new_status_id', activeNextId);

        fetch('../api/update_rescue_status.php', { method: 'POST', body })
          .then(r => r.json())
          .then(data => {
            if (!data.success) throw new Error(data.message || 'Failed');

            const newStatus = data.status_name;   // e.g. "Being Rescued" or "Rescued"
            const newId = parseInt(data.status_id);

            /* Badge class map */
            const classMap = {
              'Rescue Needed': 'badge--danger',
              'Being Rescued': 'badge--warning',
              'Rescued': 'badge--success',
              'Not Required': 'badge--neutral',
            };

            /* Next step map — what the badge should advance to next */
            const nextMap = {
              'Being Rescued': { nextId: 4, modalType: 'finish' },
            };

            const stillClickable = nextMap[newStatus] !== undefined;

            if (stillClickable) {
              /* Keep as button, update data attrs + text + class */
              const next = nextMap[newStatus];
              activeBadgeBtn.classList.remove('badge--danger', 'badge--warning', 'badge--success', 'badge--neutral');
              activeBadgeBtn.classList.add(classMap[newStatus]);
              activeBadgeBtn.textContent = newStatus;
              activeBadgeBtn.dataset.nextStatusId = next.nextId;
              activeBadgeBtn.dataset.modalType = next.modalType;
            } else {
              /* Convert button → static span */
              const span = document.createElement('span');
              span.className = `rescue-badge ${classMap[newStatus] || 'badge--neutral'}`;
              span.textContent = newStatus;
              activeBadgeBtn.replaceWith(span);
            }

            showToast('✅ Status updated to: ' + newStatus, 'success');
            closeModal();
          })
          .catch(err => {
            showToast('❌ ' + err.message, 'error');
            okBtn.disabled = false;
            okBtn.textContent = MODAL_CONTENT[
              activeBadgeBtn ? activeBadgeBtn.dataset.modalType : 'start'
            ].btnLabel;
          });
      };

      /* ─── TOAST ─── */
      let toastTimer = null;
      function showToast(msg, type = 'success') {
        toast.textContent = msg;
        toast.className = `rescue-toast toast--${type} show`;
        if (toastTimer) clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.remove('show'), 3500);
      }

    })();
  </script>

</section>