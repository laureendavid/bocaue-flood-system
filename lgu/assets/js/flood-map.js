/* ================================================================
   FLOOD MONITORING MAP — lgu/assets/js/flood-map.js
   ================================================================ */

let floodMap = null;
let allReports = [];
let markers = [];
let activeFilter = "all";

/* Date filter state */
let activeDatePreset = "all"; /* "all" | "today" | "7" | "30" | "custom" */
let activeDateFrom = null; /* "YYYY-MM-DD" or null */
let activeDateTo = null; /* "YYYY-MM-DD" or null */

const BOCAUE_CENTER = [14.7982, 120.926];
const BOCAUE_BOUNDS = L.latLngBounds([14.747, 120.865], [14.845, 120.99]);
const BOCAUE_POLYGON = [
  [14.844, 120.888],
  [14.839, 120.924],
  [14.831, 120.963],
  [14.816, 120.986],
  [14.787, 120.988],
  [14.764, 120.975],
  [14.751, 120.948],
  [14.748, 120.91],
  [14.757, 120.882],
  [14.779, 120.867],
  [14.809, 120.868],
];

const SEVERITY = {
  1: { color: "#22c55e", label: "Passable", border: "#16a34a" },
  2: { color: "#eab308", label: "Limited Access", border: "#ca8a04" },
  3: { color: "#ef4444", label: "Impassable", border: "#dc2626" },
};

/* ----------------------------------------------------------------
   Helpers
---------------------------------------------------------------- */
function escHtml(str) {
  if (!str) return "";
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

function pointInsideBocaue(lat, lng) {
  const x = lng,
    y = lat;
  let inside = false;
  for (
    let i = 0, j = BOCAUE_POLYGON.length - 1;
    i < BOCAUE_POLYGON.length;
    j = i++
  ) {
    const yi = BOCAUE_POLYGON[i][0],
      xi = BOCAUE_POLYGON[i][1];
    const yj = BOCAUE_POLYGON[j][0],
      xj = BOCAUE_POLYGON[j][1];
    const intersects =
      yi > y !== yj > y &&
      x < ((xj - xi) * (y - yi)) / (yj - yi + Number.EPSILON) + xi;
    if (intersects) inside = !inside;
  }
  return inside;
}

/* ----------------------------------------------------------------
   Date filter helper
---------------------------------------------------------------- */
function filterByDate(report) {
  if (activeDatePreset === "all" && !activeDateFrom && !activeDateTo)
    return true;

  const created = report.created_at ? new Date(report.created_at) : null;
  if (!created || isNaN(created)) return false;

  const createdDate = new Date(
    created.getFullYear(),
    created.getMonth(),
    created.getDate(),
  );
  const today = new Date();
  today.setHours(0, 0, 0, 0);

  if (activeDatePreset === "today") {
    return createdDate.getTime() === today.getTime();
  }
  if (activeDatePreset === "7") {
    const cutoff = new Date(today);
    cutoff.setDate(cutoff.getDate() - 6);
    return createdDate >= cutoff;
  }
  if (activeDatePreset === "30") {
    const cutoff = new Date(today);
    cutoff.setDate(cutoff.getDate() - 29);
    return createdDate >= cutoff;
  }
  /* custom range */
  if (activeDateFrom) {
    const from = new Date(activeDateFrom + "T00:00:00");
    if (createdDate < from) return false;
  }
  if (activeDateTo) {
    const to = new Date(activeDateTo + "T00:00:00");
    if (createdDate > to) return false;
  }
  return true;
}

function fmtDate(d) {
  if (!d) return "";
  const [y, m, day] = d.split("-");
  const months = [
    "Jan",
    "Feb",
    "Mar",
    "Apr",
    "May",
    "Jun",
    "Jul",
    "Aug",
    "Sep",
    "Oct",
    "Nov",
    "Dec",
  ];
  return `${months[parseInt(m) - 1]} ${parseInt(day)}, ${y}`;
}

/* ----------------------------------------------------------------
   Boundary overlay
---------------------------------------------------------------- */
function applyBoundaryLayer(map) {
  const worldRing = [
    [-90, -180],
    [-90, 180],
    [90, 180],
    [90, -180],
  ];
  L.polygon([worldRing, BOCAUE_POLYGON], {
    stroke: false,
    fillColor: "#0b1f3b",
    fillOpacity: 0.4,
    interactive: false,
  }).addTo(map);
  L.polygon(BOCAUE_POLYGON, {
    color: "#2563eb",
    weight: 2,
    fillOpacity: 0.02,
    dashArray: "5,5",
    interactive: false,
  }).addTo(map);
  map.setMaxBounds(BOCAUE_BOUNDS);
}

/* ----------------------------------------------------------------
   Current location control
---------------------------------------------------------------- */
function addCurrentLocationControl(map) {
  let locationMarker = null;
  const control = L.control({ position: "topright" });
  control.onAdd = function () {
    const button = L.DomUtil.create("button", "leaflet-bar");
    button.type = "button";
    button.innerHTML =
      '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px;vertical-align:middle;">' +
      '<circle cx="12" cy="12" r="3"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3"/></svg>My Location';
    button.style.cssText =
      "background:#fff;border:none;padding:8px 12px;font-size:12px;" +
      "font-weight:600;cursor:pointer;min-width:130px;border-radius:6px;" +
      "color:#1e293b;display:flex;align-items:center;";
    L.DomEvent.disableClickPropagation(button);
    L.DomEvent.on(button, "click", () => {
      if (!navigator.geolocation) {
        alert("Geolocation is not supported on this browser.");
        return;
      }
      navigator.geolocation.getCurrentPosition(
        (position) => {
          const lat = position.coords.latitude,
            lng = position.coords.longitude;
          if (!pointInsideBocaue(lat, lng)) {
            alert("You are outside Bocaue, Bulacan coverage area.");
            return;
          }
          if (locationMarker) locationMarker.setLatLng([lat, lng]);
          else locationMarker = L.marker([lat, lng]).addTo(map);
          locationMarker.bindPopup("Your current location").openPopup();
          map.flyTo([lat, lng], 16, { duration: 0.7 });
        },
        () => alert("Unable to get your current location."),
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 },
      );
    });
    return button;
  };
  control.addTo(map);
}

