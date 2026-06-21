(function () {
  "use strict";

  var pageMap = null;
  var evacMap = null;
  var pageMarkers = {};
  var allCenters = [];

  var BOCAUE_CENTER = [14.7982, 120.926];
  var BOCAUE_BOUNDS = L.latLngBounds([14.747, 120.865], [14.845, 120.99]);
  var BOCAUE_POLYGON = [
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
      fillOpacity: 0.35,
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

  function addCurrentLocationControl(map) {
    var locationMarker = null;
    var control = L.control({ position: "topright" });
    control.onAdd = function () {
      var button = L.DomUtil.create("button", "leaflet-bar");
      button.type = "button";
      button.innerHTML =
        '<svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="margin-right:5px;vertical-align:middle;">' +
        '<circle cx="12" cy="12" r="3"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3"/></svg>My Location';
      button.style.cssText =
        "background:#fff;border:none;padding:8px 12px;font-size:12px;" +
        "font-weight:600;cursor:pointer;min-width:130px;border-radius:6px;" +
        "color:#1e293b;display:flex;align-items:center;";
      L.DomEvent.disableClickPropagation(button);
      L.DomEvent.on(button, "click", function () {
        if (!navigator.geolocation) {
          alert("Geolocation is not supported on this browser.");
          return;
        }
        navigator.geolocation.getCurrentPosition(
          function (position) {
            var lat = position.coords.latitude,
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

  /* ----------------------------------------------------------
     Status helpers
  ---------------------------------------------------------- */
  function getStatus(occ, cap) {
    if (occ >= cap) {
      return { label: "Full", color: "#ef4444", border: "#dc2626" };
    }
    if (occ >= cap * 0.8) {
      return { label: "Near Full", color: "#eab308", border: "#ca8a04" };
    }
    return { label: "Available", color: "#22c55e", border: "#16a34a" };
  }

  function makeCenterIcon(statusColor, statusBorder) {
    var svg =
      '<svg xmlns="http://www.w3.org/2000/svg" width="30" height="40" viewBox="0 0 32 42">' +
      '<defs><filter id="ecm-ds" x="-40%" y="-20%" width="180%" height="170%">' +
      '<feDropShadow dx="0" dy="3" stdDeviation="3" flood-color="rgba(0,0,0,0.4)"/>' +
      "</filter></defs>" +
      '<path filter="url(#ecm-ds)" fill="' +
      statusColor +
      '" stroke="' +
      statusBorder +
      '" stroke-width="1.5" ' +
      'd="M16 2C9.373 2 4 7.373 4 14c0 9.5 12 26 12 26S28 23.5 28 14C28 7.373 22.627 2 16 2z"/>' +
      '<circle cx="16" cy="14" r="6" fill="rgba(255,255,255,0.95)"/>' +
      '<svg x="8" y="6" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="' +
      statusColor +
      '" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">' +
      '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>' +
      "</svg>" +
      "</svg>";
    return L.divIcon({
      html: svg,
      className: "",
      iconSize: [30, 40],
      iconAnchor: [15, 40],
      popupAnchor: [0, -42],
    });
  }

  function buildMarkerPopup(center, occ, cap, pct, status) {
    var lat = parseFloat(center.latitude);
    var lng = parseFloat(center.longitude);
    var gmapsUrl =
      !isNaN(lat) && !isNaN(lng)
        ? "https://www.google.com/maps?q=" + lat + "," + lng
        : null;

    return (
      '<div class="ecm-popup">' +
      '<div class="ecm-popup__header" style="background:' +
      status.color +
      ';">' +
      '<div class="ecm-popup__eyebrow">Evacuation Center</div>' +
      '<div class="ecm-popup__title">' +
      escHtml(center.center_name) +
      "</div>" +
      "</div>" +
      '<div class="ecm-popup__body">' +
      '<div class="ecm-popup__address">' +
      escHtml(center.location || "—") +
      "</div>" +
      '<div class="ecm-popup__occ-row">' +
      '<span class="ecm-popup__occ-label">Occupancy</span>' +
      '<span class="ecm-popup__occ-value">' +
      occ +
      " / " +
      cap +
      "</span>" +
      "</div>" +
      '<div class="ecm-popup__bar-wrap">' +
      '<div class="ecm-popup__bar-fill" style="width:' +
      Math.min(pct, 100) +
      "%;background:" +
      status.color +
      ';"></div>' +
      "</div>" +
      '<span class="ecm-popup__badge" style="background:' +
      status.color +
      "22;color:" +
      status.border +
      ';">' +
      status.label +
      "</span>" +
      (gmapsUrl
        ? '<a class="ecm-popup__gmaps" href="' +
          gmapsUrl +
          '" target="_blank" rel="noopener noreferrer">Open in Google Maps</a>'
        : "") +
      "</div>" +
      "</div>"
    );
  }

  /* ----------------------------------------------------------
     Dashboard modal + mini-map
  ---------------------------------------------------------- */
  function injectModal() {
    if (document.getElementById("lgu-evac-modal")) return;
    var div = document.createElement("div");
    div.innerHTML =
      '<div id="lgu-evac-modal" style="display:none;position:fixed;inset:0;z-index:9999;' +
      'background:rgba(0,0,0,0.5);align-items:center;justify-content:center;">' +
      '<div style="background:#fff;border-radius:16px;width:90%;max-width:580px;' +
      "overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,0.22);font-family:'Segoe UI',system-ui,sans-serif;\">" +
      '<div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;">' +
      '<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">' +
      '<div style="flex:1;min-width:0;">' +
      '<div id="lgu-evac-modal-name" style="font-weight:700;font-size:1rem;color:#0f172a;"></div>' +
      '<div id="lgu-evac-modal-location" style="font-size:0.78rem;color:#64748b;margin-top:2px;"></div>' +
      "</div>" +
      '<button id="lgu-evac-modal-close" style="background:none;border:none;cursor:pointer;padding:4px;color:#94a3b8;display:flex;align-items:center;flex-shrink:0;">' +
      '<span class="material-symbols-outlined">close</span></button></div>' +
      '<div style="display:flex;align-items:center;gap:12px;margin-top:12px;flex-wrap:wrap;">' +
      '<div style="flex:1;min-width:140px;">' +
      '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">' +
      '<span style="font-size:0.7rem;font-weight:600;color:#94a3b8;letter-spacing:0.06em;text-transform:uppercase;">Occupancy</span>' +
      '<span id="lgu-evac-modal-occ" style="font-size:0.75rem;font-weight:700;color:#334155;"></span></div>' +
      '<div style="height:7px;border-radius:99px;background:#f1f5f9;overflow:hidden;">' +
      '<div id="lgu-evac-modal-bar-fill" style="height:100%;border-radius:99px;transition:width 0.4s ease;width:0%;"></div></div></div>' +
      '<span id="lgu-evac-modal-status" style="font-size:0.72rem;font-weight:700;padding:4px 10px;border-radius:99px;border:1.5px solid;white-space:nowrap;"></span>' +
      "</div></div>" +
      '<div id="lgu-evac-modal-map" style="height:300px;width:100%;"></div>' +
      '<div style="padding:12px 16px;border-top:1px solid #f1f5f9;">' +
      '<a id="lgu-evac-modal-gmaps" href="#" target="_blank" rel="noopener noreferrer" ' +
      'style="display:flex;align-items:center;justify-content:center;gap:7px;padding:9px 14px;border-radius:9px;background:#f8fafc;border:1.5px solid #e2e8f0;text-decoration:none;color:#1e40af;font-size:0.78rem;font-weight:600;cursor:pointer;">' +
      '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">' +
      '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>' +
      "Open in Google Maps</a></div></div></div>";
    document.body.appendChild(div.firstChild);
    document
      .getElementById("lgu-evac-modal-close")
      .addEventListener("click", function (e) {
        e.stopPropagation();
        closeModal();
      });
    document
      .getElementById("lgu-evac-modal")
      .addEventListener("click", function (e) {
        if (e.target === this) closeModal();
      });
  }

  function initModalMap(lat, lng, name, address) {
    var mapEl = document.getElementById("lgu-evac-modal-map");
    if (!mapEl || typeof L === "undefined") return;
    if (evacMap) {
      evacMap.remove();
      evacMap = null;
    }
    evacMap = L.map("lgu-evac-modal-map", {
      zoomControl: true,
      minZoom: 13,
      maxZoom: 19,
      maxBounds: BOCAUE_BOUNDS,
      maxBoundsViscosity: 1.0,
    }).setView([lat, lng], 16);
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: "© OpenStreetMap contributors",
      maxZoom: 19,
    }).addTo(evacMap);
    applyBoundaryLayer(evacMap);
    addCurrentLocationControl(evacMap);
    L.marker([lat, lng])
      .addTo(evacMap)
      .bindPopup(
        "<strong>" +
          name +
          "</strong><br><span style='font-size:0.75rem;color:#64748b;'>" +
          address +
          "</span>",
      )
      .openPopup();
    setTimeout(function () {
      evacMap.invalidateSize();
    }, 150);
  }

  function openModal(center) {
    var modal = document.getElementById("lgu-evac-modal");
    if (!modal) return;

    var occ = parseInt(center.occupancy) || 0;
    var cap = parseInt(center.capacity) || 0;
    var pct = cap > 0 ? Math.round((occ / cap) * 100) : 0;

    var statusText, statusColor, statusBg;
    if (occ >= cap) {
      statusText = "Full";
      statusColor = "#dc2626";
      statusBg = "#fef2f2";
    } else if (occ >= cap * 0.8) {
      statusText = "Near Full";
      statusColor = "#d97706";
      statusBg = "#fffbeb";
    } else {
      statusText = "Available";
      statusColor = "#16a34a";
      statusBg = "#f0fdf4";
    }

    var barColor =
      occ >= cap ? "#ef4444" : occ >= cap * 0.8 ? "#eab308" : "#22c55e";
    var lat = parseFloat(center.latitude);
    var lng = parseFloat(center.longitude);
    var gmapsUrl =
      !isNaN(lat) && !isNaN(lng)
        ? "https://www.google.com/maps?q=" + lat + "," + lng
        : null;

    document.getElementById("lgu-evac-modal-name").textContent =
      center.center_name;
    document.getElementById("lgu-evac-modal-location").textContent =
      center.location || "—";

    var barEl = document.getElementById("lgu-evac-modal-bar-fill");
    if (barEl) {
      barEl.style.width = pct + "%";
      barEl.style.background = barColor;
    }

    var occEl = document.getElementById("lgu-evac-modal-occ");
    if (occEl) occEl.textContent = occ + " / " + cap + " (" + pct + "%)";

    var badgeEl = document.getElementById("lgu-evac-modal-status");
    if (badgeEl) {
      badgeEl.textContent = statusText;
      badgeEl.style.color = statusColor;
      badgeEl.style.background = statusBg;
      badgeEl.style.borderColor = statusColor + "33";
    }

    var gmBtn = document.getElementById("lgu-evac-modal-gmaps");
    if (gmBtn) {
      if (gmapsUrl) {
        gmBtn.href = gmapsUrl;
        gmBtn.style.display = "flex";
      } else gmBtn.style.display = "none";
    }

    modal.style.display = "flex";

    if (!isNaN(lat) && !isNaN(lng)) {
      initModalMap(lat, lng, center.center_name, center.location || "—");
    } else {
      document.getElementById("lgu-evac-modal-map").innerHTML =
        "<p style='padding:24px;color:#64748b;text-align:center;'>No location data available.</p>";
    }
  }

  function closeModal() {
    var modal = document.getElementById("lgu-evac-modal");
    if (modal) modal.style.display = "none";
    if (evacMap) {
      evacMap.remove();
      evacMap = null;
    }
  }

  window.closeLguEvacModal = closeModal;

  /* ----------------------------------------------------------
     Init map (#evac-centers-map) — Data Monitoring page
  ---------------------------------------------------------- */
  function initPageMap() {
    var mapDiv = document.getElementById("evac-centers-map");
    if (!mapDiv || typeof L === "undefined") return;
    if (pageMap) {
      pageMap.invalidateSize();
      return;
    }
    pageMap = L.map("evac-centers-map", {
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
    }).addTo(pageMap);
    applyBoundaryLayer(pageMap);
    addCurrentLocationControl(pageMap);
    setTimeout(function () {
      pageMap.invalidateSize();
    }, 150);
  }

  function renderPageMarkers(centers) {
    if (!pageMap) return;
    Object.keys(pageMarkers).forEach(function (key) {
      pageMap.removeLayer(pageMarkers[key]);
    });
    pageMarkers = {};
    var bounds = [];
    centers.forEach(function (center) {
      var lat = parseFloat(center.latitude);
      var lng = parseFloat(center.longitude);
      if (isNaN(lat) || isNaN(lng)) return;
      var occ = parseInt(center.occupancy) || 0;
      var cap = parseInt(center.capacity) || 0;
      var pct = cap > 0 ? Math.round((occ / cap) * 100) : 0;
      var status = getStatus(occ, cap);
      var marker = L.marker([lat, lng], {
        icon: makeCenterIcon(status.color, status.border),
      })
        .addTo(pageMap)
        .bindPopup(buildMarkerPopup(center, occ, cap, pct, status), {
          maxWidth: 280,
          minWidth: 240,
          className: "ecm-popup-wrap",
        });
      var key = String(center.center_id || center.center_name);
      pageMarkers[key] = marker;
      bounds.push([lat, lng]);
    });
    if (bounds.length > 1)
      pageMap.fitBounds(bounds, { padding: [30, 30], maxZoom: 16 });
    else if (bounds.length === 1) pageMap.setView(bounds[0], 15);
  }

  function focusCenterOnMap(center) {
    if (!pageMap) return;
    var lat = parseFloat(center.latitude);
    var lng = parseFloat(center.longitude);
    if (isNaN(lat) || isNaN(lng)) return;
    var key = String(center.center_id || center.center_name);
    var marker = pageMarkers[key];
    pageMap.flyTo([lat, lng], 17, { duration: 0.7 });
    if (marker)
      setTimeout(function () {
        marker.openPopup();
      }, 700);
    var mapCard = document.querySelector(".evac-map-card");
    if (mapCard && window.innerWidth <= 1100)
      mapCard.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  /* ----------------------------------------------------------
     Render table:
     - Dashboard (#page-dashboard)  → row click opens modal
     - Data Monitoring (has #evac-centers-map) → row click flies to map
  ---------------------------------------------------------- */
  function renderTable(data) {
    var tbody = document.getElementById("evac-monitor-tbody");
    if (!tbody) return;
    tbody.innerHTML = "";

    var onDashboard = !!document.getElementById("page-dashboard");

    if (!data.length) {
      tbody.innerHTML =
        "<tr class='empty-row'><td colspan='3'>No evacuation centers to display.</td></tr>";
      return;
    }

    data.forEach(function (center) {
      var occ = parseInt(center.occupancy) || 0;
      var cap = parseInt(center.capacity) || 0;
      var pct = cap > 0 ? Math.round((occ / cap) * 100) : 0;

      var barColor, badgeClass, statusText;
      if (occ >= cap) {
        barColor = "#ef4444";
        badgeClass = "badge--full";
        statusText = "Full";
      } else if (occ >= cap * 0.8) {
        barColor = "#eab308";
        badgeClass = "badge--near-full";
        statusText = "Near Full";
      } else {
        barColor = "#22c55e";
        badgeClass = "badge--available";
        statusText = "Available";
      }

      var tr = document.createElement("tr");
      tr.style.cursor = "pointer";
      tr.innerHTML =
        "<td>" +
        "<div style='font-weight:600;font-size:0.85rem;'>" +
        escHtml(center.center_name) +
        "</div>" +
        "<div style='font-size:0.75rem;color:#64748b;margin-top:2px;'>" +
        escHtml(center.location || "—") +
        "</div>" +
        "<div class='capacity-bar-wrap' style='margin-top:6px;'>" +
        "<div class='capacity-bar' style='width:" +
        pct +
        "%;background:" +
        barColor +
        ";'></div></div></td>" +
        "<td style='font-size:0.85rem;'>" +
        occ +
        "/" +
        cap +
        "</td>" +
        "<td><span class='badge " +
        badgeClass +
        "'>" +
        statusText +
        "</span></td>";

      (function (c) {
        tr.addEventListener("click", function () {
          if (onDashboard) openModal(c);
          else focusCenterOnMap(c);
        });
      })(center);

      tr.addEventListener("mouseenter", function () {
        this.style.background = "#f0f9ff";
      });
      tr.addEventListener("mouseleave", function () {
        this.style.background = "";
      });
      tbody.appendChild(tr);
    });
  }

  /* ----------------------------------------------------------
     Fetch data
  ---------------------------------------------------------- */
  function loadEvacMonitor() {
    var tbody = document.getElementById("evac-monitor-tbody");
    if (!tbody) return;

    fetch("../includes/fetch_evac_monitor.php")
      .then(function (res) {
        return res.json();
      })
      .then(function (json) {
        if (!json.success || !json.data || json.data.length === 0) {
          tbody.innerHTML =
            "<tr class='empty-row'><td colspan='3'>No evacuation centers to display.</td></tr>";
          renderPageMarkers([]);
          return;
        }
        allCenters = json.data;
        renderTable(allCenters);
        renderPageMarkers(allCenters);
      })
      .catch(function (err) {
        console.error("Evac monitor error:", err);
        tbody.innerHTML =
          "<tr class='empty-row'><td colspan='3'>Failed to load.</td></tr>";
      });
  }

  /* ----------------------------------------------------------
     Utility
  ---------------------------------------------------------- */
  function escHtml(str) {
    if (!str) return "";
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  /* ----------------------------------------------------------
     Boot
  ---------------------------------------------------------- */
  function init() {
    if (!document.getElementById("evac-monitor-tbody")) return;

    injectModal();
    setTimeout(initPageMap, 150);
    loadEvacMonitor();

    document.querySelectorAll(".nav-link").forEach(function (link) {
      link.addEventListener("click", function () {
        setTimeout(function () {
          if (document.getElementById("evac-centers-map")) {
            initPageMap();
            if (allCenters.length) renderPageMarkers(allCenters);
          }
        }, 200);
      });
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
