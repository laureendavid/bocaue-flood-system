(function () {
  "use strict";

  var pageMap = null;
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
     Init map (#evac-centers-map)
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

    if (bounds.length > 1) {
      pageMap.fitBounds(bounds, { padding: [30, 30], maxZoom: 16 });
    } else if (bounds.length === 1) {
      pageMap.setView(bounds[0], 15);
    }
  }

  /* Pan/fly to a center's marker and open its popup — used when a
     table row is clicked. */
  function focusCenterOnMap(center) {
    if (!pageMap) return;
    var lat = parseFloat(center.latitude);
    var lng = parseFloat(center.longitude);
    if (isNaN(lat) || isNaN(lng)) return;

    var key = String(center.center_id || center.center_name);
    var marker = pageMarkers[key];

    pageMap.flyTo([lat, lng], 17, { duration: 0.7 });

    if (marker) {
      setTimeout(function () {
        marker.openPopup();
      }, 700);
    }

    var mapCard = document.querySelector(".evac-map-card");
    if (mapCard && window.innerWidth <= 1100) {
      mapCard.scrollIntoView({ behavior: "smooth", block: "start" });
    }
  }

  /* ----------------------------------------------------------
     Render evac table — clicking a row flies to the marker
  ---------------------------------------------------------- */
  function renderTable(data) {
    var tbody = document.getElementById("evac-monitor-tbody");
    if (!tbody) return;
    tbody.innerHTML = "";

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
        ";'></div>" +
        "</div>" +
        "</td>" +
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
          focusCenterOnMap(c);
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