/* ----------------------------------------------------------------
   Severity pin icon (SVG)
---------------------------------------------------------------- */
function makeSeverityIcon(severityId) {
  const meta = SEVERITY[severityId] || { color: "#94a3b8", border: "#64748b" };
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="32" height="42" viewBox="0 0 32 42">
    <defs>
      <filter id="ds${severityId}" x="-40%" y="-20%" width="180%" height="170%">
        <feDropShadow dx="0" dy="3" stdDeviation="3" flood-color="rgba(0,0,0,0.4)"/>
      </filter>
    </defs>
    <path filter="url(#ds${severityId})" fill="${meta.color}" stroke="${meta.border}" stroke-width="1.5"
      d="M16 2C9.373 2 4 7.373 4 14c0 9.5 12 26 12 26S28 23.5 28 14C28 7.373 22.627 2 16 2z"/>
    <circle cx="16" cy="14" r="6" fill="rgba(255,255,255,0.95)"/>
    <circle cx="16" cy="14" r="3.5" fill="${meta.color}"/>
  </svg>`;
  return L.divIcon({
    html: svg,
    className: "",
    iconSize: [32, 42],
    iconAnchor: [16, 42],
    popupAnchor: [0, -44],
  });
}

/* ----------------------------------------------------------------
   Popup content
---------------------------------------------------------------- */
function buildPopup(r) {
  const meta = SEVERITY[r.severity_id] || {
    color: "#94a3b8",
    label: "Unknown",
  };
  const lat = parseFloat(r.latitude),
    lng = parseFloat(r.longitude);
  const gmapsUrl = `https://www.google.com/maps?q=${lat},${lng}`;
  const date = r.created_at
    ? new Date(r.created_at).toLocaleDateString("en-PH", {
        year: "numeric",
        month: "short",
        day: "numeric",
      })
    : "—";
  const severityIcons = { 1: "✓", 2: "⚠", 3: "✕" };
  const icon = severityIcons[r.severity_id] || "•";

  return `
    <div style="font-family:'Segoe UI',system-ui,sans-serif;min-width:230px;max-width:290px;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.15);">
      <div style="background:${meta.color};padding:10px 14px;display:flex;align-items:center;gap:8px;">
        <span style="width:26px;height:26px;border-radius:50%;background:rgba(255,255,255,0.25);display:inline-flex;align-items:center;justify-content:center;font-size:14px;font-weight:900;color:#fff;">${icon}</span>
        <div>
          <div style="color:rgba(255,255,255,0.75);font-size:0.65rem;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;">Flood Severity</div>
          <div style="color:#fff;font-size:0.88rem;font-weight:700;line-height:1.2;">${escHtml(meta.label)}</div>
        </div>
      </div>
      <div style="padding:12px 14px;background:#fff;">
        <div style="display:flex;align-items:flex-start;gap:8px;margin-bottom:10px;">
          <span style="margin-top:2px;font-size:14px;flex-shrink:0;">📍</span>
          ${r.full_address ? `<div style="font-weight:700;font-size:0.88rem;color:#0f172a;line-height:1.3;">${escHtml(r.full_address)}</div>` : ""}
        </div>
        <div style="height:1px;background:#f1f5f9;margin:8px 0;"></div>
        ${
          r.water_level
            ? `
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
          <span style="font-size:13px;">💧</span>
          <div style="font-size:0.78rem;color:#334155;"><span style="color:#94a3b8;">Water level</span> &nbsp;<strong style="color:#0f172a;">${escHtml(r.water_level)}</strong></div>
        </div>`
            : ""
        }
        ${
          r.description
            ? `
        <div style="display:flex;align-items:flex-start;gap:8px;margin-bottom:6px;">
          <span style="font-size:13px;margin-top:1px;">📝</span>
          <div style="font-size:0.78rem;color:#334155;">${escHtml(r.description)}</div>
        </div>`
            : ""
        }
        <div style="display:flex;align-items:center;gap:8px;margin-top:6px;">
          <span style="font-size:13px;">🕐</span>
          <div style="font-size:0.72rem;color:#94a3b8;">
            Reported ${date}${r.reported_by ? ` &nbsp;·&nbsp; <strong style="color:#64748b;">${escHtml(r.reported_by)}</strong>` : ""}
          </div>
        </div>
        <div style="height:1px;background:#f1f5f9;margin:10px 0 8px;"></div>
        <a href="${gmapsUrl}" target="_blank" rel="noopener noreferrer"
          style="display:flex;align-items:center;justify-content:center;gap:6px;
          padding:7px 12px;border-radius:8px;background:#f8fafc;border:1.5px solid #e2e8f0;
          text-decoration:none;color:#1e40af;font-size:0.76rem;font-weight:600;cursor:pointer;">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
          </svg>
          Open in Google Maps
        </a>
      </div>
    </div>`;
}

