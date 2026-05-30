// --- Toast ---
function showToast(message, type = "success") {
  const toastContainer = document.getElementById("toast-container");
  if (!toastContainer) return;
  const toast = document.createElement("div");
  toast.classList.add("toast", type);
  toast.innerHTML = `<span>${message}</span><span class="close-toast">&times;</span>`;
  toastContainer.appendChild(toast);
  toast
    .querySelector(".close-toast")
    .addEventListener("click", () => toast.remove());
  setTimeout(() => toast.remove(), 3000);
}

// Change Role — open modal
document.addEventListener("click", function (e) {
  const btn = e.target.closest(".btn-change-role");
  if (!btn) return;
  document.getElementById("cr-user-id").value = btn.dataset.id;
  document.getElementById("cr-name").value = btn.dataset.name;
  document.getElementById("cr-email").value = btn.dataset.email;
  document.getElementById("cr-role").value = btn.dataset.role;
  openModal("modal-change-role");
});

// Delete — open modal
document.addEventListener("click", function (e) {
  const btn = e.target.closest(".btn-delete");
  if (!btn) return;
  document.getElementById("del-user-id").value = btn.dataset.id;
  document.getElementById("del-user-name").textContent = btn.dataset.name;
  openModal("modal-delete");
});

// Save role change
document
  .getElementById("confirm-change-role")
  ?.addEventListener("click", () => {
    const userId = document.getElementById("cr-user-id").value;
    const newRole = document.getElementById("cr-role").value;

    fetch("../api/change_role.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: `user_id=${userId}&role=${encodeURIComponent(newRole)}`,
    })
      .then((r) => r.json())
      .then((data) => {
        closeModal("modal-change-role");
        showToast(data.message, data.success ? "success" : "error");
        if (data.success) setTimeout(() => location.reload(), 1500);
      })
      .catch(() =>
        showToast("Something went wrong. Please try again.", "error"),
      );
  });

// Confirm delete
document.getElementById("confirm-delete")?.addEventListener("click", () => {
  const userId = document.getElementById("del-user-id").value;

  fetch("../api/delete_user.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `user_id=${userId}`,
  })
    .then((r) => r.json())
    .then((data) => {
      closeModal("modal-delete");
      showToast(data.message, data.success ? "success" : "error");
      if (data.success) setTimeout(() => location.reload(), 1500);
    })
    .catch(() => showToast("Something went wrong. Please try again.", "error"));
});

// Close on outside click
document.querySelectorAll(".modal-overlay").forEach((overlay) => {
  overlay.addEventListener("click", function (e) {
    if (e.target === this) closeModal(this.id);
  });
});

// ── Search & Filter ────────────────────────────────────────
const umSearch = document.getElementById("um-search");
const umFilterBtn = document.getElementById("um-filter-btn");
const umFilterDropdown = document.getElementById("um-filter-dropdown");
const umTbody = document.getElementById("um-tbody");
const umEmpty = document.getElementById("um-empty");

// Toggle filter dropdown
umFilterBtn?.addEventListener("click", (e) => {
  e.stopPropagation();
  const isOpen = umFilterDropdown.style.display === "flex";
  umFilterDropdown.style.display = isOpen ? "none" : "flex";
  umFilterDropdown.style.flexDirection = "column";
  umFilterDropdown.style.gap = "8px";
});
// Close filter dropdown when clicking outside
document.addEventListener("click", (e) => {
  if (!umFilterDropdown?.contains(e.target) && e.target !== umFilterBtn) {
    if (umFilterDropdown) umFilterDropdown.style.display = "none";
  }
});

function filterTable() {
  if (!umTbody) return;

  const search = umSearch?.value.toLowerCase().trim() ?? "";
  const activeRole =
    document.querySelector('input[name="um-role"]:checked')?.value ?? "";
  const rows = umTbody.querySelectorAll("tr");
  let visibleCount = 0;

  rows.forEach((row) => {
    if (row.classList.contains("empty-row")) return;

    const text = row.textContent.toLowerCase();
    const role = row.querySelector(".badge")?.textContent.trim() ?? "";

    const matchesSearch = search === "" || text.includes(search);
    const matchesRole = activeRole === "" || role === activeRole;

    if (matchesSearch && matchesRole) {
      row.style.display = "";
      visibleCount++;
    } else {
      row.style.display = "none";
    }
  });

  // Show/hide empty state
  if (umEmpty) umEmpty.style.display = visibleCount === 0 ? "block" : "none";

  // Update filter button to show active state
  if (umFilterBtn) {
    const activeRole = document.querySelector(
      'input[name="um-role"]:checked',
    )?.value;
    umFilterBtn.style.background = activeRole ? "var(--tab-active)" : "";
    umFilterBtn.style.fontWeight = activeRole ? "700" : "";
  }
}

