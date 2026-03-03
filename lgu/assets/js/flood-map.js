/* ================================================================
   FLOOD MONITORING MAP — lgu/assets/js/flood-map.js
   ================================================================ */

let floodMap = null;

function initFloodMap() {
  const mapDiv = document.getElementById("flood-map");
  if (!mapDiv) return;
  if (floodMap) {
    floodMap.invalidateSize();
    return;
  }

  const bocaueBounds = L.latLngBounds(
    [14.77, 120.91], // southwest
    [14.84, 120.99], // northeast
  );

  floodMap = L.map("flood-map", {
    center: [14.805, 120.95],
    zoom: 14,
    minZoom: 14, // prevents zooming out to see other places
    maxZoom: 19,
    maxBounds: bocaueBounds,
    maxBoundsViscosity: 1.0, // hard lock, can't pan outside
  });

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution:
      '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 19,
  }).addTo(floodMap);

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
