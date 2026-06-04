/* ================================================================
   FLOOD MONITORING MAP — lgu/assets/js/flood-map.js
   ================================================================ */

let floodMap = null;
let allReports = [];
let markers = [];
let activeFilter = "all";

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
  const x = lng;
  const y = lat;
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
      '<circle cx="12" cy="12" r="3"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3"/>' +
      "</svg>My Location";
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
          const lat = position.coords.latitude;
          const lng = position.coords.longitude;
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
  const lat = parseFloat(r.latitude);
  const lng = parseFloat(r.longitude);
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

      <!-- Header -->
      <div style="background:${meta.color};padding:10px 14px;display:flex;align-items:center;gap:8px;">
        <span style="width:26px;height:26px;border-radius:50%;background:rgba(255,255,255,0.25);display:inline-flex;align-items:center;justify-content:center;font-size:14px;font-weight:900;color:#fff;">${icon}</span>
        <div>
          <div style="color:rgba(255,255,255,0.75);font-size:0.65rem;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;">Flood Severity</div>
          <div style="color:#fff;font-size:0.88rem;font-weight:700;line-height:1.2;">${escHtml(meta.label)}</div>
        </div>
      </div>

      <!-- Body -->
      <div style="padding:12px 14px;background:#fff;">

        <!-- Location -->
        <div style="display:flex;align-items:flex-start;gap:8px;margin-bottom:10px;">
          <span style="margin-top:2px;font-size:14px;flex-shrink:0;">📍</span>
          <div>
            <div style="font-weight:700;font-size:0.88rem;color:#0f172a;line-height:1.3;">${escHtml(r.barangay_name)}, ${escHtml(r.municipality)}</div>
            ${r.full_address ? `<div style="font-size:0.73rem;color:#64748b;margin-top:1px;">${escHtml(r.full_address)}</div>` : ""}
          </div>
        </div>

        <div style="height:1px;background:#f1f5f9;margin:8px 0;"></div>

        <!-- Water level -->
        ${
          r.water_level
            ? `
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
          <span style="font-size:13px;">💧</span>
          <div style="font-size:0.78rem;color:#334155;"><span style="color:#94a3b8;">Water level</span> &nbsp;<strong style="color:#0f172a;">${escHtml(r.water_level)}</strong></div>
        </div>`
            : ""
        }

        <!-- Description -->
        ${
          r.description
            ? `
        <div style="display:flex;align-items:flex-start;gap:8px;margin-bottom:6px;">
          <span style="font-size:13px;margin-top:1px;">📝</span>
          <div style="font-size:0.78rem;color:#334155;">${escHtml(r.description)}</div>
        </div>`
            : ""
        }

        <!-- Date + Reporter -->
        <div style="display:flex;align-items:center;gap:8px;margin-top:6px;">
          <span style="font-size:13px;">🕐</span>
          <div style="font-size:0.72rem;color:#94a3b8;">
            Reported ${date}${r.reported_by ? ` &nbsp;·&nbsp; <strong style="color:#64748b;">${escHtml(r.reported_by)}</strong>` : ""}
          </div>
        </div>

        <div style="height:1px;background:#f1f5f9;margin:10px 0 8px;"></div>

        <!-- Google Maps button -->
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
      activeFilter === "all" || String(r.severity_id) === String(activeFilter),
  );

  filtered.forEach((r) => {
    const lat = parseFloat(r.latitude);
    const lng = parseFloat(r.longitude);
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
   Legend (bottom-left of map)
---------------------------------------------------------------- */
function addLegend(map) {
  const legend = L.control({ position: "bottomleft" });
  legend.onAdd = function () {
    const div = L.DomUtil.create("div");
    div.style.cssText =
      "background:#fff;border-radius:10px;padding:10px 12px;" +
      "box-shadow:0 2px 16px rgba(0,0,0,0.15);font-family:'Segoe UI',system-ui,sans-serif;" +
      "font-size:0.76rem;min-width:140px;";

    div.innerHTML =
      `<div style="font-weight:700;color:#64748b;margin-bottom:8px;font-size:0.65rem;
         letter-spacing:0.1em;text-transform:uppercase;">Flood Severity</div>` +
      Object.entries(SEVERITY)
        .map(
          ([id, meta]) =>
            `<div style="display:flex;align-items:center;gap:8px;padding:3px 6px;border-radius:6px;
               cursor:pointer;transition:background 0.15s;" data-severity-filter="${id}"
               onmouseenter="this.style.background='#f8fafc'" onmouseleave="this.style.background='transparent'">
               <span style="width:11px;height:11px;border-radius:50%;flex-shrink:0;
                 background:${meta.color};border:2px solid ${meta.border};display:inline-block;"></span>
               <span style="color:#334155;font-weight:500;">${meta.label}</span>
             </div>`,
        )
        .join("") +
      `<div style="margin-top:8px;padding:3px 6px;border-radius:6px;cursor:pointer;
         color:#3b82f6;font-weight:600;font-size:0.73rem;transition:background 0.15s;"
         data-severity-filter="all"
         onmouseenter="this.style.background='#eff6ff'" onmouseleave="this.style.background='transparent'">
         ⟳ Show All
       </div>`;

    L.DomEvent.disableClickPropagation(div);
    div.querySelectorAll("[data-severity-filter]").forEach((el) => {
      el.addEventListener("click", function () {
        activeFilter = this.getAttribute("data-severity-filter");
        renderMarkers();
        updateFilterUI();
      });
    });

    return div;
  };
  legend.addTo(map);
}

/* ----------------------------------------------------------------
   Filter bar (#flood-filter-bar)
---------------------------------------------------------------- */
function buildFilterBar() {
  const bar = document.getElementById("flood-filter-bar");
  if (!bar) return;

  /* Inject scoped styles once */
  if (!document.getElementById("lgu-filter-styles")) {
    const style = document.createElement("style");
    style.id = "lgu-filter-styles";
    style.textContent = `
      #flood-filter-bar {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
        padding: 10px 14px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
      }
      .lfb-btn {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 7px 16px;
        border-radius: 8px;
        border: 2px solid transparent;
        background: #fff;
        color: #475569;
        font-size: 0.78rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.18s ease;
        box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        white-space: nowrap;
      }
      .lfb-btn:hover:not(.lfb-active) {
        transform: translateY(-1px);
        box-shadow: 0 3px 10px rgba(0,0,0,0.12);
      }
      .lfb-btn.lfb-active {
        color: #fff;
        box-shadow: 0 3px 10px rgba(0,0,0,0.18);
        transform: translateY(-1px);
      }
      .lfb-dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        border: 2px solid rgba(0,0,0,0.15);
        flex-shrink: 0;
      }
      #flood-marker-count {
        margin-left: auto;
        font-size: 0.73rem;
        color: #94a3b8;
        font-weight: 500;
        white-space: nowrap;
      }
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
  addLegend(floodMap);
  buildFilterBar(); /* calls updateFilterUI() internally */
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
};
