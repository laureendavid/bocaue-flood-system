/* ===================================================================
   rescuer.js — Rescuer Dashboard
   Navigation is handled server-side via main.php?page=...
   This file handles: sidebar toggle, profile dropdown, search,
   and dynamic data rendering functions.
   =================================================================== */

/* ===========================================================
   SIDEBAR TOGGLE
   =========================================================== */
function openSidebar() {
  const sidebar = document.getElementById("sidebar");
  const overlay = document.getElementById("sidebar-overlay");
  if (!sidebar || !overlay) return;
  sidebar.classList.add("open");
  overlay.classList.add("visible");
  document.body.style.overflow = "hidden";
}

function closeSidebar() {
  const sidebar = document.getElementById("sidebar");
  const overlay = document.getElementById("sidebar-overlay");
  if (!sidebar || !overlay) return;
  sidebar.classList.remove("open");
  overlay.classList.remove("visible");
  document.body.style.overflow = "";
}

/* ===========================================================
   BADGE HELPERS
   =========================================================== */
function getOccupancyBadge(current, max) {
  const pct = max > 0 ? (current / max) * 100 : 0;
  if (pct >= 100) return { cls: "badge-full", label: "Full" };
  if (pct >= 75) return { cls: "badge-nearfull", label: "Near Full" };
  return { cls: "badge-available", label: "Available" };
}

function getProgressBarClass(current, max) {
  const pct = max > 0 ? (current / max) * 100 : 0;
  if (pct >= 100) return "full";
  if (pct >= 75) return "nearfull";
  return "available";
}

/* ===========================================================
   DASHBOARD — RESCUE STATS
   =========================================================== */
function renderDashboardStats(data) {
  // data = { needing: 0, inProgress: 0, rescued: 0, myOngoing: 0, myRescued: 0 }
  const set = (id, val) => {
    const el = document.getElementById(id);
    if (el) el.textContent = val ?? "0";
  };
  set("stat-needing", data.needing ?? 0);
  set("stat-inprogress", data.inProgress ?? 0);
  set("stat-rescued", data.rescued ?? 0);
  set("stat-my-ongoing", data.myOngoing ?? 0);
  set("stat-my-rescued", data.myRescued ?? 0);
}

/* ===========================================================
   DASHBOARD — HOTLINES LIST
   =========================================================== */
function renderDashboardHotlines(hotlines) {
  // hotlines = [{ barangay: "Wakas", entries: [{ name: "PNP", number: "09..." }] }]
  const list = document.getElementById("dash-hotlines-list");
  if (!list) return;
  if (!hotlines || hotlines.length === 0) {
    list.innerHTML =
      '<li class="empty-state-inline">No hotlines available.</li>';
    return;
  }
  list.innerHTML = hotlines
    .map(
      (district) => `
    <li class="hotline-district">
      <h4 class="hotline-district-name">${escHtml(district.barangay)}</h4>
      ${(district.entries || [])
        .map(
          (e) => `
        <div class="hotline-row">
          <span>${escHtml(e.name)}</span>
          <span>${escHtml(e.number)}</span>
        </div>
      `,
        )
        .join("")}
    </li>
  `,
    )
    .join("");
}

/* ===========================================================
   DASHBOARD — EVACUATION CENTERS LIST
   =========================================================== */
function renderDashboardEvacCenters(centers) {
  // centers = [{ name: "Center 1", current: 85, max: 100 }]
  const list = document.getElementById("dash-evac-list");
  if (!list) return;
  if (!centers || centers.length === 0) {
    list.innerHTML =
      '<li class="empty-state-inline">No evacuation centers available.</li>';
    return;
  }
  list.innerHTML = centers
    .map((c) => {
      const pct = c.max > 0 ? Math.min((c.current / c.max) * 100, 100) : 0;
      const badge = getOccupancyBadge(c.current, c.max);
      const barCls = getProgressBarClass(c.current, c.max);
      return `
      <li class="evac-progress-item">
        <div class="evac-progress-header">
          <span class="evac-center-name">${escHtml(c.name)}</span>
          <span class="badge ${badge.cls}">${badge.label}</span>
        </div>
        <div class="progress-bar-wrap">
          <div class="progress-bar ${barCls}" style="width:${pct.toFixed(1)}%"></div>
        </div>
      </li>
    `;
    })
    .join("");
}

/* ===========================================================
   FLOOD MONITORING MAP
   =========================================================== */
function renderFloodMap(data) {
  // data = { iframeUrl: "https://..." }
  const container = document.getElementById("flood-map-placeholder");
  if (container && data?.iframeUrl) {
    container.outerHTML = `
      <iframe
        class="flood-map-iframe"
        src="${escHtml(data.iframeUrl)}"
        title="Flood Monitoring Map – Bocaue, Bulacan"
        loading="lazy"
      ></iframe>
    `;
  }
}

/* ===========================================================
   EVACUATION CENTER TABLE
   =========================================================== */