/* ----------------------------------------------------------------
   Render / re-render markers
---------------------------------------------------------------- */
function renderMarkers() {
  markers.forEach((m) => floodMap.removeLayer(m));
  markers = [];

  const filtered = allReports.filter(
    (r) =>
      (activeFilter === "all" ||
        String(r.severity_id) === String(activeFilter)) &&
      filterByDate(r),
  );

  filtered.forEach((r) => {
    const lat = parseFloat(r.latitude),
      lng = parseFloat(r.longitude);
    if (isNaN(lat) || isNaN(lng)) return;
    const marker = L.marker([lat, lng], {
      icon: makeSeverityIcon(parseInt(r.severity_id)),
    })
      .addTo(floodMap)
      .bindPopup(buildPopup(r), {
        maxWidth: 300,
        className: "flood-severity-popup",
      });
    markers.push(marker);
  });

  const badge = document.getElementById("flood-marker-count");
  if (badge)
    badge.textContent =
      filtered.length + " report" + (filtered.length !== 1 ? "s" : "");
}

/* ----------------------------------------------------------------
   Date filter bar (#flood-date-filter-bar)
---------------------------------------------------------------- */
function buildDateFilterBar() {
  const bar = document.getElementById("flood-date-filter-bar");
  if (!bar) return;

  if (!document.getElementById("lgu-date-filter-styles")) {
    const style = document.createElement("style");
    style.id = "lgu-date-filter-styles";
    style.textContent = `
      #flood-date-filter-bar {
        display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
        padding: 10px 14px; background: #f8fafc;
        border: 1px solid #e2e8f0; border-radius: 12px;
      }
      .ldfb-label {
        font-size: 0.7rem; font-weight: 600; color: #64748b;
        text-transform: uppercase; letter-spacing: 0.08em;
        white-space: nowrap; margin-right: 4px; display: flex; align-items: center; gap: 4px;
      }
      .ldfb-preset {
        display: inline-flex; align-items: center; padding: 6px 13px;
        border-radius: 8px; border: 1.5px solid #e2e8f0;
        background: #fff; color: #475569;
        font-size: 0.76rem; font-weight: 600; cursor: pointer;
        transition: all 0.15s ease; white-space: nowrap;
      }
      .ldfb-preset:hover:not(.ldfb-active) { background: #f1f5f9; border-color: #94a3b8; }
      .ldfb-preset.ldfb-active { background: #3b82f6; border-color: #2563eb; color: #fff; }
      .ldfb-sep { color: #cbd5e1; font-size: 1rem; margin: 0 2px; }
      .ldfb-date-wrap { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
      .ldfb-date-wrap label { font-size: 0.72rem; color: #94a3b8; white-space: nowrap; }
      .ldfb-date-wrap input[type="date"] {
        padding: 5px 9px; border-radius: 8px; font-size: 0.76rem;
        border: 1.5px solid #e2e8f0; background: #fff; color: #1e293b; cursor: pointer;
        font-family: inherit;
      }
      .ldfb-date-wrap input[type="date"]:focus { outline: none; border-color: #3b82f6; }
      .ldfb-apply {
        padding: 5px 13px; border-radius: 8px; font-size: 0.76rem; font-weight: 600;
        border: 1.5px solid #2563eb; background: #3b82f6; color: #fff; cursor: pointer;
        transition: background 0.15s;
      }
      .ldfb-apply:hover { background: #2563eb; }
      .ldfb-clear {
        padding: 5px 10px; border-radius: 8px; font-size: 0.72rem; font-weight: 600;
        border: 1.5px solid #e2e8f0; background: transparent; color: #64748b; cursor: pointer;
        transition: background 0.15s;
      }
      .ldfb-clear:hover { background: #f1f5f9; }
      #flood-date-active-info {
        display: none; align-items: center; gap: 6px;
        font-size: 0.72rem; color: #1e40af;
        padding: 5px 12px; background: #eff6ff;
        border: 1px solid #bfdbfe; border-radius: 8px;
      }
      #flood-date-active-info.show { display: inline-flex; }
    `;
    document.head.appendChild(style);
  }

  bar.innerHTML = `
    <span class="ldfb-label">
      <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none"
        stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/>
        <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
      </svg>
      Date
    </span>
    <button class="ldfb-preset ldfb-active" data-preset="all">All time</button>
    <button class="ldfb-preset" data-preset="today">Today</button>
    <button class="ldfb-preset" data-preset="7">Last 7 days</button>
    <button class="ldfb-preset" data-preset="30">Last 30 days</button>
    <span class="ldfb-sep">|</span>
    <div class="ldfb-date-wrap">
      <label for="flood-date-from">From</label>
      <input type="date" id="flood-date-from" />
      <label for="flood-date-to">to</label>
      <input type="date" id="flood-date-to" />
      <button class="ldfb-apply" id="flood-date-apply">Apply</button>
      <button class="ldfb-clear" id="flood-date-clear" style="display:none;">Clear</button>
    </div>
  `;

  bar.querySelectorAll(".ldfb-preset").forEach((btn) => {
    btn.addEventListener("click", function () {
      activeDatePreset = this.getAttribute("data-preset");
      activeDateFrom = null;
      activeDateTo = null;
      document.getElementById("flood-date-from").value = "";
      document.getElementById("flood-date-to").value = "";
      document.getElementById("flood-date-clear").style.display = "none";
      bar
        .querySelectorAll(".ldfb-preset")
        .forEach((b) => b.classList.remove("ldfb-active"));
      this.classList.add("ldfb-active");
      updateDateActiveInfo();
      renderMarkers();
    });
  });

  document.getElementById("flood-date-apply").addEventListener("click", () => {
    const from = document.getElementById("flood-date-from").value;
    const to = document.getElementById("flood-date-to").value;
    if (!from && !to) return;
    activeDatePreset = "custom";
    activeDateFrom = from || null;
    activeDateTo = to || null;
    bar
      .querySelectorAll(".ldfb-preset")
      .forEach((b) => b.classList.remove("ldfb-active"));
    document.getElementById("flood-date-clear").style.display = "";
    updateDateActiveInfo();
    renderMarkers();
  });

  document.getElementById("flood-date-clear").addEventListener("click", () => {
    activeDatePreset = "all";
    activeDateFrom = null;
    activeDateTo = null;
    document.getElementById("flood-date-from").value = "";
    document.getElementById("flood-date-to").value = "";
    document.getElementById("flood-date-clear").style.display = "none";
    bar
      .querySelectorAll(".ldfb-preset")
      .forEach((b) => b.classList.remove("ldfb-active"));
    bar.querySelector('[data-preset="all"]').classList.add("ldfb-active");
    updateDateActiveInfo();
    renderMarkers();
  });
}

