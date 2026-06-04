(function () {
  "use strict";

  var evacMap = null;
  var allCenters = [];
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
    var userLocationMarker = null;
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
            if (userLocationMarker) userLocationMarker.setLatLng([lat, lng]);
            else userLocationMarker = L.marker([lat, lng]).addTo(map);
            userLocationMarker.bindPopup("Your current location").openPopup();
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
     Render dashboard evac widget (ul#dash-evac-list)
  ---------------------------------------------------------- */
  function renderDashboardEvac(data) {
    var container = document.getElementById("dash-evac-list");
    if (!container) return;

    container.innerHTML = "";

    if (!data.length) {
      container.innerHTML =
        "<p class='rdb-empty'>No evacuation centers available.</p>";
      return;
    }

    data.forEach(function (center) {
      var occ = parseInt(center.occupancy) || 0;
      var cap = parseInt(center.capacity) || 0;
      var pct = cap > 0 ? Math.round((occ / cap) * 100) : 0;

      var barClass = "rdb-progress__bar--available";
      var badgeClass = "badge-available";
      var statusText = "Available";

      if (occ >= cap) {
        barClass = "rdb-progress__bar--full";
        badgeClass = "badge-full";
        statusText = "Full";
      } else if (occ >= cap * 0.8) {
        barClass = "rdb-progress__bar--nearfull";
        badgeClass = "badge-nearfull";
        statusText = "Near Full";
      }

      var row = document.createElement("div");
      row.className = "rdb-evac-row";
      row.style.cursor = "pointer";
      row.innerHTML =
        "<div class='rdb-evac-top'>" +
        "<div class='rdb-evac-info'>" +
        "<span class='rdb-evac-name'>" +
        escHtml(center.center_name) +
        "</span>" +
        "<span class='rdb-evac-addr'>" +
        escHtml(center.location || "—") +
        "</span>" +
        "</div>" +
        "<div class='rdb-evac-right'>" +
        "<span class='badge " +
        badgeClass +
        "'>" +
        statusText +
        "</span>" +
        "<span class='rdb-evac-count'>" +
        occ +
        "/" +
        cap +
        "</span>" +
        "</div>" +
        "</div>" +
        "<div class='rdb-progress'>" +
        "<div class='rdb-progress__bar " +
        barClass +
        "' style='width:" +
        Math.min(pct, 100) +
        "%'></div>" +
        "</div>";

      (function (c) {
        row.addEventListener("click", function () {
          openModal({
            name: c.center_name,
            address: c.location || "—",
            lat: c.latitude,
            lng: c.longitude,
            occupancy: c.occupancy,
            capacity: c.capacity,
          });
        });
        row.addEventListener("mouseenter", function () {
          this.style.background = "#f0f9ff";
        });
        row.addEventListener("mouseleave", function () {
          this.style.background = "";
        });
      })(center);

      container.appendChild(row);
    });
  }

  /* ----------------------------------------------------------
     Init modal map
  ---------------------------------------------------------- */
  function initModalMap(lat, lng, name, address) {
    var mapEl = document.getElementById("rescuer-evac-modal-map");
    if (!mapEl || typeof L === "undefined") return;

    if (evacMap) {
      evacMap.remove();
      evacMap = null;
    }

    evacMap = L.map("rescuer-evac-modal-map", {
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
          "</strong><br>" +
          "<span style='font-size:0.75rem;color:#64748b;'>" +
          address +
          "</span>",
      )
      .openPopup();

    setTimeout(function () {
      evacMap.invalidateSize();
    }, 150);
  }

  /* ----------------------------------------------------------
     Open modal
  ---------------------------------------------------------- */
  function openModal(center) {
    var modal = document.getElementById("rescuer-evac-modal");
    if (!modal) return;

    var occ = parseInt(center.occupancy) || 0;
    var cap = parseInt(center.capacity) || 0;
    var pct = cap > 0 ? Math.round((occ / cap) * 100) : 0;

    /* status */
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

    /* Google Maps link */
    var lat = parseFloat(center.lat);
    var lng = parseFloat(center.lng);
    var gmapsUrl =
      !isNaN(lat) && !isNaN(lng)
        ? "https://www.google.com/maps?q=" + lat + "," + lng
        : null;

    /* populate header */
    document.getElementById("rescuer-evac-modal-name").textContent =
      center.name;
    document.getElementById("rescuer-evac-modal-location").textContent =
      center.address;

    /* capacity bar */
    var barEl = document.getElementById("rescuer-evac-modal-bar-fill");
    if (barEl) {
      barEl.style.width = pct + "%";
      barEl.style.background = barColor;
    }

    /* capacity text */
    var occEl = document.getElementById("rescuer-evac-modal-occ");
    if (occEl) occEl.textContent = occ + " / " + cap + " (" + pct + "%)";

    /* status badge */
    var badgeEl = document.getElementById("rescuer-evac-modal-status");
    if (badgeEl) {
      badgeEl.textContent = statusText;
      badgeEl.style.color = statusColor;
      badgeEl.style.background = statusBg;
      badgeEl.style.borderColor = statusColor + "33";
    }

    /* Google Maps button */
    var gmBtn = document.getElementById("rescuer-evac-modal-gmaps");
    if (gmBtn) {
      if (gmapsUrl) {
        gmBtn.href = gmapsUrl;
        gmBtn.style.display = "flex";
      } else {
        gmBtn.style.display = "none";
      }
    }

    modal.style.display = "flex";

    if (!isNaN(lat) && !isNaN(lng)) {
      initModalMap(lat, lng, center.name, center.address);
    } else {
      document.getElementById("rescuer-evac-modal-map").innerHTML =
        "<p style='padding:24px;color:#64748b;text-align:center;'>No location data available.</p>";
    }
  }

  /* ----------------------------------------------------------
     Close modal
  ---------------------------------------------------------- */
  function closeModal() {
    var modal = document.getElementById("rescuer-evac-modal");
    if (modal) modal.style.display = "none";
    if (evacMap) {
      evacMap.remove();
      evacMap = null;
    }
  }

  window.closeRescuerEvacModal = closeModal;

  /* ----------------------------------------------------------
     Search
  ---------------------------------------------------------- */
  function initSearch() {
    var input = document.getElementById("evac-search");
    var filterBtn = document.getElementById("evac-filter-btn");
    var filterDropdown = document.getElementById("evac-filter-dropdown");

    if (filterBtn && filterDropdown) {
      filterBtn.addEventListener("click", function (e) {
        e.stopPropagation();
        filterDropdown.style.display =
          filterDropdown.style.display === "none" ? "block" : "none";
      });
      document.addEventListener("click", function (e) {
        if (!filterDropdown.contains(e.target) && e.target !== filterBtn) {
          filterDropdown.style.display = "none";
        }
      });
    }

    function applyFilters() {
      var q = input ? input.value.toLowerCase().trim() : "";
      var checkedInput = document.querySelector(
        "#evac-status-filters input:checked",
      );
      var selected = (checkedInput ? checkedInput.value : "all").toLowerCase();

      var filtered = allCenters.filter(function (c) {
        var matchesSearch =
          c.center_name.toLowerCase().includes(q) ||
          (c.location || "").toLowerCase().includes(q);

        var occ = parseInt(c.occupancy) || 0;
        var cap = parseInt(c.capacity) || 0;
        var statusText = "available";
        if (occ >= cap) statusText = "full";
        else if (occ >= cap * 0.8) statusText = "near full";

        var matchesStatus = selected === "all" || statusText === selected;
        return matchesSearch && matchesStatus;
      });

      renderTable(filtered);
    }

    if (input) input.addEventListener("input", applyFilters);
    document
      .querySelectorAll("#evac-status-filters input")
      .forEach(function (rb) {
        rb.addEventListener("change", applyFilters);
      });
  }

  /* ----------------------------------------------------------
     Render table
  ---------------------------------------------------------- */
  function renderTable(data) {
    var tbody = document.getElementById("evac-table-body");
    if (!tbody) return;

    tbody.innerHTML = "";

    if (!data.length) {
      tbody.innerHTML =
        "<tr class='empty-row'><td colspan='3'>No evacuation centers found.</td></tr>";
      return;
    }

    data.forEach(function (center) {
      var occ = parseInt(center.occupancy) || 0;
      var cap = parseInt(center.capacity) || 0;
      var pct = cap > 0 ? Math.round((occ / cap) * 100) : 0;

      var barColor = "#22c55e";
      var badgeClass = "badge-available";
      var statusText = "Available";

      if (occ >= cap) {
        barColor = "#ef4444";
        badgeClass = "badge-full";
        statusText = "Full";
      } else if (occ >= cap * 0.8) {
        barColor = "#eab308";
        badgeClass = "badge-nearfull";
        statusText = "Near Full";
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
          openModal({
            name: c.center_name,
            address: c.location || "—",
            lat: c.latitude,
            lng: c.longitude,
            occupancy: c.occupancy,
            capacity: c.capacity,
          });
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
  function loadCenters() {
    var tbody = document.getElementById("evac-table-body");

    fetch("../includes/fetch_evac_monitor.php")
      .then(function (res) {
        return res.json();
      })
      .then(function (json) {
        if (!json.success || !json.data || !json.data.length) {
          if (tbody)
            tbody.innerHTML =
              "<tr class='empty-row'><td colspan='3'>No evacuation centers to display.</td></tr>";
          renderDashboardEvac([]);
          return;
        }
        allCenters = json.data;
        if (tbody) renderTable(allCenters);
        renderDashboardEvac(allCenters);
      })
      .catch(function (err) {
        console.error("Evac load error:", err);
        if (tbody)
          tbody.innerHTML =
            "<tr class='empty-row'><td colspan='3'>Failed to load.</td></tr>";
      });
  }

  /* ----------------------------------------------------------
     Inject modal
  ---------------------------------------------------------- */
  function injectModal() {
    if (document.getElementById("rescuer-evac-modal")) return;

    var div = document.createElement("div");
    div.innerHTML =
      '<div id="rescuer-evac-modal" style="display:none;position:fixed;inset:0;z-index:9999;' +
      'background:rgba(0,0,0,0.5);align-items:center;justify-content:center;">' +
      '<div style="background:#fff;border-radius:16px;width:90%;max-width:580px;' +
      "overflow:hidden;box-shadow:0 8px 40px rgba(0,0,0,0.22);font-family:'Segoe UI',system-ui,sans-serif;\">" +
      /* ── Header ── */
      '<div style="padding:16px 20px;border-bottom:1px solid #f1f5f9;">' +
      '<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">' +
      '<div style="flex:1;min-width:0;">' +
      '<div id="rescuer-evac-modal-name" style="font-weight:700;font-size:1rem;color:#0f172a;"></div>' +
      '<div id="rescuer-evac-modal-location" style="font-size:0.78rem;color:#64748b;margin-top:2px;"></div>' +
      "</div>" +
      '<button id="rescuer-evac-modal-close" style="background:none;border:none;cursor:pointer;' +
      'padding:4px;color:#94a3b8;display:flex;align-items:center;flex-shrink:0;">' +
      '<span class="material-symbols-outlined">close</span>' +
      "</button>" +
      "</div>" +
      /* capacity + status row */
      '<div style="display:flex;align-items:center;gap:12px;margin-top:12px;flex-wrap:wrap;">' +
      '<div style="flex:1;min-width:140px;">' +
      '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">' +
      '<span style="font-size:0.7rem;font-weight:600;color:#94a3b8;letter-spacing:0.06em;text-transform:uppercase;">Occupancy</span>' +
      '<span id="rescuer-evac-modal-occ" style="font-size:0.75rem;font-weight:700;color:#334155;"></span>' +
      "</div>" +
      '<div style="height:7px;border-radius:99px;background:#f1f5f9;overflow:hidden;">' +
      '<div id="rescuer-evac-modal-bar-fill" style="height:100%;border-radius:99px;transition:width 0.4s ease;width:0%;"></div>' +
      "</div>" +
      "</div>" +
      '<span id="rescuer-evac-modal-status" style="font-size:0.72rem;font-weight:700;' +
      'padding:4px 10px;border-radius:99px;border:1.5px solid;white-space:nowrap;"></span>' +
      "</div>" +
      "</div>" +
      /* ── Map ── */
      '<div id="rescuer-evac-modal-map" style="height:300px;width:100%;"></div>' +
      /* ── Footer: Google Maps button ── */
      '<div style="padding:12px 16px;border-top:1px solid #f1f5f9;">' +
      '<a id="rescuer-evac-modal-gmaps" href="#" target="_blank" rel="noopener noreferrer" ' +
      'style="display:flex;align-items:center;justify-content:center;gap:7px;' +
      "padding:9px 14px;border-radius:9px;background:#f8fafc;border:1.5px solid #e2e8f0;" +
      'text-decoration:none;color:#1e40af;font-size:0.78rem;font-weight:600;cursor:pointer;">' +
      '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" ' +
      'stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">' +
      '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>' +
      "</svg>" +
      "Open in Google Maps" +
      "</a>" +
      "</div>" +
      "</div>" +
      "</div>";

    document.body.appendChild(div.firstChild);

    document
      .getElementById("rescuer-evac-modal-close")
      .addEventListener("click", function (e) {
        e.stopPropagation();
        closeModal();
      });
    document
      .getElementById("rescuer-evac-modal")
      .addEventListener("click", function (e) {
        if (e.target === this) closeModal();
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
    var hasDashboard = document.getElementById("dash-evac-list");
    var hasEvacPage = document.getElementById("evac-table-body");
    if (!hasDashboard && !hasEvacPage) return;

    injectModal();
    if (hasEvacPage) initSearch();
    loadCenters();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
