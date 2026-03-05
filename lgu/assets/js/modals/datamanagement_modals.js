/* ================================================================
   MODALS & LEAFLET MAP — lgu/assets/js/modals.js
   ================================================================ */

function openModal(id) {
  const modal = document.getElementById(id);
  if (!modal) return;
  modal.classList.add("open");
  document.body.style.overflow = "hidden";
  if (id === "modal-center") setTimeout(initCenterMap, 100);
}

function closeModal(id) {
  const modal = document.getElementById(id);
  if (!modal) return;
  modal.classList.remove("open");
  document.body.style.overflow = "";
}

let centerMap = null;
let centerMarker = null;

const bocaueBounds = L.latLngBounds([14.77, 120.91], [14.84, 120.99]);

function initCenterMap() {
  if (centerMap) {
    centerMap.invalidateSize();
    return;
  }

  centerMap = L.map("center-map", {
    center: [14.805, 120.95],
    zoom: 14,
    minZoom: 13,
    maxZoom: 19,
    maxBounds: bocaueBounds,
    maxBoundsViscosity: 1.0,
  });

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution:
      '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 19,
  }).addTo(centerMap);

  // ===== USE MY LOCATION BUTTON =====
  const LocateControl = L.Control.extend({
    options: { position: "topleft" },
    onAdd: function () {
      const btn = L.DomUtil.create("button");
      btn.innerHTML = `<span class="material-symbols-outlined" style="font-size:17px;vertical-align:middle">my_location</span> Use My Location`;
      btn.style.cssText = `
        display:flex; align-items:center; gap:6px; padding:7px 12px;
        background:white; border:1.5px solid #e2e8f0; border-radius:8px;
        font-size:0.82rem; font-weight:600; font-family:Inter,sans-serif;
        cursor:pointer; color:#1e293b; box-shadow:0 2px 8px rgba(0,0,0,0.12);
        white-space:nowrap; margin-top:8px;
      `;

      L.DomEvent.disableClickPropagation(btn);

      btn.addEventListener("click", function () {
        btn.innerHTML = `<span class="material-symbols-outlined" style="font-size:17px;vertical-align:middle">sync</span> Locating...`;
        btn.disabled = true;
        btn.style.opacity = "0.7";

        if (!navigator.geolocation) {
          alert("Geolocation is not supported by your browser.");
          resetBtn();
          return;
        }

        navigator.geolocation.getCurrentPosition(
          function (pos) {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            const latlng = L.latLng(lat, lng);

            if (!bocaueBounds.contains(latlng)) {
              alert(
                "Your location is outside Bocaue, Bulacan. Please pin the location manually on the map.",
              );
              centerMap.setView([14.805, 120.95], 14);
              resetBtn();
              return;
            }

            centerMap.setView(latlng, 17);
            placeMarker(lat, lng);
            resetBtn();
          },
          function (err) {
            let msg = "Could not get your location.";
            if (err.code === 1)
              msg =
                "Location access denied. Please allow location permission in your browser.";
            if (err.code === 2)
              msg = "Location unavailable. Please pin manually on the map.";
            if (err.code === 3)
              msg = "Location request timed out. Please try again.";
            alert(msg);
            resetBtn();
          },
          { enableHighAccuracy: true, timeout: 10000 },
        );
      });

      function resetBtn() {
        btn.innerHTML = `<span class="material-symbols-outlined" style="font-size:17px;vertical-align:middle">my_location</span> Use My Location`;
        btn.disabled = false;
        btn.style.opacity = "1";
      }

      return btn;
    },
  });

  new LocateControl().addTo(centerMap);

  // ===== CLICK TO PIN =====
  centerMap.on("click", function (e) {
    placeMarker(e.latlng.lat, e.latlng.lng);
  });
}

