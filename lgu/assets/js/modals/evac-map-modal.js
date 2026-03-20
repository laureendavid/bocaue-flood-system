(function () {
  "use strict";

  var evacMap = null;
  var evacMarker = null;
  var allCenters = [];

  /* ----------------------------------------------------------
     Init modal map
  ---------------------------------------------------------- */
  function initModalMap(lat, lng, name, address) {
    var mapEl = document.getElementById("evac-modal-map");
    if (!mapEl || typeof L === "undefined") return;

    // Destroy previous instance
    if (evacMap) {
      evacMap.remove();
      evacMap = null;
      evacMarker = null;
    }

    evacMap = L.map("evac-modal-map", { zoomControl: true }).setView(
      [lat, lng],
      16,
    );

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: "© OpenStreetMap contributors",
      maxZoom: 19,
    }).addTo(evacMap);

    evacMarker = L.marker([lat, lng])
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
    var modal = document.getElementById("evac-map-modal");
    if (!modal) return;

    document.getElementById("evac-modal-name").textContent = center.name;
    document.getElementById("evac-modal-location").textContent = center.address;

    modal.style.display = "flex";

    var lat = parseFloat(center.lat);
    var lng = parseFloat(center.lng);

    if (!isNaN(lat) && !isNaN(lng)) {
      initModalMap(lat, lng, center.name, center.address);
    } else {
      document.getElementById("evac-modal-map").innerHTML =
        "<p style='padding:24px;color:#64748b;text-align:center;'>No location data available.</p>";
    }
  }

  /* ----------------------------------------------------------
     Close modal
  ---------------------------------------------------------- */
  function closeModal() {
    var modal = document.getElementById("evac-map-modal");
    if (modal) modal.style.display = "none";
    if (evacMap) {
      evacMap.remove();
      evacMap = null;
      evacMarker = null;
    }
  }

  /* ----------------------------------------------------------
     Render evac table
  ---------------------------------------------------------- */
  function renderTable(data) {
    var tbody = document.getElementById("evac-monitor-tbody");
    if (!tbody) return;

    tbody.innerHTML = "";

    data.forEach(function (center, index) {
      var occ = parseInt(center.occupancy) || 0;
      var cap = parseInt(center.capacity) || 0;
      var pct = cap > 0 ? Math.round((occ / cap) * 100) : 0;

      var barColor = "#22c55e";
      var badgeClass = "badge--available";
      var statusText = "Available";

      if (occ >= cap) {
        barColor = "#ef4444";
        badgeClass = "badge--full";
        statusText = "Full";
      } else if (occ >= cap * 0.8) {
        barColor = "#eab308";
        badgeClass = "badge--near-full";
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

      // Attach click directly on the element — same pattern as safety-centers.js
      (function (c) {
        tr.addEventListener("click", function () {
          openModal({
            name: c.center_name,
            address: c.location || "—",
            lat: c.latitude,
            lng: c.longitude,
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
  function loadEvacMonitor() {
    var tbody = document.getElementById("evac-monitor-tbody");
    if (!tbody) return;

    fetch("/soe/includes/fetch_evac_monitor.php")
      .then(function (res) {
        return res.json();
      })
      .then(function (json) {
        if (!json.success || !json.data || json.data.length === 0) {
          tbody.innerHTML =
            "<tr class='empty-row'><td colspan='3'>No evacuation centers to display.</td></tr>";
          return;
        }
        allCenters = json.data;
        renderTable(allCenters);
      })
      .catch(function (err) {
        console.error("Evac monitor error:", err);
        tbody.innerHTML =
          "<tr class='empty-row'><td colspan='3'>Failed to load.</td></tr>";
      });
  }

  /* ----------------------------------------------------------
     Inject modal HTML into body
  ---------------------------------------------------------- */
  function injectModal() {
    if (document.getElementById("evac-map-modal")) return;
    var div = document.createElement("div");
    div.innerHTML =
      '<div id="evac-map-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;">' +
      '<div style="background:#fff;border-radius:14px;width:90%;max-width:560px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.2);">' +
      '<div style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid #e2e8f0;">' +
      "<div>" +
      '<div id="evac-modal-name" style="font-weight:700;font-size:1rem;color:#1e293b;"></div>' +
      '<div id="evac-modal-location" style="font-size:0.78rem;color:#64748b;margin-top:2px;"></div>' +
      "</div>" +
      '<button id="evac-modal-close" style="background:none;border:none;cursor:pointer;padding:4px;color:#64748b;display:flex;align-items:center;">' +
      '<span class="material-symbols-outlined">close</span>' +
      "</button>" +
      "</div>" +
      '<div id="evac-modal-map" style="height:320px;width:100%;"></div>' +
      "</div>" +
      "</div>";
    document.body.appendChild(div.firstChild);

    document
      .getElementById("evac-modal-close")
      .addEventListener("click", function (e) {
        e.stopPropagation();
        closeModal();
      });
    document
      .getElementById("evac-map-modal")
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
    if (!document.getElementById("evac-monitor-tbody")) return;
    injectModal();
    loadEvacMonitor();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