function updateDateActiveInfo() {
  const el = document.getElementById("flood-date-active-info");
  if (!el) return;
  if (activeDatePreset === "all") {
    el.classList.remove("show");
    return;
  }
  let msg = "";
  if (activeDatePreset === "today") msg = "Showing today's reports only";
  else if (activeDatePreset === "7")
    msg = "Showing reports from the last 7 days";
  else if (activeDatePreset === "30")
    msg = "Showing reports from the last 30 days";
  else if (activeDateFrom && activeDateTo)
    msg = `${fmtDate(activeDateFrom)} – ${fmtDate(activeDateTo)}`;
  else if (activeDateFrom) msg = `From ${fmtDate(activeDateFrom)} onwards`;
  else if (activeDateTo) msg = `Up to ${fmtDate(activeDateTo)}`;
  el.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none"
    stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/>
    <line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
    </svg> ${msg}`;
  el.classList.add("show");
}

/* ----------------------------------------------------------------
   Severity filter bar (#flood-filter-bar)
---------------------------------------------------------------- */
function buildFilterBar() {
  const bar = document.getElementById("flood-filter-bar");
  if (!bar) return;

  if (!document.getElementById("lgu-filter-styles")) {
    const style = document.createElement("style");
    style.id = "lgu-filter-styles";
    style.textContent = `
      #flood-filter-bar {
        display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
        padding: 10px 14px; background: #f8fafc;
        border: 1px solid #e2e8f0; border-radius: 12px;
      }
      .lfb-btn {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 7px 16px; border-radius: 8px;
        border: 2px solid transparent; background: #fff; color: #475569;
        font-size: 0.78rem; font-weight: 600; cursor: pointer;
        transition: all 0.18s ease; box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        white-space: nowrap;
      }
      .lfb-btn:hover:not(.lfb-active) { transform: translateY(-1px); box-shadow: 0 3px 10px rgba(0,0,0,0.12); }
      .lfb-btn.lfb-active { color: #fff; box-shadow: 0 3px 10px rgba(0,0,0,0.18); transform: translateY(-1px); }
      .lfb-dot { width: 9px; height: 9px; border-radius: 50%; border: 2px solid rgba(0,0,0,0.15); flex-shrink: 0; }
      #flood-marker-count { margin-left: auto; font-size: 0.73rem; color: #94a3b8; font-weight: 500; white-space: nowrap; }
    `;
    document.head.appendChild(style);
  }

  const items = [
    { id: "all", label: "All Reports", color: "#3b82f6", border: "#2563eb" },
    ...Object.entries(SEVERITY).map(([id, meta]) => ({
      id,
      label: meta.label,
      color: meta.color,
      border: meta.border,
    })),
  ];

  bar.innerHTML =
    items
      .map(
        (item) => `
      <button class="lfb-btn" data-filter="${item.id}">
        ${
          item.id === "all"
            ? `<span style="font-size:15px;line-height:1;">⊞</span>`
            : `<span class="lfb-dot" style="background:${item.color};"></span>`
        }
        ${item.label}
      </button>`,
      )
      .join("") + `<span id="flood-marker-count"></span>`;

  bar.querySelectorAll(".lfb-btn").forEach((btn) => {
    btn.addEventListener("click", function () {
      activeFilter = this.getAttribute("data-filter");
      renderMarkers();
      updateFilterUI();
    });
  });

  updateFilterUI();
}

function updateFilterUI() {
  const bar = document.getElementById("flood-filter-bar");
  if (!bar) return;
  const severityBgs = { 1: "#22c55e", 2: "#eab308", 3: "#ef4444" };
  const severityBorders = { 1: "#16a34a", 2: "#ca8a04", 3: "#dc2626" };

  bar.querySelectorAll(".lfb-btn").forEach((btn) => {
    const filter = btn.getAttribute("data-filter");
    const isActive = filter === activeFilter;
    if (isActive) {
      btn.classList.add("lfb-active");
      const bg =
        filter === "all" ? "#3b82f6" : severityBgs[filter] || "#3b82f6";
      const bd =
        filter === "all" ? "#2563eb" : severityBorders[filter] || "#2563eb";
      btn.style.background = bg;
      btn.style.borderColor = bd;
      btn.style.color = "#fff";
      const dot = btn.querySelector(".lfb-dot");
      if (dot) {
        dot.style.background = "rgba(255,255,255,0.85)";
        dot.style.borderColor = "rgba(255,255,255,0.4)";
      }
    } else {
      btn.classList.remove("lfb-active");
      btn.style.background = "#fff";
      btn.style.borderColor = "transparent";
      btn.style.color = "#475569";
      const dot = btn.querySelector(".lfb-dot");
      if (dot) {
        dot.style.background = severityBgs[filter] || "#94a3b8";
        dot.style.borderColor = "rgba(0,0,0,0.15)";
      }
    }
  });
}

/* ----------------------------------------------------------------
   Fetch approved reports from PHP
---------------------------------------------------------------- */
function loadFloodSeverityData() {
  fetch("../includes/fetch_flood_severity_map.php")
    .then((res) => res.json())
    .then((json) => {
      if (!json.success) {
        console.warn("Flood severity fetch failed:", json.message);
        return;
      }
      allReports = json.data || [];
      renderMarkers();
      updateFilterUI();
    })
    .catch((err) => console.error("Flood severity map error:", err));
}

/* ----------------------------------------------------------------
   Init map
---------------------------------------------------------------- */
function initFloodMap() {
  const mapDiv = document.getElementById("flood-map");
  if (!mapDiv) return;

  if (floodMap) {
    floodMap.invalidateSize();
    return;
  }

  floodMap = L.map("flood-map", {
    center: BOCAUE_CENTER,
    zoom: 14,
    minZoom: 13,
    maxZoom: 19,
    maxBounds: BOCAUE_BOUNDS,
    maxBoundsViscosity: 1.0,
  });

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution:
      '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 19,
  }).addTo(floodMap);

  applyBoundaryLayer(floodMap);
  addCurrentLocationControl(floodMap);
  /* NOTE: addLegend() removed — legend is now in the filter bar above the map */
  buildDateFilterBar();
  buildFilterBar();
  loadFloodSeverityData();

  floodMap.invalidateSize();
}

/* ----------------------------------------------------------------
   Boot
---------------------------------------------------------------- */
document.addEventListener("DOMContentLoaded", () => {
  setTimeout(initFloodMap, 200);

  document.querySelectorAll(".nav-link").forEach((link) => {
    link.addEventListener("click", function () {
      if (this.dataset.page === "data-monitoring") {
        setTimeout(initFloodMap, 200);
      }
    });
  });
});

/* ----------------------------------------------------------------
   Global API
---------------------------------------------------------------- */
window.floodSeverityMap = {
  refresh: loadFloodSeverityData,
  setFilter: (id) => {
    activeFilter = String(id);
    renderMarkers();
    updateFilterUI();
  },
  setDatePreset: (preset) => {
    activeDatePreset = preset;
    activeDateFrom = null;
    activeDateTo = null;
    renderMarkers();
    updateDateActiveInfo();
  },
};
