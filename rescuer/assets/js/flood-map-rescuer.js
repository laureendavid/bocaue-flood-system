/* ================================================================
   FLOOD MONITORING MAP — rescuer side
   Targets: #flood-map-placeholder  (inside .flood-map-container)
   Fetches: ../includes/fetch_flood_severity_map.php
   ================================================================ */

(function () {
  "use strict";

  /* ── Config ────────────────────────────────────────────────── */
  var FETCH_URL = "../includes/fetch_flood_severity_map.php";
  var BOCAUE_CENTER = [14.7982, 120.926];
  var BOCAUE_BOUNDS, BOCAUE_POLYGON, SEVERITY;

  /* ── State ─────────────────────────────────────────────────── */
  var floodMap = null;
  var allReports = [];
  var markers = [];
  var activeFilter = "all";

  /* ── Helpers ───────────────────────────────────────────────── */
  function escHtml(str) {
    if (!str) return "";
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function pointInsideBocaue(lat, lng) {
    var x = lng,
      y = lat,
      inside = false;
    for (
      var i = 0, j = BOCAUE_POLYGON.length - 1;
      i < BOCAUE_POLYGON.length;
      j = i++
    ) {
      var yi = BOCAUE_POLYGON[i][0],
        xi = BOCAUE_POLYGON[i][1];
      var yj = BOCAUE_POLYGON[j][0],
        xj = BOCAUE_POLYGON[j][1];
      var intersects =
        yi > y !== yj > y &&
        x < ((xj - xi) * (y - yi)) / (yj - yi + Number.EPSILON) + xi;
      if (intersects) inside = !inside;
    }
    return inside;
  }

  /* ── Severity pin icon ─────────────────────────────────────── */
  function makeSeverityIcon(severityId) {
    var meta = SEVERITY[severityId] || { color: "#94a3b8", border: "#64748b" };
    var svg =
      '<svg xmlns="http://www.w3.org/2000/svg" width="30" height="38" viewBox="0 0 30 38">' +
      '<defs><filter id="ds" x="-30%" y="-20%" width="160%" height="160%">' +
      '<feDropShadow dx="0" dy="2" stdDeviation="2" flood-color="rgba(0,0,0,0.35)"/>' +
      "</filter></defs>" +
      '<path filter="url(#ds)" fill="' +
      meta.color +
      '" stroke="' +
      meta.border +
      '" stroke-width="1.5"' +
      ' d="M15 1C8.373 1 3 6.373 3 13c0 9 12 24 12 24S27 22 27 13C27 6.373 21.627 1 15 1z"/>' +
      '<circle cx="15" cy="13" r="5.5" fill="#fff" opacity="0.9"/>' +
      "</svg>";

    return L.divIcon({
      html: svg,
      className: "",
      iconSize: [30, 38],
      iconAnchor: [15, 38],
      popupAnchor: [0, -38],
    });
  }

  /* ── Popup ─────────────────────────────────────────────────── */
  function buildPopup(r) {
    var meta = SEVERITY[r.severity_id] || {
      color: "#94a3b8",
      label: "Unknown",
    };
    var dot =
      '<span style="display:inline-block;width:10px;height:10px;border-radius:50%;' +
      "background:" +
      meta.color +
      ';margin-right:6px;vertical-align:middle;"></span>';
    var date = r.created_at
      ? new Date(r.created_at).toLocaleDateString("en-PH", {
          year: "numeric",
          month: "short",
          day: "numeric",
        })
      : "—";

    return (
      "<div style=\"font-family:'Segoe UI',sans-serif;min-width:220px;max-width:280px;\">" +
      '<div style="background:' +
      meta.color +
      ";color:#fff;margin:-1px -1px 0;padding:8px 12px;" +
      'border-radius:8px 8px 0 0;font-weight:700;font-size:0.82rem;letter-spacing:0.03em;">' +
      dot +
      escHtml(meta.label).toUpperCase() +
      "</div>" +
      '<div style="padding:10px 12px;">' +
      '<div style="font-weight:700;font-size:0.9rem;color:#1e293b;margin-bottom:2px;">' +
      escHtml(r.barangay_name) +
      ", " +
      escHtml(r.municipality) +
      "</div>" +
      (r.full_address
        ? '<div style="font-size:0.75rem;color:#64748b;margin-bottom:6px;">' +
          escHtml(r.full_address) +
          "</div>"
        : "") +
      '<hr style="border:none;border-top:1px solid #e2e8f0;margin:6px 0;">' +
      (r.water_level
        ? '<div style="font-size:0.78rem;color:#475569;margin-bottom:4px;"><strong>Water level:</strong> ' +
          escHtml(r.water_level) +
          "</div>"
        : "") +
      (r.description
        ? '<div style="font-size:0.78rem;color:#475569;margin-bottom:4px;"><strong>Details:</strong> ' +
          escHtml(r.description) +
          "</div>"
        : "") +
      '<div style="font-size:0.72rem;color:#94a3b8;margin-top:6px;">Reported ' +
      date +
      "</div>" +
      "</div></div>"
    );
  }

  /* ── Render markers ────────────────────────────────────────── */
  function renderMarkers() {
    markers.forEach(function (m) {
      floodMap.removeLayer(m);
    });
    markers = [];

    var filtered = allReports.filter(function (r) {
      return (
        activeFilter === "all" || String(r.severity_id) === String(activeFilter)
      );
    });

    filtered.forEach(function (r) {
      var lat = parseFloat(r.latitude);
      var lng = parseFloat(r.longitude);
      if (isNaN(lat) || isNaN(lng)) return;

      var marker = L.marker([lat, lng], {
        icon: makeSeverityIcon(parseInt(r.severity_id)),
      })
        .addTo(floodMap)
        .bindPopup(buildPopup(r), {
          maxWidth: 290,
          className: "flood-severity-popup",
        });

      markers.push(marker);
    });

    var badge = document.getElementById("rescuer-flood-marker-count");
    if (badge)
      badge.textContent =
        filtered.length + " report" + (filtered.length !== 1 ? "s" : "");
  }

  /* ── Boundary overlay ──────────────────────────────────────── */
  function applyBoundaryLayer(map) {
    var worldRing = [
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

  /* ── Current location control (top-right) ──────────────────── */
  function addCurrentLocationControl(map) {
    var locationMarker = null;
    var control = L.control({ position: "topright" });

    control.onAdd = function () {
      var button = L.DomUtil.create("button", "leaflet-bar");
      button.type = "button";
      button.textContent = "Use My Current Location";
      button.style.cssText =
        "background:#fff;border:none;padding:8px 10px;font-size:12px;" +
        "font-weight:600;cursor:pointer;min-width:168px;border-radius:6px;";

      L.DomEvent.disableClickPropagation(button);
      L.DomEvent.on(button, "click", function () {
        if (!navigator.geolocation) {
          alert("Geolocation is not supported on this browser.");
          return;
        }
        navigator.geolocation.getCurrentPosition(
          function (position) {
            var lat = position.coords.latitude;
            var lng = position.coords.longitude;
            if (!pointInsideBocaue(lat, lng)) {
              alert("You are outside Bocaue, Bulacan coverage area.");
              return;
            }
            if (locationMarker) locationMarker.setLatLng([lat, lng]);
            else locationMarker = L.marker([lat, lng]).addTo(map);
            locationMarker.bindPopup("Your current location").openPopup();
            map.flyTo([lat, lng], 16, { duration: 0.7 });
          },
          function () {
            alert("Unable to get your current location.");
          },
          { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 },
        );
      });

      return button;
    };

    control.addTo(map);
  }

  /* ── Legend (bottom-left of map) ───────────────────────────── */
  function addLegend(map) {
    var legend = L.control({ position: "bottomleft" });
    legend.onAdd = function () {
      var div = L.DomUtil.create("div");
      div.style.cssText =
        "background:#fff;border-radius:10px;padding:12px 14px;" +
        "box-shadow:0 2px 12px rgba(0,0,0,0.18);font-family:'Segoe UI',sans-serif;" +
        "font-size:0.78rem;line-height:1.7;min-width:150px;";

      div.innerHTML =
        '<div style="font-weight:700;color:#1e293b;margin-bottom:6px;font-size:0.8rem;' +
        'letter-spacing:0.04em;text-transform:uppercase;">Flood Severity</div>' +
        Object.entries(SEVERITY)
          .map(function (entry) {
            var id = entry[0],
              meta = entry[1];
            return (
              '<div style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:2px 0;"' +
              ' data-severity-filter="' +
              id +
              '">' +
              '<span style="width:13px;height:13px;border-radius:50%;flex-shrink:0;background:' +
              meta.color +
              ";border:2px solid " +
              meta.border +
              ';display:inline-block;"></span>' +
              '<span style="color:#334155;">' +
              meta.label +
              "</span></div>"
            );
          })
          .join("") +
        '<div style="margin-top:8px;border-top:1px solid #e2e8f0;padding-top:6px;' +
        'cursor:pointer;color:#3b82f6;font-weight:600;" data-severity-filter="all">Show All</div>';

      L.DomEvent.disableClickPropagation(div);
      div.querySelectorAll("[data-severity-filter]").forEach(function (el) {
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

  /* ── Filter bar (#rescuer-flood-filter-bar) ────────────────── */
  function buildFilterBar() {
    var bar = document.getElementById("rescuer-flood-filter-bar");
    if (!bar) return;

    var items = [
      { id: "all", label: "All", color: "#3b82f6", border: "#2563eb" },
    ];
    Object.entries(SEVERITY).forEach(function (entry) {
      items.push({
        id: entry[0],
        label: entry[1].label,
        color: entry[1].color,
        border: entry[1].border,
      });
    });

    bar.innerHTML =
      items
        .map(function (item) {
          return (
            '<button data-filter="' +
            item.id +
            '" style="' +
            "display:inline-flex;align-items:center;gap:6px;" +
            "padding:6px 14px;border-radius:999px;" +
            "border:2px solid " +
            item.border +
            ";" +
            "background:#fff;color:#1e293b;" +
            "font-size:0.78rem;font-weight:600;" +
            'cursor:pointer;transition:all 0.15s;">' +
            (item.id !== "all"
              ? '<span style="width:10px;height:10px;border-radius:50%;background:' +
                item.color +
                ";border:2px solid " +
                item.border +
                ';display:inline-block;"></span>'
              : "") +
            item.label +
            "</button>"
          );
        })
        .join("") +
      '<span id="rescuer-flood-marker-count" style="margin-left:auto;font-size:0.75rem;' +
      'color:#64748b;align-self:center;"></span>';

    bar.querySelectorAll("button[data-filter]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        activeFilter = this.getAttribute("data-filter");
        renderMarkers();
        updateFilterUI();
      });
      btn.addEventListener("mouseenter", function () {
        if (!this.classList.contains("active-filter")) {
          var meta = SEVERITY[this.dataset.filter] || { color: "#3b82f6" };
          this.style.background = meta.color;
          this.style.color = "#fff";
        }
      });
      btn.addEventListener("mouseleave", function () {
        if (!this.classList.contains("active-filter")) {
          this.style.background = "#fff";
          this.style.color = "#1e293b";
        }
      });
    });
  }

  function updateFilterUI() {
    var bar = document.getElementById("rescuer-flood-filter-bar");
    if (!bar) return;
    bar.querySelectorAll("button[data-filter]").forEach(function (btn) {
      var isActive = btn.getAttribute("data-filter") === activeFilter;
      var meta = SEVERITY[btn.dataset.filter] || {
        color: "#3b82f6",
        border: "#2563eb",
      };
      if (isActive) {
        btn.classList.add("active-filter");
        btn.style.background = meta.color;
        btn.style.color = "#fff";
      } else {
        btn.classList.remove("active-filter");
        btn.style.background = "#fff";
        btn.style.color = "#1e293b";
      }
    });
  }

  /* ── Fetch data ────────────────────────────────────────────── */
  function loadFloodSeverityData() {
    fetch(FETCH_URL)
      .then(function (res) {
        return res.json();
      })
      .then(function (json) {
        if (!json.success) {
          console.warn("Flood map fetch failed:", json.message);
          return;
        }
        allReports = json.data || [];
        renderMarkers();
        updateFilterUI();
      })
      .catch(function (err) {
        console.error("Flood map error:", err);
      });
  }

  /* ── Init map ──────────────────────────────────────────────── */
  function initFloodMap() {
    var placeholder = document.getElementById("flood-map-placeholder");
    if (!placeholder || typeof L === "undefined") return;

    if (floodMap) {
      floodMap.invalidateSize();
      return;
    }

    // Safe to use L here
    BOCAUE_BOUNDS = L.latLngBounds([14.747, 120.865], [14.845, 120.99]);
    BOCAUE_POLYGON = [
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
    SEVERITY = {
      1: { color: "#22c55e", label: "Passable", border: "#16a34a" },
      2: { color: "#eab308", label: "Limited Access", border: "#ca8a04" },
      3: { color: "#ef4444", label: "Impassable", border: "#dc2626" },
    };

    // Clear placeholder and use it as the map container
    placeholder.innerHTML = "";
    placeholder.style.cssText = "width:100%;height:100%;min-height:400px;";

    floodMap = L.map(placeholder, {
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
    buildFilterBar();
    loadFloodSeverityData();

    setTimeout(function () {
      floodMap.invalidateSize();
    }, 150);
  }

  /* ── Boot ──────────────────────────────────────────────────── */
  function init() {
    if (!document.getElementById("flood-map-placeholder")) return;
    setTimeout(initFloodMap, 200);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }

  // Global refresh
  window.rescuerFloodMap = {
    refresh: loadFloodSeverityData,
    setFilter: function (id) {
      activeFilter = String(id);
      renderMarkers();
      updateFilterUI();
    },
  };
})();