function placeMarker(lat, lng) {
  const latlng = L.latLng(lat, lng);
  if (centerMarker) {
    centerMarker.setLatLng(latlng);
  } else {
    centerMarker = L.marker(latlng, { draggable: true }).addTo(centerMap);
    centerMarker.on("dragend", function () {
      const pos = centerMarker.getLatLng();
      updateCoords(pos.lat, pos.lng);
      updateDisplay(pos.lat, pos.lng);
    });
  }
  updateCoords(lat, lng);
  updateDisplay(lat, lng);
}

function updateCoords(lat, lng) {
  document.getElementById("center-lat").value = lat.toFixed(7);
  document.getElementById("center-lng").value = lng.toFixed(7);
}

function updateDisplay(lat, lng) {
  // 1. Update the coords bar
  const coordsDisplay = document.getElementById("map-coords-display");
  if (coordsDisplay) {
    coordsDisplay.innerHTML = `
      <span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle">location_on</span>
      Lat: ${lat.toFixed(7)}, Lng: ${lng.toFixed(7)}
    `;
  }

  // 2. Show the address field and set loading state
  const nameGroup = document.getElementById("location-name-group");
  const nameInput = document.getElementById("map-location-name");
  if (nameGroup) nameGroup.style.display = "block";
  if (nameInput) {
    nameInput.value = "Fetching address...";
    nameInput.style.color = "#94a3b8";
  }

  // 3. Fallback hidden value
  document.getElementById("center-address").value =
    `${lat.toFixed(7)}, ${lng.toFixed(7)}`;

  // 4. Reverse geocode with Nominatim (clean address format)
  fetch(
    `https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json&addressdetails=1`,
  )
    .then((res) => res.json())
    .then((data) => {
      const a = data.address || {};

      // Build a clean readable address from the parts we care about
      const parts = [
        a.house_number && a.road ? `${a.house_number} ${a.road}` : a.road,
        a.suburb || a.village || a.neighbourhood || a.hamlet,
        a.town || a.city || a.municipality,
        a.province || a.state,
      ].filter(Boolean); // remove undefined/null/empty parts

      const locationName =
        parts.length > 0
          ? parts.join(", ")
          : `${lat.toFixed(7)}, ${lng.toFixed(7)}`;

      if (nameInput) {
        nameInput.value = locationName;
        nameInput.style.color = "#1e293b";
      }
      document.getElementById("center-address").value = locationName;
    })
    .catch(() => {
      if (nameInput) {
        nameInput.value = "";
        nameInput.style.color = "#1e293b";
      }
    });
}

/* ===== LOCATION NAME MANUAL EDIT SYNC ===== */
document.addEventListener("DOMContentLoaded", () => {
  const nameInput = document.getElementById("map-location-name");
  if (nameInput) {
    nameInput.addEventListener("input", function () {
      document.getElementById("center-address").value = this.value;
    });
  }
});

/* ===== INIT ===== */
document.addEventListener("DOMContentLoaded", () => {
  const btnHotline = document.getElementById("btn-add-hotline");
  if (btnHotline)
    btnHotline.addEventListener("click", () => openModal("modal-hotline"));

  const btnCenter = document.getElementById("btn-add-center");
  if (btnCenter)
    btnCenter.addEventListener("click", () => openModal("modal-center"));

  const btnAnnouncement = document.getElementById("btn-add-announcement");
  if (btnAnnouncement)
    btnAnnouncement.addEventListener("click", () =>
      openModal("modal-announcement"),
    );

  document.querySelectorAll(".modal-close").forEach((btn) => {
    btn.addEventListener("click", function () {
      closeModal(this.dataset.modal);
    });
  });

  document.querySelectorAll(".modal-overlay").forEach((overlay) => {
    overlay.addEventListener("click", function (e) {
      if (e.target === this) closeModal(this.id);
    });
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      document
        .querySelectorAll(".modal-overlay.open")
        .forEach((m) => closeModal(m.id));
    }
  });
});

//end of datamanagement_modals