function renderEvacTable(centers) {
  // centers = [{ name: "Lolomboy Center", address: "Barrio 1st, Lolomboy", current: 50, max: 100 }]
  const tbody = document.getElementById("evac-table-body");
  if (!tbody) return;
  if (!centers || centers.length === 0) {
    tbody.innerHTML =
      '<tr class="empty-row"><td colspan="4">No evacuation centers available.</td></tr>';
    return;
  }
  tbody.innerHTML = centers
    .map((c) => {
      const badge = getOccupancyBadge(c.current, c.max);
      return `
      <tr>
        <td>
          <p class="evac-center-name-main">${escHtml(c.name)}</p>
          <p class="evac-center-address">${escHtml(c.address || "")}</p>
        </td>
        <td>${c.current}/${c.max}</td>
        <td><span class="badge ${badge.cls}">${badge.label}</span></td>
        <td class="col-center">
          <button class="btn-view" aria-label="View ${escHtml(c.name)}">
            <span class="material-symbols-outlined">visibility</span>
          </button>
        </td>
      </tr>
    `;
    })
    .join("");
}

/* ===========================================================
   HOTLINES TABLE
   =========================================================== */
function renderHotlinesTable(hotlines) {
  // hotlines = [{ barangay: "Wakas", name: "PNP", number: "922-423-5245" }]
  const tbody = document.getElementById("hotlines-table-body");
  if (!tbody) return;
  if (!hotlines || hotlines.length === 0) {
    tbody.innerHTML =
      '<tr class="empty-row"><td colspan="4">No hotlines available.</td></tr>';
    return;
  }
  tbody.innerHTML = hotlines
    .map(
      (h) => `
    <tr>
      <td>${escHtml(h.barangay)}</td>
      <td>${escHtml(h.name)}</td>
      <td class="font-mono">${escHtml(h.number)}</td>
      <td class="col-center">
        <button class="btn-call" aria-label="Call ${escHtml(h.barangay)} ${escHtml(h.name)}"
                onclick="window.location.href='tel:${escHtml(h.number.replace(/\D/g, ""))}'">
          <span class="material-symbols-outlined">call</span>
        </button>
      </td>
    </tr>
  `,
    )
    .join("");
}

/* ===========================================================
   COMMUNITY — ANNOUNCEMENTS
   =========================================================== */
function renderAnnouncements(announcements) {
  // announcements = [{ title, issuedBy, body, datetime, displayDate, isWarning }]
  const container = document.getElementById("announce-list");
  if (!container) return;
  if (!announcements || announcements.length === 0) {
    container.innerHTML =
      '<p class="community-no-announce">No announcements to display.</p>';
    return;
  }
  container.innerHTML = announcements
    .map(
      (a) => `
    <article class="announce-card${a.isWarning ? " announce-card--warning" : ""}">
      <div class="announce-card-top">
        <h4 class="announce-title">${escHtml(a.title)}</h4>
        <div class="announce-issuer">
          <p class="announce-issuer-label">Issued by:</p>
          <p class="announce-issuer-name">${escHtml(a.issuedBy)}</p>
        </div>
      </div>
      <p class="announce-body">${escHtml(a.body)}</p>
      <footer class="announce-footer">
        <time datetime="${escHtml(a.datetime)}">${escHtml(a.displayDate)}</time>
      </footer>
    </article>
  `,
    )
    .join("");
}

/* ===========================================================
   COMMUNITY — POSTS FEED
   =========================================================== */
function renderReportsFeed(reports) {
  /* reports = [{
       userName, userAddress, userAvatar,
       datetime, displayDate,
       imageUrl, waterLevel,
       description, severity, severityLevel,  // "red"|""
       rescueStatus  // "needed" | "inprogress" | "rescued"
     }] */
  const feed = document.getElementById("reports-feed");
  if (!feed) return;
  if (!reports || reports.length === 0) {
    feed.innerHTML =
      '<article class="post-card post-card--empty">No community posts to display.</article>';
    return;
  }

  const rescueBtnMap = {
    needed: { cls: "rescue-btn--needed", label: "Rescue Needed" },
    inprogress: { cls: "rescue-btn--inprogress", label: "In Progress" },
    rescued: { cls: "rescue-btn--rescued", label: "Rescued" },
  };

  feed.innerHTML = reports
    .map((r, i) => {
      const btn = rescueBtnMap[r.rescueStatus] || rescueBtnMap.needed;
      const isRescued = r.rescueStatus === "rescued";
      const avatarHtml = r.userAvatar
        ? `<img src="${escHtml(r.userAvatar)}" alt="${escHtml(r.userName)}" />`
        : `<span class="material-symbols-outlined post-avatar-placeholder">person</span>`;
      const imgHtml = r.imageUrl
        ? `<img src="${escHtml(r.imageUrl)}" alt="Flood situation" class="post-img${isRescued ? " post-img--greyscale" : ""}" />`
        : "";
      const waterIconCls = isRescued ? " post-water-icon--green" : "";

      return `
      <article class="post-card${isRescued ? " post-card--rescued" : ""}" aria-labelledby="post-${i}-name">
        <header class="post-card-header">
          <div class="post-user-info">
            <div class="post-avatar" aria-hidden="true">${avatarHtml}</div>
            <div class="post-user-meta">
              <h4 id="post-${i}-name" class="post-user-name">${escHtml(r.userName)}</h4>
              <address class="post-user-address">${escHtml(r.userAddress)}</address>
            </div>
          </div>
          <time class="post-time" datetime="${escHtml(r.datetime)}">${escHtml(r.displayDate)}</time>
        </header>
        <div class="post-card-body">
          <div class="post-image-col">
            ${imgHtml}
            <div class="post-water-level">
              <span class="material-symbols-outlined post-water-icon${waterIconCls}">water_drop</span>
              <span>Water Level: <strong>${escHtml(r.waterLevel)}</strong></span>
            </div>
          </div>
          <div class="post-text-col">
            <p class="post-description">${escHtml(r.description)}</p>
            <div class="post-card-footer">
              <p class="post-severity">
                <span class="severity-label">Flood Severity:</span>
                <span class="severity-value${r.severityLevel === "red" ? " severity-value--red" : ""}">${escHtml(r.severity)}</span>
              </p>
              <button class="rescue-btn ${btn.cls}">${btn.label}</button>
            </div>
          </div>
        </div>
      </article>
    `;
    })
    .join("");
}

