(function () {
  "use strict";

  var evacMap = null;
  var allCenters = [];
  /* ----------------------------------------------------------
   Render dashboard evac widget (ul#dash-evac-list)
---------------------------------------------------------- */
  function renderDashboardEvac(data) {
    var list = document.getElementById("dash-evac-list");
    if (!list) return;

    list.innerHTML = "";

    if (!data.length) {
      list.innerHTML =
        "<li class='empty-state-inline'>No evacuation centers available.</li>";
      return;
    }

    data.forEach(function (center) {
      var occ = parseInt(center.occupancy) || 0;
      var cap = parseInt(center.capacity) || 0;
      var pct = cap > 0 ? Math.round((occ / cap) * 100) : 0;

      var barColor = "#22c55e";
      var badgeClass = "badge-available"; // was badge--available
      var statusText = "Available";

      if (occ >= cap) {
        barColor = "#ef4444";
        badgeClass = "badge-full"; // was badge--full
        statusText = "Full";
      } else if (occ >= cap * 0.8) {
        barColor = "#eab308";
        badgeClass = "badge-nearfull"; // was badge--near-full
        statusText = "Near Full";
      }

      var li = document.createElement("li");
      li.style.cssText =
        "list-style:none; padding:10px 0; border-bottom:1px solid #f1f5f9; cursor:pointer;";
      li.innerHTML =
        "<div style='display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;'>" +
        "<div>" +
        "<div style='font-weight:600; font-size:0.85rem;'>" +
        escHtml(center.center_name) +
        "</div>" +
        "<div style='font-size:0.72rem; color:#64748b; margin-top:1px;'>" +
        escHtml(center.location || "—") +
        "</div>" +
        "</div>" +
        "<span class='badge " +
        badgeClass +
        "' style='flex-shrink:0; margin-left:8px;'>" +
        statusText +
        "</span>" +
        "</div>" +
        "<div class='capacity-bar-wrap'>" +
        "<div class='capacity-bar' style='width:" +
        pct +
        "%; background:" +
        barColor +
        ";'></div>" +
        "</div>" +
        "<div style='font-size:0.72rem; color:#64748b; margin-top:4px; text-align:right;'>" +
        occ +
        "/" +
        cap +
        "</div>";

      (function (c) {
        li.addEventListener("click", function () {
          openModal({
            name: c.center_name,
            address: c.location || "—",
            lat: c.latitude,
            lng: c.longitude,
          });
        });
        li.addEventListener("mouseenter", function () {
          this.style.background = "#f0f9ff";
        });
        li.addEventListener("mouseleave", function () {
          this.style.background = "";
        });
      })(center);

      list.appendChild(li);
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

    evacMap = L.map("rescuer-evac-modal-map", { zoomControl: true }).setView(
      [lat, lng],
      16,
    );

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: "© OpenStreetMap contributors",
      maxZoom: 19,
    }).addTo(evacMap);

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

    document.getElementById("rescuer-evac-modal-name").textContent =
      center.name;
    document.getElementById("rescuer-evac-modal-location").textContent =
      center.address;

    modal.style.display = "flex";

    var lat = parseFloat(center.lat);
    var lng = parseFloat(center.lng);

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
    if (!input) return;
    input.addEventListener("input", function () {
      var q = input.value.toLowerCase().trim();
      var filtered = allCenters.filter(function (c) {
        return (
          c.center_name.toLowerCase().includes(q) ||
          (c.location || "").toLowerCase().includes(q)
        );
      });
      renderTable(filtered);
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
      var badgeClass = "badge-available"; // was badge--available
      var statusText = "Available";

      if (occ >= cap) {
        barColor = "#ef4444";
        badgeClass = "badge-full"; // was badge--full
        statusText = "Full";
      } else if (occ >= cap * 0.8) {
        barColor = "#eab308";
        badgeClass = "badge-nearfull"; // was badge--near-full
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
      '<div id="rescuer-evac-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;">' +
      '<div style="background:#fff;border-radius:14px;width:90%;max-width:560px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.2);">' +
      '<div style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid #e2e8f0;">' +
      "<div>" +
      '<div id="rescuer-evac-modal-name" style="font-weight:700;font-size:1rem;color:#1e293b;"></div>' +
      '<div id="rescuer-evac-modal-location" style="font-size:0.78rem;color:#64748b;margin-top:2px;"></div>' +
      "</div>" +
      '<button id="rescuer-evac-modal-close" style="background:none;border:none;cursor:pointer;padding:4px;color:#64748b;display:flex;align-items:center;">' +
      '<span class="material-symbols-outlined">close</span>' +
      "</button>" +
      "</div>" +
      '<div id="rescuer-evac-modal-map" style="height:320px;width:100%;"></div>' +
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
