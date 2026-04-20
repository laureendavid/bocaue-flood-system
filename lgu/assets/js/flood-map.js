/* ================================================================
   FLOOD MONITORING MAP — lgu/assets/js/flood-map.js
   ================================================================ */

let floodMap = null;
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

function pointInsideBocaue(lat, lng) {
  const x = lng;
  const y = lat;
  let inside = false;
  for (let i = 0, j = BOCAUE_POLYGON.length - 1; i < BOCAUE_POLYGON.length; j = i++) {
    const yi = BOCAUE_POLYGON[i][0];
    const xi = BOCAUE_POLYGON[i][1];
    const yj = BOCAUE_POLYGON[j][0];
    const xj = BOCAUE_POLYGON[j][1];
    const intersects = (yi > y) !== (yj > y)
      && x < ((xj - xi) * (y - yi)) / (yj - yi + Number.EPSILON) + xi;
    if (intersects) inside = !inside;
  }
  return inside;
}

function applyBoundaryLayer(map) {
  const worldRing = [[-90, -180], [-90, 180], [90, 180], [90, -180]];

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

function addCurrentLocationControl(map) {
  let locationMarker = null;
  const control = L.control({ position: "topright" });

  control.onAdd = function onAdd() {
    const button = L.DomUtil.create("button", "leaflet-bar");
    button.type = "button";
    button.textContent = "Use My Current Location";
    button.style.background = "#fff";
    button.style.border = "none";
    button.style.padding = "8px 10px";
    button.style.fontSize = "12px";
    button.style.fontWeight = "600";
    button.style.cursor = "pointer";
    button.style.minWidth = "168px";
    button.style.borderRadius = "6px";

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

          if (locationMarker) {
            locationMarker.setLatLng([lat, lng]);
          } else {
            locationMarker = L.marker([lat, lng]).addTo(map);
          }
          locationMarker.bindPopup("Your current location").openPopup();
          map.flyTo([lat, lng], 16, { duration: 0.7 });
        },
        () => {
          alert("Unable to get your current location.");
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 },
      );
    });

    return button;
  };

  control.addTo(map);
}

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

  floodMap.invalidateSize();
}

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