/* ===========================================================
   SEARCH FILTERING
   =========================================================== */
function filterTable(tbodyId, query) {
  const tbody = document.getElementById(tbodyId);
  if (!tbody) return;
  const q = query.toLowerCase();
  tbody.querySelectorAll("tr:not(.empty-row)").forEach((row) => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? "" : "none";
  });
}

/* ===========================================================
   UTILITY — HTML ESCAPE
   =========================================================== */
function escHtml(str) {
  if (str == null) return "";
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

/* ===========================================================
   DATA LOADING — replace with your actual fetch() / AJAX calls
   =========================================================== */
async function loadDashboardStats() {
  // const res = await fetch('api/get_stats.php');
  // renderDashboardStats(await res.json());
}
async function loadDashboardHotlines() {
  // const res = await fetch('api/get_hotlines.php?limit=5');
  // renderDashboardHotlines(await res.json());
}
async function loadDashboardEvacCenters() {
  // const res = await fetch('api/get_evac_centers.php');
  // renderDashboardEvacCenters(await res.json());
}
async function loadFloodMap() {
  // const res = await fetch('api/get_flood_status.php');
  // renderFloodMap(await res.json());
}
async function loadEvacTable() {
  // const res = await fetch('api/get_evac_centers.php');
  // renderEvacTable(await res.json());
}
async function loadHotlinesTable() {
  // const res = await fetch('api/get_hotlines.php');
  // renderHotlinesTable(await res.json());
}
async function loadCommunity() {
  // const [ann, rep] = await Promise.all([fetch('api/get_announcements.php'), fetch('api/get_reports.php')]);
  // renderAnnouncements(await ann.json());
  // renderReportsFeed(await rep.json());
}

/* ===========================================================
   INIT
   =========================================================== */
document.addEventListener("DOMContentLoaded", () => {
  /* --- Hamburger open --- */
  const hamburgerBtn = document.getElementById("hamburger-btn");
  if (hamburgerBtn)
    hamburgerBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      openSidebar();
    });

  /* --- Sidebar close (X) --- */
  const closeBtn = document.getElementById("sidebar-close-btn");
  if (closeBtn)
    closeBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      closeSidebar();
    });

  /* --- Overlay click closes sidebar --- */
  const overlay = document.getElementById("sidebar-overlay");
  if (overlay) overlay.addEventListener("click", closeSidebar);

  /* --- Profile dropdown --- */
  const profileBtn = document.getElementById("profile-btn");
  const profileDropdown = document.getElementById("profile-dropdown");
  if (profileBtn && profileDropdown) {
    profileBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      profileDropdown.classList.toggle("open");
    });
    document.addEventListener("click", () =>
      profileDropdown.classList.remove("open"),
    );
  }

  /* --- Search filtering --- */
  const evacSearch = document.getElementById("evac-search");
  if (evacSearch)
    evacSearch.addEventListener("input", () =>
      filterTable("evac-table-body", evacSearch.value),
    );

  const hotlinesSearch = document.getElementById("hotlines-search");
  if (hotlinesSearch)
    hotlinesSearch.addEventListener("input", () =>
      filterTable("hotlines-table-body", hotlinesSearch.value),
    );

  /* --- Load data for whichever page is active --- */
  const page = document.querySelector(".page.active");
  if (!page) return;
  const pageId = page.id;

  if (pageId === "page-dashboard") {
    loadDashboardStats();
    loadDashboardHotlines();
    loadDashboardEvacCenters();
  } else if (pageId === "page-flood-monitoring-map") {
    loadFloodMap();
  } else if (pageId === "page-evacuation-center") {
    loadEvacTable();
  } else if (pageId === "page-hotlines") {
    loadHotlinesTable();
  } else if (pageId === "page-community") {
    loadCommunity();
  }
});
