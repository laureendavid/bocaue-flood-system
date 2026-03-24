(function () {
  "use strict";

  var allHotlines = [];

  /* ----------------------------------------------------------
     Inject confirm modal
  ---------------------------------------------------------- */
  function injectModal() {
    if (document.getElementById("call-confirm-modal")) return;
    var div = document.createElement("div");
    div.innerHTML =
      '<div id="call-confirm-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;">' +
      '<div style="background:#fff;border-radius:14px;width:90%;max-width:380px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,0.2);padding:28px 24px;">' +
      '<div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">' +
      '<div style="width:44px;height:44px;border-radius:50%;background:#dcfce7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">' +
      '<span class="material-symbols-outlined" style="color:#16a34a;font-size:22px;">call</span>' +
      "</div>" +
      "<div>" +
      '<div style="font-weight:700;font-size:1rem;color:#1e293b;">Call Hotline?</div>' +
      '<div id="call-confirm-name" style="font-size:0.82rem;color:#64748b;margin-top:2px;"></div>' +
      "</div>" +
      "</div>" +
      '<div style="background:#f8fafc;border-radius:8px;padding:12px 16px;margin-bottom:20px;text-align:center;">' +
      '<div style="font-size:1.2rem;font-weight:700;color:#1e293b;font-family:monospace;" id="call-confirm-number"></div>' +
      "</div>" +
      '<div style="display:flex;gap:10px;">' +
      '<button id="call-confirm-cancel" style="flex:1;padding:10px;border-radius:8px;border:1.5px solid #e2e8f0;background:#fff;font-size:0.85rem;font-weight:600;cursor:pointer;color:#64748b;">Cancel</button>' +
      '<a id="call-confirm-btn" href="#" style="flex:1;padding:10px;border-radius:8px;border:none;background:#16a34a;font-size:0.85rem;font-weight:600;cursor:pointer;color:#fff;text-align:center;text-decoration:none;display:flex;align-items:center;justify-content:center;gap:6px;">' +
      '<span class="material-symbols-outlined" style="font-size:16px;">call</span>Call Now' +
      "</a>" +
      "</div>" +
      "</div>" +
      "</div>";
    document.body.appendChild(div.firstChild);

    document
      .getElementById("call-confirm-cancel")
      .addEventListener("click", closeCallModal);
    document
      .getElementById("call-confirm-modal")
      .addEventListener("click", function (e) {
        if (e.target === this) closeCallModal();
      });
  }

  function openCallModal(name, number) {
    document.getElementById("call-confirm-name").textContent = name;
    document.getElementById("call-confirm-number").textContent = number;
    document.getElementById("call-confirm-btn").href =
      "tel:" + number.replace(/\D/g, "");
    document.getElementById("call-confirm-modal").style.display = "flex";
  }

  function closeCallModal() {
    document.getElementById("call-confirm-modal").style.display = "none";
  }

  /* ----------------------------------------------------------
     Filter dropdowns
  ---------------------------------------------------------- */
  function injectFilterDropdowns() {
    if (document.getElementById("filter-barangay-dropdown")) return;

    // Barangay filter dropdown
    var barangayDiv = document.createElement("div");
    barangayDiv.style.cssText = "position:relative; display:inline-block;";
    barangayDiv.innerHTML =
      '<button id="filter-barangay-btn" class="btn-filter">' +
      '<span class="material-symbols-outlined" style="font-size:16px">location_on</span> Filter by Barangay' +
      "</button>" +
      '<div id="filter-barangay-dropdown" style="display:none;position:absolute;top:calc(100% + 6px);left:0;background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;box-shadow:0 4px 16px rgba(0,0,0,0.1);z-index:999;min-width:200px;max-height:260px;overflow-y:auto;padding:8px 0;">' +
      '<div id="filter-barangay-options"></div>' +
      "</div>";

    // Hotline name filter dropdown
    var nameDiv = document.createElement("div");
    nameDiv.style.cssText = "position:relative; display:inline-block;";
    nameDiv.innerHTML =
      '<button id="filter-name-btn" class="btn-filter">' +
      '<span class="material-symbols-outlined" style="font-size:16px">badge</span> Filter by Hotline Name' +
      "</button>" +
      '<div id="filter-name-dropdown" style="display:none;position:absolute;top:calc(100% + 6px);left:0;background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;box-shadow:0 4px 16px rgba(0,0,0,0.1);z-index:999;min-width:200px;max-height:260px;overflow-y:auto;padding:8px 0;">' +
      '<div id="filter-name-options"></div>' +
      "</div>";

    // Find the existing filter button and replace it
    var existingFilter = document.querySelector("#page-hotlines .btn-filter");
    if (existingFilter) {
      var toolbar = existingFilter.parentNode;
      toolbar.removeChild(existingFilter);
      toolbar.appendChild(barangayDiv);
      toolbar.appendChild(nameDiv);
    }

    // Toggle barangay dropdown
    document
      .getElementById("filter-barangay-btn")
      .addEventListener("click", function (e) {
        e.stopPropagation();
        var dd = document.getElementById("filter-barangay-dropdown");
        var nameDd = document.getElementById("filter-name-dropdown");
        nameDd.style.display = "none";
        dd.style.display = dd.style.display === "block" ? "none" : "block";
      });

    // Toggle name dropdown
    document
      .getElementById("filter-name-btn")
      .addEventListener("click", function (e) {
        e.stopPropagation();
        var dd = document.getElementById("filter-name-dropdown");
        var barangayDd = document.getElementById("filter-barangay-dropdown");
        barangayDd.style.display = "none";
        dd.style.display = dd.style.display === "block" ? "none" : "block";
      });

    // Close on outside click
    document.addEventListener("click", function () {
      var dd1 = document.getElementById("filter-barangay-dropdown");
      var dd2 = document.getElementById("filter-name-dropdown");
      if (dd1) dd1.style.display = "none";
      if (dd2) dd2.style.display = "none";
    });
  }

  function populateFilters() {
    var barangays = [];
    var names = [];

    allHotlines.forEach(function (h) {
      if (barangays.indexOf(h.barangay) === -1) barangays.push(h.barangay);
      if (names.indexOf(h.hotline_name) === -1) names.push(h.hotline_name);
    });

    barangays.sort();
    names.sort();

    var barangayOpts = document.getElementById("filter-barangay-options");
    var nameOpts = document.getElementById("filter-name-options");

    if (barangayOpts) {
      barangayOpts.innerHTML =
        makeRadioOption("filter-barangay", "", "All Barangays", true) +
        barangays
          .map(function (b) {
            return makeRadioOption("filter-barangay", b, b, false);
          })
          .join("");
      barangayOpts.querySelectorAll("input").forEach(function (r) {
        r.addEventListener("change", applyFilters);
      });
    }

    if (nameOpts) {
      nameOpts.innerHTML =
        makeRadioOption("filter-name", "", "All Types", true) +
        names
          .map(function (n) {
            return makeRadioOption("filter-name", n, n, false);
          })
          .join("");
      nameOpts.querySelectorAll("input").forEach(function (r) {
        r.addEventListener("change", applyFilters);
      });
    }
  }

  function makeRadioOption(name, value, label, checked) {
    return (
      '<label style="display:flex;align-items:center;gap:8px;padding:8px 16px;font-size:0.83rem;cursor:pointer;color:#1e293b;">' +
      '<input type="radio" name="' +
      name +
      '" value="' +
      value +
      '"' +
      (checked ? " checked" : "") +
      ' style="cursor:pointer;" />' +
      escHtml(label) +
      "</label>"
    );
  }

  function applyFilters() {
    var search = (document.getElementById("hotlines-search") || {}).value || "";
    search = search.toLowerCase().trim();

    var activeBarangay =
      (document.querySelector('input[name="filter-barangay"]:checked') || {})
        .value || "";
    var activeName =
      (document.querySelector('input[name="filter-name"]:checked') || {})
        .value || "";

    // Update button highlight
    var barangayBtn = document.getElementById("filter-barangay-btn");
    var nameBtn = document.getElementById("filter-name-btn");
    if (barangayBtn)
      barangayBtn.style.background = activeBarangay ? "var(--tab-active)" : "";
    if (nameBtn)
      nameBtn.style.background = activeName ? "var(--tab-active)" : "";

    var filtered = allHotlines.filter(function (h) {
      var matchSearch =
        search === "" ||
        h.hotline_name.toLowerCase().includes(search) ||
        h.barangay.toLowerCase().includes(search) ||
        h.contact_number.includes(search);
      var matchBarangay =
        activeBarangay === "" || h.barangay === activeBarangay;
      var matchName = activeName === "" || h.hotline_name === activeName;
      return matchSearch && matchBarangay && matchName;
    });

    renderTable(filtered);
  }

  /* ----------------------------------------------------------
     Render table
  ---------------------------------------------------------- */
  function renderTable(data) {
    var tbody = document.getElementById("hotlines-table-body");
    if (!tbody) return;

    tbody.innerHTML = "";

    if (!data.length) {
      tbody.innerHTML =
        "<tr class='empty-row'><td colspan='4'>No hotlines found.</td></tr>";
      return;
    }

    data.forEach(function (h) {
      var tr = document.createElement("tr");
      tr.innerHTML =
        "<td style='font-weight:600;font-size:0.85rem;'>" +
        escHtml(h.hotline_name) +
        "</td>" +
        "<td style='font-size:0.85rem;'>" +
        escHtml(h.barangay) +
        "</td>" +
        "<td style='font-size:0.85rem;font-family:monospace;'>" +
        escHtml(h.contact_number) +
        "</td>" +
        "<td style='text-align:center;'>" +
        "<button class='btn-call' title='Call " +
        escHtml(h.hotline_name) +
        "'>" +
        "<span class='material-symbols-outlined'>call</span>" +
        "</button>" +
        "</td>";

      tr.querySelector(".btn-call").addEventListener("click", function () {
        openCallModal(h.hotline_name + " — " + h.barangay, h.contact_number);
      });

      tbody.appendChild(tr);
    });
  }

  /* ----------------------------------------------------------
     Fetch
  ---------------------------------------------------------- */
  function loadHotlines() {
    var tbody = document.getElementById("hotlines-table-body");
    if (!tbody) return;

    fetch("../api/fetch_hotlines.php")
      .then(function (res) {
        return res.json();
      })
      .then(function (json) {
        if (!json.success || !json.data || !Object.keys(json.data).length) {
          tbody.innerHTML =
            "<tr class='empty-row'><td colspan='4'>No hotlines available.</td></tr>";
          return;
        }

        allHotlines = [];
        Object.keys(json.data).forEach(function (barangay) {
          json.data[barangay].forEach(function (h) {
            allHotlines.push({
              hotline_name: h.hotline_name,
              barangay: barangay,
              contact_number: h.contact_number,
            });
          });
        });

        populateFilters();
        renderTable(allHotlines);
      })
      .catch(function (err) {
        console.error("Hotlines load error:", err);
        tbody.innerHTML =
          "<tr class='empty-row'><td colspan='4'>Failed to load hotlines.</td></tr>";
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
    if (!document.getElementById("hotlines-table-body")) return;
    injectModal();
    injectFilterDropdowns();

    var searchInput = document.getElementById("hotlines-search");
    if (searchInput) searchInput.addEventListener("input", applyFilters);

    loadHotlines();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();