// Listen for search input
umSearch?.addEventListener("input", filterTable);

// Listen for role filter change
document.querySelectorAll('input[name="um-role"]').forEach((radio) => {
  radio.addEventListener("change", () => {
    if (umFilterDropdown) umFilterDropdown.style.display = "none";
    filterTable();
  });
});

(function () {
  var modal = document.getElementById("addUserModal");
  var openBtn = document.getElementById("btn-add-user");
  var closeBtn = document.getElementById("closeAddUserModal");
  var cancelBtn = document.getElementById("cancelAddUser");
  var form = document.getElementById("addUserForm");
  var errorBox = document.getElementById("au_error");
  var matchText = document.getElementById("au_match_text");
  var mapStatus = document.getElementById("au_map_status");
  var addressEl = document.getElementById("au_address");
  var latEl = document.getElementById("au_lat");
  var lngEl = document.getElementById("au_lng");
  var submitBtn = document.getElementById("au_submit_btn");
  var pwEl = document.getElementById("au_password");
  var cpEl = document.getElementById("au_confirm_password");
  var barangayEl = document.getElementById("au_barangay");
  var gpsBtn = document.getElementById("au_gps_btn");

  var auMap = null,
    auMarker = null;
  var suppressMarkerMove = false;

  /* ── Bocaue boundary (same as registration page) ── */
  var bocaueBoundary = [
    [14.845, 120.867],
    [14.842, 120.902],
    [14.835, 120.932],
    [14.825, 120.966],
    [14.81, 120.989],
    [14.786, 120.987],
    [14.764, 120.971],
    [14.751, 120.949],
    [14.748, 120.92],
    [14.75, 120.89],
    [14.76, 120.872],
    [14.784, 120.866],
    [14.812, 120.864],
    [14.835, 120.865],
  ];

  /* ── Open / Close ── */
  function openModal() {
    modal.classList.add("open");
    document.body.style.overflow = "hidden";
    setTimeout(initMap, 80);
  }
  function closeModal() {
    modal.classList.remove("open");
    document.body.style.overflow = "";
    form.reset();
    errorBox.style.display = "none";
    matchText.textContent = "";
    latEl.value = "";
    lngEl.value = "";
    setMapStatus("Select a barangay or drag the pin.");
  }

  if (openBtn) openBtn.addEventListener("click", openModal);
  if (closeBtn) closeBtn.addEventListener("click", closeModal);
  if (cancelBtn) cancelBtn.addEventListener("click", closeModal);
  modal.addEventListener("click", function (e) {
    if (e.target === modal) closeModal();
  });

  /* ── Map helpers ── */
  function setMapStatus(text) {
    mapStatus.textContent = text;
  }

  function updateLatLng(latlng) {
    latEl.value = latlng.lat.toFixed(7);
    lngEl.value = latlng.lng.toFixed(7);
  }

  function keepInsideBounds(latlng, strictBounds) {
    return L.latLng(
      Math.min(
        Math.max(latlng.lat, strictBounds.getSouth()),
        strictBounds.getNorth(),
      ),
      Math.min(
        Math.max(latlng.lng, strictBounds.getWest()),
        strictBounds.getEast(),
      ),
    );
  }

  /* Reverse geocode → fill address field (same logic as registration) */
  function reverseGeocode(latlng, updateField) {
    setMapStatus("Resolving address…");
    fetch(
      "https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&lat=" +
        encodeURIComponent(latlng.lat) +
        "&lon=" +
        encodeURIComponent(latlng.lng),
      { headers: { Accept: "application/json" } },
    )
      .then(function (r) {
        return r.json();
      })
      .then(function (d) {
        var addr = d && d.display_name ? d.display_name : "";
        if (updateField && addr) {
          addressEl.value = addr;
        }
        setMapStatus(
          addr
            ? "Address updated from map pin."
            : "Location updated. Enter address manually.",
        );
      })
      .catch(function () {
        setMapStatus("Could not fetch address. Enter manually.");
      });
  }

  /* Forward geocode → move pin (triggered when address field loses focus) */
  function forwardGeocode(query) {
    setMapStatus("Matching typed address to map…");
    fetch(
      "https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&q=" +
        encodeURIComponent(query + ", Bocaue, Bulacan, Philippines"),
      { headers: { Accept: "application/json" } },
    )
      .then(function (r) {
        return r.json();
      })
      .then(function (data) {
        if (!data || !data.length) {
          setMapStatus("Address kept as manual entry.");
          return;
        }
        var latlng = keepInsideBounds(
          L.latLng(parseFloat(data[0].lat), parseFloat(data[0].lon)),
          auMap.options._strictBounds,
        );
        suppressMarkerMove = true;
        auMarker.setLatLng(latlng);
        updateLatLng(latlng);
        auMap.panTo(latlng);
        suppressMarkerMove = false;
        setMapStatus("Marker updated from typed address.");
      })
      .catch(function () {
        setMapStatus("Address updated manually.");
      });
  }

  function moveMarker(latlng, shouldReverse) {
    var safe = keepInsideBounds(latlng, auMap.options._strictBounds);
    auMarker.setLatLng(safe);
    updateLatLng(safe);
    if (shouldReverse) {
      reverseGeocode(safe, true);
    }
  }

  /* ── Build map (runs once, on first open) ── */
  function initMap() {
    if (auMap) {
      auMap.invalidateSize();
      return;
    }
    if (!window.L) {
      var s = document.createElement("script");
      s.src =
        "https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js";
      s.onload = buildMap;
      document.head.appendChild(s);
    } else {
      buildMap();
    }
  }

  function buildMap() {
    auMap = L.map("au_map", {
      zoomControl: true,
      minZoom: 13,
      maxZoom: 18,
      maxBoundsViscosity: 1.0,
    });

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 19,
      noWrap: true,
      attribution: "© OpenStreetMap contributors",
    }).addTo(auMap);

    var boundaryLayer = L.polygon(bocaueBoundary, {
      color: "#2a6fc0",
      weight: 2,
      fillColor: "#7fb4ef",
      fillOpacity: 0.15,
    }).addTo(auMap);

    var bounds = boundaryLayer.getBounds();
    var strictBounds = bounds.pad(0.02);
    auMap.options._strictBounds = strictBounds; // stash for helpers
    auMap.setMaxBounds(strictBounds);
    auMap.fitBounds(bounds, { padding: [14, 14], maxZoom: 14 });

    auMarker = L.marker([14.7995, 120.926], { draggable: true }).addTo(auMap);
    updateLatLng(auMarker.getLatLng());

    auMap.on("click", function (e) {
      moveMarker(e.latlng, true);
    });
    auMarker.on("dragend", function () {
      moveMarker(auMarker.getLatLng(), true);
    });
  }

  /* ── Barangay → pre-fill address AND pan map ── */
  var barangayCoords = {
    1: [14.8083, 120.906], // Antipona
    2: [14.7958, 120.9158], // Bagumbayan
    3: [14.8003, 120.9283], // Bambang
    4: [14.79, 120.905], // Batia
    5: [14.7836, 120.9497], // Biñang 1st
    6: [14.78, 120.955], // Biñang 2nd
    7: [14.82, 120.94], // Bolacan
    8: [14.81, 120.95], // Bundukan
    9: [14.795, 120.935], // Bunlo
    10: [14.785, 120.915], // Caingin
    11: [14.77, 120.925], // Duhat
    12: [14.805, 120.92], // Igulot
    13: [14.76, 120.945], // Lolomboy
    14: [14.7962, 120.926], // Poblacion
    15: [14.815, 120.91], // Sulucan
    16: [14.805, 120.935], // Taal
    17: [14.825, 120.925], // Tambobong
    18: [14.775, 120.935], // Turo
    19: [14.765, 120.915], // Wakas
  };

  barangayEl.addEventListener("change", function () {
    var selected = this.options[this.selectedIndex];
    var brgName = selected
      ? selected.getAttribute("data-name") || selected.text
      : "";
    var val = this.value;

    /* 1. Pre-fill address field */
    if (brgName) {
      addressEl.value = "Barangay " + brgName + ", Bocaue, Bulacan";
    }

    /* 2. Pan map to barangay center if map is ready */
    if (auMap && auMarker && barangayCoords[val]) {
      var latlng = L.latLng(barangayCoords[val][0], barangayCoords[val][1]);
      suppressMarkerMove = true;
      auMarker.setLatLng(latlng);
      updateLatLng(latlng);
      auMap.panTo(latlng);
      suppressMarkerMove = false;
      setMapStatus("Showing " + brgName + ". Drag pin for exact location.");
    }
  });

  /* ── Address blur → move pin (same as registration page) ── */
  addressEl.addEventListener("blur", function () {
    var val = this.value.trim();
    if (!val || suppressMarkerMove || !auMap) return;
    forwardGeocode(val);
  });

  /* ── GPS button ── */
  gpsBtn.addEventListener("click", function () {
    if (!navigator.geolocation) {
      setMapStatus("Geolocation not supported.");
      return;
    }
    setMapStatus("Detecting current location…");
    navigator.geolocation.getCurrentPosition(
      function (pos) {
        if (!auMap) return;
        var latlng = keepInsideBounds(
          L.latLng(pos.coords.latitude, pos.coords.longitude),
          auMap.options._strictBounds,
        );
        auMap.panTo(latlng);
        moveMarker(latlng, true);
      },
      function () {
        setMapStatus("Unable to get current location.");
      },
      { enableHighAccuracy: true, timeout: 10000 },
    );
  });

  /* ── Uppercase (typing, paste, autofill) ── */
  ["au_first_name", "au_last_name"].forEach(function (id) {
    var el = document.getElementById(id);
    if (!el) return;
    function forceUpper() {
      el.value = el.value.toUpperCase();
    }
    el.addEventListener("input", forceUpper);
    el.addEventListener("change", forceUpper);
    el.addEventListener("paste", function () {
      setTimeout(forceUpper, 0);
    });
  });

  /* ── Password toggle ── */
  document.querySelectorAll(".au-pw-toggle").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var t = document.getElementById(this.dataset.target);
      if (!t) return;
      var show = t.type === "password";
      t.type = show ? "text" : "password";
      this.textContent = show ? "HIDE" : "SHOW";
    });
  });

  /* ── Password match ── */
  function checkMatch() {
    if (!cpEl.value) {
      matchText.textContent = "";
      return;
    }
    var ok = pwEl.value === cpEl.value;
    matchText.textContent = ok ? "Passwords match." : "Passwords do not match.";
    matchText.style.color = ok ? "#0f7a33" : "#b91c1c";
  }
  pwEl.addEventListener("input", checkMatch);
  cpEl.addEventListener("input", checkMatch);

  /* ── Submit ── */
  form.addEventListener("submit", function (e) {
    e.preventDefault();
    errorBox.style.display = "none";

    var fn = document.getElementById("au_first_name").value.trim();
    var ln = document.getElementById("au_last_name").value.trim();
    var em = document.getElementById("au_email").value.trim();
    var brg = barangayEl.value;
    var rol = document.getElementById("au_role").value;
    var pw = pwEl.value;
    var cpw = cpEl.value;

    if (!fn || !ln) return showError("First and last name are required.");
    if (!em) return showError("Email address is required.");
    if (!brg) return showError("Please select a barangay.");
    if (!rol) return showError("Please select a role.");
    if (pw.length < 8)
      return showError("Password must be at least 8 characters.");
    if (pw !== cpw) return showError("Passwords do not match.");

    submitBtn.disabled = true;
    submitBtn.innerHTML = "Saving…";

    var fd = new FormData(form);
    fd.append("action", "add_user_lgu");

    fetch("../api/handle_add_user.php", { method: "POST", body: fd })
      .then(function (r) {
        return r.json();
      })
      .then(function (res) {
        submitBtn.disabled = false;
        submitBtn.innerHTML =
          '<span class="material-symbols-outlined" style="font-size:16px;">person_add</span> Add User';
        if (res.success) {
          closeModal();
          if (typeof showToast === "function")
            showToast("User added successfully!");
          if (typeof loadUsers === "function") loadUsers();
          else location.reload();
        } else {
          showError(res.message || "Failed to add user. Try again.");
        }
      })
      .catch(function () {
        submitBtn.disabled = false;
        submitBtn.innerHTML =
          '<span class="material-symbols-outlined" style="font-size:16px;">person_add</span> Add User';
        showError("Network error. Please try again.");
      });
  });

  function showError(msg) {
    errorBox.textContent = msg;
    errorBox.style.display = "block";
    errorBox.scrollIntoView({ behavior: "smooth", block: "nearest" });
  }
})();
