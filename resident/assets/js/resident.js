/* ===================================================================
   resident.js — Resident Dashboard
   Only dashboard (home.php) is active. Other page functions
   will be added when those section files are built.
   =================================================================== */

/* ===========================================================
   SIDEBAR TOGGLE
   =========================================================== */
function openSidebar() {
  const sidebar = document.getElementById("sidebar");
  const overlay = document.getElementById("sidebar-overlay");
  if (!sidebar || !overlay) return;
  sidebar.classList.add("open");
  overlay.classList.add("visible");
  document.body.style.overflow = "hidden";
}

function closeSidebar() {
  const sidebar = document.getElementById("sidebar");
  const overlay = document.getElementById("sidebar-overlay");
  if (!sidebar || !overlay) return;
  sidebar.classList.remove("open");
  overlay.classList.remove("visible");
  document.body.style.overflow = "";
}

/* ===========================================================
   THEME TOGGLE
   =========================================================== */
function initTheme() {
  const saved = localStorage.getItem("resident-theme");
  if (saved === "dark") document.documentElement.classList.add("dark");
  updateThemeIcon();
}

function toggleTheme() {
  document.documentElement.classList.toggle("dark");
  const isDark = document.documentElement.classList.contains("dark");
  localStorage.setItem("resident-theme", isDark ? "dark" : "light");
  updateThemeIcon();
}

function updateThemeIcon() {
  const btn = document.getElementById("theme-toggle");
  if (!btn) return;
  const icon = btn.querySelector(".material-symbols-outlined");
  const isDark = document.documentElement.classList.contains("dark");
  if (icon) icon.textContent = isDark ? "light_mode" : "dark_mode";
}

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

const BOCAUE_CENTER = [14.7982, 120.926];

function getBocaueBounds() {
  if (typeof L === "undefined" || typeof L.latLngBounds !== "function") {
    return null;
  }
  return L.latLngBounds([14.747, 120.865], [14.845, 120.99]);
}
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

function isPointInsideBocaue(lat, lng) {
  const x = lng;
  const y = lat;
  let inside = false;
  for (
    let i = 0, j = BOCAUE_POLYGON.length - 1;
    i < BOCAUE_POLYGON.length;
    j = i++
  ) {
    const yi = BOCAUE_POLYGON[i][0];
    const xi = BOCAUE_POLYGON[i][1];
    const yj = BOCAUE_POLYGON[j][0];
    const xj = BOCAUE_POLYGON[j][1];
    const intersect =
      yi > y !== yj > y &&
      x < ((xj - xi) * (y - yi)) / (yj - yi + Number.EPSILON) + xi;
    if (intersect) inside = !inside;
  }
  return inside;
}

function applyBocaueBoundaryMask(map) {
  if (!map || typeof L === "undefined") return;

  const worldRing = [
    [-90, -180],
    [-90, 180],
    [90, 180],
    [90, -180],
  ];

  L.polygon([worldRing, BOCAUE_POLYGON], {
    stroke: false,
    fillColor: "#0b1f3b",
    fillOpacity: 0.42,
    interactive: false,
  }).addTo(map);

  L.polygon(BOCAUE_POLYGON, {
    color: "#2563eb",
    weight: 2,
    fillOpacity: 0.02,
    dashArray: "5, 5",
    interactive: false,
  }).addTo(map);

  const bounds = getBocaueBounds();
  if (bounds) {
    map.setMaxBounds(bounds);
  }
}

function createBocaueLeafletMap(elementId, extraOptions) {
  const bounds = getBocaueBounds();
  const map = L.map(elementId, Object.assign(
    {
      center: BOCAUE_CENTER,
      zoom: 14,
      minZoom: 13,
      maxZoom: 19,
      maxBounds: bounds,
      maxBoundsViscosity: 1.0,
    },
    extraOptions || {},
  ));

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution:
      '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 19,
  }).addTo(map);

  applyBocaueBoundaryMask(map);
  return map;
}

function getBocauePolygonLatLngBounds() {
  if (typeof L === "undefined" || typeof L.latLngBounds !== "function") {
    return null;
  }
  return L.latLngBounds(
    BOCAUE_POLYGON.map(function (point) {
      return [point[0], point[1]];
    }),
  );
}

function fitMapToBocaueBoundary(map, padding) {
  if (!map) return;
  const bounds = getBocauePolygonLatLngBounds();
  if (!bounds) return;
  map.fitBounds(bounds, {
    padding: padding || [32, 32],
    maxZoom: 14,
  });
}

function addUseCurrentLocationButton(map, onLocationFound) {
  if (!map || typeof L === "undefined") return;

  const control = L.control({ position: "topright" });
  control.onAdd = function onAdd() {
    const btn = L.DomUtil.create("button", "leaflet-bar use-location-btn");
    btn.type = "button";
    btn.textContent = "Use My Current Location";
    btn.style.background = "#fff";
    btn.style.border = "none";
    btn.style.padding = "8px 10px";
    btn.style.fontSize = "12px";
    btn.style.fontWeight = "600";
    btn.style.cursor = "pointer";
    btn.style.minWidth = "168px";
    btn.style.borderRadius = "6px";

    L.DomEvent.disableClickPropagation(btn);
    L.DomEvent.on(btn, "click", function onClick() {
      if (!navigator.geolocation) {
        alert("Geolocation is not supported on this browser.");
        return;
      }

      navigator.geolocation.getCurrentPosition(
        function success(position) {
          const lat = position.coords.latitude;
          const lng = position.coords.longitude;

          if (!isPointInsideBocaue(lat, lng)) {
            alert("You are outside Bocaue, Bulacan coverage area.");
            return;
          }

          onLocationFound(lat, lng);
        },
        function error() {
          alert("Unable to get your current location.");
        },
        {
          enableHighAccuracy: true,
          timeout: 10000,
          maximumAge: 0,
        },
      );
    });

    return btn;
  };

  control.addTo(map);
}

const BOCAUE_VIEWBOX = "120.865,14.845,120.99,14.747";

function formatNominatimLabel(result) {
  if (!result) return "";
  const title = (result.name || result.display_name || "").split(",")[0].trim();
  const display = (result.display_name || "")
    .split(",")
    .slice(0, 4)
    .join(",")
    .trim();
  if (
    title &&
    display &&
    !display.toLowerCase().startsWith(title.toLowerCase())
  ) {
    return `${title} - ${display}`;
  }
  return display || title;
}

function formatReverseAddress(data, fallbackLat, fallbackLng) {
  if (!data) return `${fallbackLat.toFixed(6)}, ${fallbackLng.toFixed(6)}`;
  const address = data.address || {};
  const primary =
    address.amenity ||
    address.tourism ||
    address.building ||
    address.shop ||
    address.leisure ||
    address.attraction ||
    address.hotel ||
    address.resort ||
    data.name ||
    "";
  const road =
    address.road || address.pedestrian || address.footway || address.path || "";
  const locality =
    address.suburb ||
    address.neighbourhood ||
    address.quarter ||
    address.hamlet ||
    address.village ||
    "";
  const city = address.city || address.town || address.municipality || "Bocaue";
  const province = address.province || address.state || "Bulacan";

  const parts = [primary, road, locality, city, province]
    .map((part) => String(part || "").trim())
    .filter(Boolean);

  if (parts.length > 0) {
    return [...new Set(parts)].join(", ");
  }

  if (data.display_name) {
    return data.display_name.split(",").slice(0, 5).join(",").trim();
  }

  return `${fallbackLat.toFixed(6)}, ${fallbackLng.toFixed(6)}`;
}

async function searchPlacesInBocaue(query, limit = 5) {
  const endpoint = `https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&namedetails=1&countrycodes=ph&bounded=1&viewbox=${encodeURIComponent(BOCAUE_VIEWBOX)}&limit=${limit}&q=${encodeURIComponent(query)}`;
  const response = await fetch(endpoint, {
    headers: {
      Accept: "application/json",
      "Accept-Language": "en",
    },
    cache: "no-store",
  });
  if (!response.ok) {
    throw new Error("Search service unavailable.");
  }
  const rows = await response.json();
  return (rows || []).filter((row) => {
    const lat = Number.parseFloat(row.lat);
    const lon = Number.parseFloat(row.lon);
    return (
      Number.isFinite(lat) &&
      Number.isFinite(lon) &&
      isPointInsideBocaue(lat, lon)
    );
  });
}

async function geocodeAddressQuery(address) {
  const query = String(address || "").trim();
  if (!query) {
    return null;
  }

  const endpoint =
    "https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&countrycodes=ph&q=" +
    encodeURIComponent(query);
  const response = await fetch(endpoint, {
    headers: {
      Accept: "application/json",
      "Accept-Language": "en",
    },
    cache: "no-store",
  });

  if (!response.ok) {
    return null;
  }

  const rows = await response.json();
  const row = rows && rows[0];
  if (!row) {
    return null;
  }

  const lat = Number.parseFloat(row.lat);
  const lon = Number.parseFloat(row.lon);
  if (!Number.isFinite(lat) || !Number.isFinite(lon)) {
    return null;
  }

  return [lat, lon];
}

async function reverseGeocodeInBocaue(lat, lng) {
  const endpoint = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&namedetails=1&zoom=18&lat=${lat.toFixed(7)}&lon=${lng.toFixed(7)}`;
  const response = await fetch(endpoint, {
    headers: {
      Accept: "application/json",
      "Accept-Language": "en",
    },
    cache: "no-store",
  });
  if (!response.ok) {
    throw new Error("Reverse geocoding unavailable.");
  }
  const payload = await response.json();
  return formatReverseAddress(payload, lat, lng);
}

/* ===========================================================
  LOGOUT PROMPT
  =========================================================== */
function confirmLogout() {
  const shouldLogout = window.confirm("Are you sure you want to logout?");
  if (shouldLogout) {
    window.location.href = "../main/logout.php";
  }
}

/* ===========================================================
   BADGE + PROGRESS HELPERS
   =========================================================== */
function getOccupancyBadge(current, max) {
  const pct = max > 0 ? (current / max) * 100 : 0;
  if (pct >= 100) return { cls: "badge-full", label: "Full" };
  if (pct >= 75) return { cls: "badge-nearfull", label: "Near Full" };
  return { cls: "badge-available", label: "Available" };
}

function getProgressBarClass(current, max) {
  const pct = max > 0 ? (current / max) * 100 : 0;
  if (pct >= 100) return "full";
  if (pct >= 75) return "nearfull";
  return "available";
}

/* ===========================================================
   DASHBOARD — SAFETY CENTERS (right column widget)
   =========================================================== */
function renderDashboardSafetyCenters(centers) {
  const list = document.getElementById("dash-safety-list");
  if (!list) return;
  if (!centers || centers.length === 0) {
    list.innerHTML =
      '<li class="empty-state-inline">No safety centers available.</li>';
    return;
  }
  list.innerHTML = centers
    .map((c) => {
      const pct = c.max > 0 ? Math.min((c.current / c.max) * 100, 100) : 0;
      const badge = getOccupancyBadge(c.current, c.max);
      const barCls = getProgressBarClass(c.current, c.max);
      return `
    <li class="safety-center-item">
      <div class="safety-center-header">
        <span class="safety-center-name">${escHtml(c.name)}</span>
        <span class="badge ${badge.cls}">${badge.label}</span>
      </div>
      <div class="progress-bar-wrap">
        <div class="progress-bar ${barCls}" style="width:${pct.toFixed(1)}%"></div>
      </div>
    </li>`;
    })
    .join("");
}

/* ===========================================================
   DASHBOARD — COMMUNITY FEED
   =========================================================== */
function renderCommunityFeed(posts) {
  const feed = document.getElementById("community-feed");
  if (!feed) return;
  if (!posts || posts.length === 0) {
    feed.innerHTML =
      '<div class="feed-card" style="padding:32px;text-align:center;color:var(--text-muted);font-style:italic;">No posts yet.</div>';
    return;
  }

  const badgeMap = {
    rescue: { cls: "badge-rescue", label: "Rescue Needed" },
    lgu: { cls: "badge-lgu", label: "Official" },
    rescued: { cls: "badge-rescued", label: "Rescued" },
    inprogress: { cls: "badge-inprogress", label: "In Progress" },
  };

  feed.innerHTML = posts
    .map((p) => {
      const badge = badgeMap[p.status] || badgeMap.rescue;
      const avatarHtml = p.avatarUrl
        ? `<img src="${escHtml(p.avatarUrl)}" alt="${escHtml(p.userName)}" style="width:100%;height:100%;object-fit:cover;">`
        : `<span class="material-symbols-outlined">person</span>`;
      const bodyHtml = p.bodyText
        ? `<p>${escHtml(p.bodyText)}</p>`
        : `<div class="skeleton-line long"></div>
         <div class="skeleton-line medium"></div>
         <div class="skeleton-line long"></div>
         <div class="skeleton-line short"></div>`;

      return `
    <article class="feed-card">
      <div class="feed-header">
        <div class="feed-user">
          <div class="avatar">${avatarHtml}</div>
          <div>
            <div class="feed-name">${escHtml(p.userName)}</div>
            <div class="feed-date">${escHtml(p.userDate)}</div>
          </div>
        </div>
        <span class="${badge.cls}">${badge.label}</span>
      </div>
      <div class="feed-body">${bodyHtml}</div>
      <div class="feed-tags">
        ${p.location ? `<span class="feed-tag"><span class="material-symbols-outlined">location_on</span>${escHtml(p.location)}</span>` : ""}
        ${p.severity ? `<span class="feed-tag"><span class="dot dot-red"></span>${escHtml(p.severity)}</span>` : ""}
        ${p.waterLevel ? `<span class="feed-tag"><span class="dot dot-orange"></span>${escHtml(p.waterLevel)}</span>` : ""}
      </div>
      <div class="feed-actions">
        <button class="feed-action"><span class="material-symbols-outlined">verified</span>Trusted Report</button>
        <button class="feed-action"><span class="material-symbols-outlined">chat_bubble</span>Comment</button>
        <button class="feed-action"><span class="material-symbols-outlined">repeat</span>Repost</button>
      </div>
    </article>`;
    })
    .join("");
}

/* ===========================================================
   UTILITY
   =========================================================== */
function escHtml(str) {
  if (str == null) return "";
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

/* ===========================================================
   DATA LOADING
   =========================================================== */
async function loadDashboardSafetyCenters() {
  // Safety center cards are server-rendered in dashboard.php.
  // Keeping this function as a no-op avoids overriding real DB data.
  return;
}

async function loadCommunityFeed() {
  // Replace with: const res = await fetch('api/get_community_feed.php');
  // renderCommunityFeed(await res.json());

  // Static preview data until API is ready:
  renderCommunityFeed([
    {
      userName: "Mary Jane",
      userDate: "October 15, 2025 at 3:30PM",
      status: "rescue",
      bodyText: null,
      location: "Bocaue, Bulacan",
      severity: "High-Level Flood",
      waterLevel: "Waist-Level",
    },
    {
      userName: "LGU",
      userDate: "October 15, 2025 at 7:30PM",
      status: "lgu",
      bodyText: null,
      location: "Bocaue, Bulacan",
      severity: "Critical Alert",
      waterLevel: null,
    },
  ]);
}

async function loadBocaueWeather() {
  const conditionTextEl = document.getElementById("weather-condition-text");
  const weatherIconEl = document.getElementById("weather-icon");
  const weatherTimeEl = document.getElementById("weather-time");
  const weatherTempEl = document.getElementById("weather-temp");
  const weatherRangeEl = document.getElementById("weather-range");
  const weatherHumidityEl = document.getElementById("weather-humidity");
  const weatherWindEl = document.getElementById("weather-wind");
  const weatherRainEl = document.getElementById("weather-rain");
  const weatherForecastEl = document.getElementById("weather-forecast");
  const weatherErrorEl = document.getElementById("weather-error");

  if (!conditionTextEl || !weatherIconEl || !weatherTimeEl || !weatherTempEl) {
    return;
  }

  const weatherMap = {
    0: { label: "Clear Sky", icon: "wb_sunny" },
    1: { label: "Mainly Clear", icon: "light_mode" },
    2: { label: "Partly Cloudy", icon: "partly_cloudy_day" },
    3: { label: "Overcast", icon: "cloud" },
    45: { label: "Foggy", icon: "foggy" },
    48: { label: "Foggy", icon: "foggy" },
    51: { label: "Light Drizzle", icon: "grain" },
    53: { label: "Drizzle", icon: "rainy" },
    55: { label: "Heavy Drizzle", icon: "rainy_heavy" },
    61: { label: "Light Rain", icon: "rainy" },
    63: { label: "Rain", icon: "rainy" },
    65: { label: "Heavy Rain", icon: "rainy_heavy" },
    80: { label: "Rain Showers", icon: "rainy" },
    81: { label: "Heavy Showers", icon: "rainy_heavy" },
    82: { label: "Violent Rain", icon: "rainy_heavy" },
    95: { label: "Thunderstorm", icon: "thunderstorm" },
    96: { label: "Thunderstorm", icon: "thunderstorm" },
    99: { label: "Thunderstorm", icon: "thunderstorm" },
  };

  function getWeatherMeta(code) {
    return weatherMap[code] || { label: "Weather Update", icon: "cloud" };
  }

  function formatToday() {
    const now = new Date();
    const time = now.toLocaleTimeString([], {
      hour: "2-digit",
      minute: "2-digit",
    });
    const date = now.toLocaleDateString([], { month: "short", day: "2-digit" });
    return `${time}\n${date}`;
  }

  try {
    if (weatherErrorEl) {
      weatherErrorEl.style.display = "none";
      weatherErrorEl.textContent = "";
    }

    const endpoint =
      "https://api.open-meteo.com/v1/forecast?latitude=14.7982&longitude=120.9260&current=temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m,precipitation_probability&daily=weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max&timezone=Asia%2FManila&forecast_days=5";
    const response = await fetch(endpoint, {
      headers: { Accept: "application/json" },
    });
    if (!response.ok) {
      throw new Error("Weather service unavailable.");
    }

    const payload = await response.json();
    const current = payload.current || {};
    const daily = payload.daily || {};
    const weather = getWeatherMeta(current.weather_code);

    conditionTextEl.textContent = weather.label;
    weatherIconEl.textContent = weather.icon;
    weatherTimeEl.textContent = formatToday();
    weatherTempEl.textContent = Number.isFinite(current.temperature_2m)
      ? Math.round(current.temperature_2m)
      : "--";
    if (weatherRangeEl) {
      const high = Array.isArray(daily.temperature_2m_max)
        ? daily.temperature_2m_max[0]
        : null;
      const low = Array.isArray(daily.temperature_2m_min)
        ? daily.temperature_2m_min[0]
        : null;
      weatherRangeEl.textContent = `H: ${high != null ? Math.round(high) : "--"}°C / L: ${low != null ? Math.round(low) : "--"}°C`;
    }

    if (weatherHumidityEl) {
      weatherHumidityEl.textContent = `${current.relative_humidity_2m ?? "--"}%`;
    }
    if (weatherWindEl) {
      weatherWindEl.textContent = `${current.wind_speed_10m != null ? Math.round(current.wind_speed_10m) : "--"} km/h`;
    }
    if (weatherRainEl) {
      weatherRainEl.textContent = `${current.precipitation_probability ?? "--"}%`;
    }

    if (weatherForecastEl && Array.isArray(daily.time)) {
      weatherForecastEl.innerHTML = daily.time
        .slice(0, 5)
        .map((dateStr, idx) => {
          const code = Array.isArray(daily.weather_code)
            ? daily.weather_code[idx]
            : null;
          const meta = getWeatherMeta(code);
          const label = new Date(dateStr)
            .toLocaleDateString([], { weekday: "short" })
            .toUpperCase();
          return `
          <div class="forecast-day">
            <div class="day-label">${label}</div>
            <span class="material-symbols-outlined">${meta.icon}</span>
          </div>
        `;
        })
        .join("");
    }
  } catch (error) {
    conditionTextEl.textContent = "Weather currently unavailable";
    weatherIconEl.textContent = "cloud_off";
    if (weatherErrorEl) {
      weatherErrorEl.style.display = "block";
      weatherErrorEl.textContent =
        error.message || "Failed to load weather data.";
    }
  }
}

/* ===========================================================
   INIT
   =========================================================== */
document.addEventListener("DOMContentLoaded", () => {
  /* Logout prompt — sidebar button */
  const logoutSidebarBtn = document.getElementById("logout-trigger-btn");
  if (logoutSidebarBtn)
    logoutSidebarBtn.addEventListener("click", confirmLogout);

  /* Notification dropdown */
  const notificationBtn = document.getElementById("notification-btn");
  const notificationDropdown = document.getElementById("notification-dropdown");
  const notificationList = document.getElementById("notification-list");
  const notificationBadge = document.getElementById("notification-badge");
  const markReadBtn = document.getElementById("mark-read-btn");
  let notificationsCache = [];
  let notificationOffset = 0;
  let notificationsHasMore = true;
  let notificationsLoading = false;
  let currentUnreadCount = 0;
  const notificationPageSize = 20;
  const seenNotificationIds = new Set();

  function dedupeNotificationsById(items) {
    if (!Array.isArray(items)) return [];

    const unique = [];
    items.forEach((item) => {
      const id = String(item?.id ?? "");
      if (!id || seenNotificationIds.has(id)) {
        return;
      }
      seenNotificationIds.add(id);
      unique.push(item);
    });

    return unique;
  }

  function resetNotificationState() {
    notificationOffset = 0;
    notificationsHasMore = true;
    notificationsCache = [];
    seenNotificationIds.clear();
  }

  function formatNotificationTime(dateStr) {
    if (!dateStr) return "";
    const dt = new Date(dateStr.replace(" ", "T"));
    if (Number.isNaN(dt.getTime())) return dateStr;
    return dt.toLocaleString([], {
      month: "short",
      day: "2-digit",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  function renderNotifications(items, append = false) {
    if (!notificationList) return;

    if (!append && (!items || items.length === 0)) {
      notificationList.innerHTML =
        '<div class="notification-empty">No notifications yet.</div>';
      return;
    }

    const html = items
      .map((item) => {
        const isUnread = String(item.status || "").toLowerCase() === "unread";
        const type = String(item.type || "alert").toLowerCase();
        const typeLabelMap = {
          report_update: "Report Update",
          approved: "Approved",
          rejected: "Rejected",
          announcement: "Announcement",
          alert: "Alert",
        };
        const typeLabel = typeLabelMap[type] || "Notification";
        return `
          <button
            type="button"
            class="notification-item ${isUnread ? "unread" : ""}"
            data-notification-id="${escapeHtml(String(item.id || ""))}"
            data-notification-read="${isUnread ? "0" : "1"}"
          >
            <div class="notification-title">${escapeHtml(item.title || typeLabel)}</div>
            <div class="notification-message">${escapeHtml(item.message || "")}</div>
            <div class="notification-meta">
              <span class="notification-type type-${escapeHtml(type)}">${escapeHtml(typeLabel)}</span>
            </div>
            <div class="notification-meta">
              <span class="notification-author">${escapeHtml(item.created_by || "Bocaue LGU")}</span>
              <span class="notification-time">${formatNotificationTime(item.created_at || "")}</span>
            </div>
          </button>
        `;
      })
      .join("");

    if (append) {
      const empty = notificationList.querySelector(".notification-empty");
      if (empty) {
        empty.remove();
      }
      notificationList.insertAdjacentHTML("beforeend", html);
    } else {
      notificationList.innerHTML = html;
    }
  }

  function updateNotificationBadge(unreadCount) {
    if (!notificationBadge) return;
    const count = Number(unreadCount || 0);
    if (count > 0) {
      notificationBadge.style.display = "inline-block";
      notificationBadge.textContent = count > 99 ? "99+" : String(count);
    } else {
      notificationBadge.style.display = "none";
      notificationBadge.textContent = "0";
    }
  }

  function renderNotificationLoadingState() {
    if (!notificationList) return;
    const existing = notificationList.querySelector(".notification-loading");
    if (!existing) {
      notificationList.insertAdjacentHTML(
        "beforeend",
        '<div class="notification-loading">Loading notifications...</div>',
      );
    }
  }

  function clearNotificationLoadingState() {
    if (!notificationList) return;
    const loadingNode = notificationList.querySelector(".notification-loading");
    if (loadingNode) {
      loadingNode.remove();
    }
  }

  function renderNotificationEndState() {
    if (!notificationList) return;
    const hasEndNode = notificationList.querySelector(".notification-end");
    if (!notificationsHasMore && notificationsCache.length > 0 && !hasEndNode) {
      notificationList.insertAdjacentHTML(
        "beforeend",
        '<div class="notification-end">You are all caught up.</div>',
      );
    }
  }

  async function loadNotifications(reset = false) {
    if (!notificationList) return;
    if (notificationsLoading) return;

    if (reset) {
      resetNotificationState();
      notificationList.innerHTML =
        '<div class="notification-loading">Loading notifications...</div>';
    } else if (!notificationsHasMore) {
      return;
    } else {
      renderNotificationLoadingState();
    }

    notificationsLoading = true;
    try {
      const query = new URLSearchParams({
        limit: String(notificationPageSize),
        offset: String(notificationOffset),
      });
      const response = await fetch(
        `../includes/fetch_notifications.php?${query.toString()}`,
        {
          headers: { Accept: "application/json" },
        },
      );
      const data = await response.json();
      if (!response.ok || !data.success) {
        throw new Error(data.message || "Unable to load notifications.");
      }
      const incoming = dedupeNotificationsById(
        Array.isArray(data.notifications) ? data.notifications : [],
      );
      notificationsHasMore = Boolean(data.has_more);
      notificationOffset += Array.isArray(data.notifications)
        ? data.notifications.length
        : 0;
      notificationsCache = reset
        ? incoming
        : notificationsCache.concat(incoming);
      clearNotificationLoadingState();
      renderNotifications(incoming, !reset);
      renderNotificationEndState();
      currentUnreadCount = Number(data.unread_count || 0);
      updateNotificationBadge(currentUnreadCount);
    } catch (error) {
      if (reset) {
        notificationList.innerHTML = `<div class="notification-empty">${escapeHtml(error.message || "Unable to load notifications.")}</div>`;
      }
    } finally {
      notificationsLoading = false;
      clearNotificationLoadingState();
    }
  }

  async function markAllAsRead() {
    try {
      const response = await fetch("../includes/mark_notifications_read.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
          Accept: "application/json",
        },
        body: "",
      });
      const data = await response.json();
      if (!response.ok || !data.success) {
        throw new Error(
          data.message || "Unable to mark notifications as read.",
        );
      }
      currentUnreadCount = Number(data.unread_count || 0);
      updateNotificationBadge(currentUnreadCount);
      loadNotifications(true);
    } catch (error) {
      alert(error.message || "Unable to mark notifications as read.");
    }
  }

  async function markSingleAsRead(notificationId) {
    if (!notificationId) return null;
    const body = new URLSearchParams();
    body.set("notification_id", String(notificationId));

    const response = await fetch("../includes/mark_notification_read.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },
      body: body.toString(),
    });
    const data = await response.json();
    if (!response.ok || !data.success) {
      throw new Error(data.message || "Unable to update notification.");
    }

    return data;
  }

  if (notificationBtn && notificationDropdown) {
    notificationBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      const isOpen = notificationDropdown.classList.contains("open");
      notificationDropdown.classList.toggle("open", !isOpen);
      if (!isOpen) {
        loadNotifications(true);
      }
    });
  }

  if (markReadBtn) {
    markReadBtn.addEventListener("click", (e) => {
      e.preventDefault();
      markAllAsRead();
    });
  }

  if (notificationDropdown) {
    notificationDropdown.addEventListener("click", (e) => e.stopPropagation());
  }

  if (notificationList) {
    notificationList.addEventListener("click", async (event) => {
      const item = event.target.closest(
        ".notification-item[data-notification-id]",
      );
      if (!item) return;

      const notificationId = Number(item.dataset.notificationId || 0);
      const isRead = item.dataset.notificationRead === "1";
      if (notificationId <= 0 || isRead) return;

      try {
        const result = await markSingleAsRead(notificationId);
        item.dataset.notificationRead = "1";
        item.classList.remove("unread");
        if (result && result.unread_count !== undefined) {
          currentUnreadCount = Number(result.unread_count || 0);
          updateNotificationBadge(currentUnreadCount);
        } else if (currentUnreadCount > 0) {
          currentUnreadCount = Math.max(currentUnreadCount - 1, 0);
          updateNotificationBadge(currentUnreadCount);
        } else {
          loadNotifications(true);
        }
      } catch (error) {
        alert(error.message || "Unable to mark notification as read.");
      }
    });

    notificationList.addEventListener("scroll", () => {
      const remaining =
        notificationList.scrollHeight -
        notificationList.scrollTop -
        notificationList.clientHeight;
      if (remaining < 80) {
        loadNotifications(false);
      }
    });
  }

  document.addEventListener("click", () => {
    if (notificationDropdown) {
      notificationDropdown.classList.remove("open");
    }
  });

  loadNotifications(true);

  /* Load dashboard data */
  const activePage = document.querySelector(".page.active");
  if (activePage && activePage.id === "page-dashboard") {
    loadDashboardSafetyCenters();
    loadCommunityFeed();
    loadBocaueWeather();
    initDashboardFloodMap();
    setTimeout(initDashboardFloodMap, 400);
  }
});

/* =============================================================
   Flood maps — Leaflet + OpenStreetMap (aligned with LGU/Rescuer)
   Data: includes/fetch_flood_severity_map.php
   ============================================================= */

const FLOOD_SEVERITY_META = {
  1: { color: "#22c55e", label: "Passable", border: "#16a34a" },
  2: { color: "#eab308", label: "Limited Access", border: "#ca8a04" },
  3: { color: "#ef4444", label: "Impassable", border: "#dc2626" },
};

const FLOOD_MAP_API = "../includes/fetch_flood_severity_map.php";

function makeFloodSeverityIcon(severityId) {
  const meta = FLOOD_SEVERITY_META[severityId] || {
    color: "#94a3b8",
    border: "#64748b",
  };
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="30" height="38" viewBox="0 0 30 38">
      <defs>
        <filter id="ds" x="-30%" y="-20%" width="160%" height="160%">
          <feDropShadow dx="0" dy="2" stdDeviation="2" flood-color="rgba(0,0,0,0.35)"/>
        </filter>
      </defs>
      <path filter="url(#ds)" fill="${meta.color}" stroke="${meta.border}" stroke-width="1.5"
        d="M15 1C8.373 1 3 6.373 3 13c0 9 12 24 12 24S27 22 27 13C27 6.373 21.627 1 15 1z"/>
      <circle cx="15" cy="13" r="5.5" fill="#fff" opacity="0.9"/>
    </svg>`;

  return L.divIcon({
    html: svg,
    className: "",
    iconSize: [30, 38],
    iconAnchor: [15, 38],
    popupAnchor: [0, -38],
  });
}

function buildFloodSeverityPopup(report) {
  const severityId = Number.parseInt(report.severity_id, 10);
  const meta = FLOOD_SEVERITY_META[severityId] || {
    color: report.severity_color || "#94a3b8",
    label: "Unknown",
    border: "#64748b",
  };
  const severityLabel = String(
    report.severity_name || meta.label || "Unknown",
  ).trim();
  const dot = `<span style="display:inline-block;width:10px;height:10px;border-radius:50%;
    background:${meta.color};margin-right:6px;vertical-align:middle;"></span>`;
  const date = report.created_at
    ? new Date(report.created_at).toLocaleDateString("en-PH", {
        year: "numeric",
        month: "short",
        day: "numeric",
      })
    : "—";
  const locationTitle = [
    report.barangay_name,
    report.municipality || "Bocaue",
  ]
    .filter(Boolean)
    .join(", ");

  return (
    `<div style="font-family:'Segoe UI',sans-serif;min-width:220px;max-width:280px;">` +
    `<div style="background:${meta.color};color:#fff;margin:-1px -1px 0;padding:8px 12px;
      border-radius:8px 8px 0 0;font-weight:700;font-size:0.82rem;letter-spacing:0.03em;">` +
    `${dot}${escapeHtml(severityLabel).toUpperCase()}</div>` +
    `<div style="padding:10px 12px;">` +
    `<div style="font-weight:700;font-size:0.9rem;color:#1e293b;margin-bottom:2px;">` +
    `${escapeHtml(locationTitle)}</div>` +
    (report.full_address
      ? `<div style="font-size:0.75rem;color:#64748b;margin-bottom:6px;">${escapeHtml(report.full_address)}</div>`
      : "") +
    `<hr style="border:none;border-top:1px solid #e2e8f0;margin:6px 0;">` +
    (report.water_level
      ? `<div style="font-size:0.78rem;color:#475569;margin-bottom:4px;"><strong>Water level:</strong> ${escapeHtml(report.water_level)}</div>`
      : "") +
    (report.description
      ? `<div style="font-size:0.78rem;color:#475569;margin-bottom:4px;"><strong>Details:</strong> ${escapeHtml(report.description)}</div>`
      : "") +
    `<div style="font-size:0.72rem;color:#94a3b8;margin-top:6px;">Reported ${date}` +
    (report.reported_by ? " · " + escapeHtml(report.reported_by) : "") +
    `</div></div></div>`
  );
}

function attachFloodMarkers(map, reports, activeFilters, markerStore) {
  markerStore.list.forEach((marker) => map.removeLayer(marker));
  markerStore.list = [];

  reports.forEach((report) => {
    const severityKey = String(report.severity_id);
    if (
      activeFilters &&
      !activeFilters.has("all") &&
      !activeFilters.has(severityKey)
    ) {
      return;
    }

    const lat = Number.parseFloat(report.latitude);
    const lng = Number.parseFloat(report.longitude);
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

    const marker = L.marker([lat, lng], {
      icon: makeFloodSeverityIcon(Number.parseInt(report.severity_id, 10)),
    })
      .addTo(map)
      .bindPopup(buildFloodSeverityPopup(report), {
        maxWidth: 290,
        className: "flood-severity-popup",
      });

    markerStore.list.push(marker);
  });
}

function fetchFloodSeverityReports() {
  return fetch(FLOOD_MAP_API)
    .then((res) => res.json())
    .then((json) => {
      if (!json.success) {
        throw new Error(json.message || "Failed to load flood reports.");
      }
      return json.data || [];
    });
}

function addCurrentLocationToMap(map, markerStore) {
  addUseCurrentLocationButton(map, function onCurrentLocation(lat, lng) {
    if (markerStore.user) {
      markerStore.user.setLatLng([lat, lng]);
    } else {
      markerStore.user = L.marker([lat, lng]).addTo(map);
    }
    markerStore.user.bindPopup("Your current location").openPopup();
    map.flyTo([lat, lng], 16, { duration: 0.7 });
  });
}

function addDashboardFloodLegend(map, onFilterChange) {
  if (map._dashFloodLegend) {
    return;
  }

  const legend = L.control({ position: "bottomleft" });
  legend.onAdd = function () {
    const div = L.DomUtil.create("div", "dash-flood-map-legend");
    div.innerHTML =
      '<div class="dash-flood-map-legend__title">Flood Severity</div>' +
      Object.entries(FLOOD_SEVERITY_META)
        .map(
          ([id, meta]) =>
            `<button type="button" class="dash-flood-map-legend__item" data-severity-filter="${id}">` +
            `<span class="dash-flood-map-legend__dot" style="background:${meta.color};border-color:${meta.border};"></span>` +
            `<span>${escapeHtml(meta.label)}</span>` +
            `</button>`,
        )
        .join("") +
      '<button type="button" class="dash-flood-map-legend__show-all" data-severity-filter="all">Show All</button>';

    L.DomEvent.disableClickPropagation(div);
    div.querySelectorAll("[data-severity-filter]").forEach((el) => {
      L.DomEvent.on(el, "click", function () {
        const filterId = this.getAttribute("data-severity-filter");
        onFilterChange(filterId);
      });
    });

    return div;
  };

  legend.addTo(map);
  map._dashFloodLegend = legend;
}

const dashboardFloodState = {
  map: null,
  markerStore: { list: [], user: null },
  reports: [],
  activeFilters: new Set(["1", "2", "3"]),
  refreshTimer: null,
  bound: false,
};

function updateDashboardFloodFilterButtons() {
  document
    .querySelectorAll("#dash-flood-filter-bar .dash-flood-filter-btn")
    .forEach((btn) => {
      const filterId = btn.dataset.filter;
      const isActive = dashboardFloodState.activeFilters.has(filterId);
      btn.classList.toggle("active", isActive);
    });
}

function setDashboardFloodFilter(filterId) {
  const filters = dashboardFloodState.activeFilters;
  filters.clear();

  if (filterId === "all") {
    ["1", "2", "3"].forEach((id) => filters.add(id));
  } else {
    filters.add(String(filterId));
  }

  updateDashboardFloodFilterButtons();
  renderDashboardFloodMarkers();
}

function renderDashboardFloodMarkers(options) {
  const map = dashboardFloodState.map;
  if (!map) return;

  attachFloodMarkers(
    map,
    dashboardFloodState.reports,
    dashboardFloodState.activeFilters,
    dashboardFloodState.markerStore,
  );

  const countEl = document.getElementById("dash-flood-report-count");
  const visible = dashboardFloodState.markerStore.list.length;
  if (countEl) {
    countEl.textContent =
      visible +
      " verified report" +
      (visible === 1 ? "" : "s") +
      " on map";
  }

  const shouldFit = options && options.fitBounds;
  if (shouldFit) {
    if (visible > 0) {
      const bounds = L.latLngBounds(
        dashboardFloodState.markerStore.list.map((marker) => marker.getLatLng()),
      );
      map.fitBounds(bounds.pad(0.12), { padding: [24, 24], maxZoom: 15 });
    } else {
      fitMapToBocaueBoundary(map);
    }
  }

  map.invalidateSize();
}

function bindDashboardFloodFilterBar() {
  if (dashboardFloodState.bound) return;
  dashboardFloodState.bound = true;

  document
    .querySelectorAll("#dash-flood-filter-bar .dash-flood-filter-btn")
    .forEach((btn) => {
      btn.addEventListener("click", () => {
        const filterId = btn.dataset.filter;
        const filters = dashboardFloodState.activeFilters;

        if (filters.has(filterId)) {
          filters.delete(filterId);
          if (!filters.size) {
            ["1", "2", "3"].forEach((id) => filters.add(id));
          }
        } else {
          filters.add(filterId);
        }

        updateDashboardFloodFilterButtons();
        renderDashboardFloodMarkers();
      });
    });
}

function loadDashboardFloodReports() {
  const fitBounds = !dashboardFloodState.didInitialFit;
  return fetchFloodSeverityReports()
    .then((reports) => {
      dashboardFloodState.reports = reports;
      renderDashboardFloodMarkers({ fitBounds: fitBounds });
      dashboardFloodState.didInitialFit = true;
    })
    .catch((err) => {
      console.error("Dashboard flood map:", err);
      const countEl = document.getElementById("dash-flood-report-count");
      if (countEl) {
        countEl.textContent = "Could not load verified reports";
      }
    });
}

function initDashboardFloodMap() {
  const mapEl = document.getElementById("dashboard-flood-map");
  if (!mapEl || typeof L === "undefined") {
    return;
  }

  if (mapEl._leafletMap) {
    mapEl._leafletMap.invalidateSize();
    loadDashboardFloodReports();
    return;
  }

  const map = createBocaueLeafletMap("dashboard-flood-map", { minZoom: 11 });
  mapEl._leafletMap = map;
  dashboardFloodState.map = map;

  addCurrentLocationToMap(map, dashboardFloodState.markerStore);
  addDashboardFloodLegend(map, setDashboardFloodFilter);
  bindDashboardFloodFilterBar();
  updateDashboardFloodFilterButtons();

  const refreshMapSize = () => map.invalidateSize();
  refreshMapSize();
  requestAnimationFrame(refreshMapSize);
  setTimeout(refreshMapSize, 200);
  setTimeout(refreshMapSize, 600);

  loadDashboardFloodReports();

  if (dashboardFloodState.refreshTimer) {
    clearInterval(dashboardFloodState.refreshTimer);
  }
  dashboardFloodState.refreshTimer = setInterval(loadDashboardFloodReports, 30000);
}

(function initResidentFloodMapPage() {
  "use strict";

  let floodPageMap = null;
  let allReports = [];
  const markerStore = { list: [], user: null };
  let searchMarker = null;
  let activeSuggestionResults = [];
  const activeFilters = new Set(["1", "2", "3"]);

  function renderFloodPageMarkers() {
    if (!floodPageMap) return;
    attachFloodMarkers(floodPageMap, allReports, activeFilters, markerStore);
  }

  function bindFilterButtons() {
    document.querySelectorAll("#page-flood-map .filter-btn").forEach((btn) => {
      btn.addEventListener("click", () => {
        const filter = btn.dataset.filter;
        if (filter === "all") {
          const allSelected = activeFilters.size === 3;
          activeFilters.clear();
          document
            .querySelectorAll("#page-flood-map .filter-btn[data-filter]")
            .forEach((el) => {
              if (el.dataset.filter === "all") return;
              if (allSelected) {
                el.classList.remove("active");
              } else {
                el.classList.add("active");
                activeFilters.add(el.dataset.filter);
              }
            });
          btn.classList.toggle("active", !allSelected);
          if (!allSelected) ["1", "2", "3"].forEach((id) => activeFilters.add(id));
          renderFloodPageMarkers();
          return;
        }

        if (activeFilters.has(filter)) {
          activeFilters.delete(filter);
          btn.classList.remove("active");
        } else {
          activeFilters.add(filter);
          btn.classList.add("active");
        }
        renderFloodPageMarkers();
      });
    });
  }

  function bindMapSearch(map) {
    const searchInput = document.getElementById("map-search");
    if (!searchInput) return;

    const suggestionId = "map-search-suggestions";
    let suggestionList = document.getElementById(suggestionId);
    if (!suggestionList) {
      suggestionList = document.createElement("datalist");
      suggestionList.id = suggestionId;
      document.body.appendChild(suggestionList);
    }
    searchInput.setAttribute("list", suggestionId);

    let suggestionTimer = null;
    searchInput.addEventListener("input", () => {
      const query = searchInput.value.trim();
      window.clearTimeout(suggestionTimer);
      if (query.length < 3) {
        activeSuggestionResults = [];
        suggestionList.innerHTML = "";
        return;
      }
      suggestionTimer = window.setTimeout(async () => {
        try {
          const rows = await searchPlacesInBocaue(query, 6);
          activeSuggestionResults = rows;
          suggestionList.innerHTML = rows
            .map(
              (row, idx) =>
                `<option value="${escapeHtml(formatNominatimLabel(row) || query)}" data-idx="${idx}"></option>`,
            )
            .join("");
        } catch {
          activeSuggestionResults = [];
          suggestionList.innerHTML = "";
        }
      }, 280);
    });

    searchInput.addEventListener("keydown", async (e) => {
      if (e.key !== "Enter") return;
      e.preventDefault();
      const query = e.target.value.trim();
      if (!query) return;
      try {
        let target = activeSuggestionResults.find(
          (row) => formatNominatimLabel(row) === query,
        );
        if (!target) {
          const rows = await searchPlacesInBocaue(query, 1);
          target = rows[0];
        }
        if (!target) {
          alert("Location not found in Bocaue.");
          return;
        }
        const lat = Number.parseFloat(target.lat);
        const lng = Number.parseFloat(target.lon);
        const label = formatNominatimLabel(target);
        if (searchMarker) searchMarker.setLatLng([lat, lng]);
        else searchMarker = L.marker([lat, lng]).addTo(map);
        searchMarker.bindPopup(label).openPopup();
        map.flyTo([lat, lng], 16, { duration: 0.6 });
      } catch (error) {
        alert(error.message || "Search unavailable. Check your connection.");
      }
    });
  }

  function initFloodMapPage() {
    if (!document.getElementById("flood-map") || typeof L === "undefined") {
      return;
    }
    if (floodPageMap) {
      floodPageMap.invalidateSize();
      return;
    }

    floodPageMap = createBocaueLeafletMap("flood-map");
    addCurrentLocationToMap(floodPageMap, markerStore);
    bindFilterButtons();
    bindMapSearch(floodPageMap);

    fetchFloodSeverityReports()
      .then((reports) => {
        allReports = reports;
        renderFloodPageMarkers();
        setTimeout(() => floodPageMap.invalidateSize(), 300);
      })
      .catch((err) => {
        console.error("Flood map:", err);
        alert("Could not load flood map data. Please try again later.");
      });
  }

  document.addEventListener("DOMContentLoaded", () => {
    setTimeout(initFloodMapPage, 200);
  });
})();

/* =============================================================
   report-flood.js — Report Flood page logic
   Depends on: Leaflet (loaded in section), resident.js
   ============================================================= */

(function () {
  "use strict";

  var map, pinMarker;
  var reverseRequestToken = 0;

  /* ----------------------------------------------------------
     Custom red pin icon
  ---------------------------------------------------------- */
  function makePinIcon() {
    return L.divIcon({
      className: "",
      html: '<span class="material-symbols-outlined" style="font-size:36px;color:#dc2626;filter:drop-shadow(0 2px 5px rgba(0,0,0,0.4));display:block;line-height:1;margin-left:-4px;">location_on</span>',
      iconSize: [32, 36],
      iconAnchor: [16, 36],
      popupAnchor: [0, -38],
    });
  }

  /* ----------------------------------------------------------
     Update the pinned-location field and pin-info strip
  ---------------------------------------------------------- */
  function updateLocationField(address, lat, lng) {
    var display = document.getElementById("pinned-location-display");
    var icon = document.getElementById("pinned-location-icon");
    var text = document.getElementById("pinned-location-text");
    var pinInfo = document.getElementById("pin-info");
    var pinAddress = document.getElementById("pin-address");
    var pinCoords = document.getElementById("pin-coords");
    var fieldAddr = document.getElementById("field-address");

    var addressText = address || lat.toFixed(5) + ", " + lng.toFixed(5);

    if (display) display.classList.add("filled");
    if (icon) icon.textContent = "location_on";
    if (text) text.textContent = addressText;
    if (pinInfo) pinInfo.classList.add("has-pin");
    if (pinAddress) pinAddress.textContent = address || "Location pinned";
    if (pinCoords)
      pinCoords.textContent = lat.toFixed(6) + ", " + lng.toFixed(6);
    if (fieldAddr) fieldAddr.value = addressText;
  }

  /* ----------------------------------------------------------
     Place / move pin
  ---------------------------------------------------------- */
  function placePin(lat, lng) {
    var latF = parseFloat(lat);
    var lngF = parseFloat(lng);

    if (!isPointInsideBocaue(latF, lngF)) {
      alert("You are outside Bocaue, Bulacan coverage area.");
      return;
    }

    if (pinMarker) {
      pinMarker.setLatLng([latF, lngF]);
    } else {
      pinMarker = L.marker([latF, lngF], {
        icon: makePinIcon(),
        draggable: true,
      })
        .addTo(map)
        .bindPopup("📍 Your flood report location")
        .openPopup();

      pinMarker.on("dragend", function (e) {
        var pos = e.target.getLatLng();
        placePin(pos.lat, pos.lng);
      });
    }

    var fieldLat = document.getElementById("field-lat");
    var fieldLng = document.getElementById("field-lng");
    if (fieldLat) fieldLat.value = latF.toFixed(6);
    if (fieldLng) fieldLng.value = lngF.toFixed(6);

    updateLocationField(null, latF, lngF);

    // Reverse-geocode with request token guard to avoid stale responses
    var token = ++reverseRequestToken;
    reverseGeocodeInBocaue(latF, lngF)
      .then(function (addressText) {
        if (token !== reverseRequestToken) return;
        updateLocationField(addressText, latF, lngF);
      })
      .catch(function () {
        if (token !== reverseRequestToken) return;
        updateLocationField(null, latF, lngF);
      });
  }

  /* ----------------------------------------------------------
     Init Leaflet map
  ---------------------------------------------------------- */
  function initMap() {
    var mapEl = document.getElementById("report-map");
    if (!mapEl || typeof L === "undefined") return;

    map = createBocaueLeafletMap("report-map");

    map.on("click", function (e) {
      placePin(e.latlng.lat, e.latlng.lng);
    });

    addUseCurrentLocationButton(map, function onCurrentLocation(lat, lng) {
      map.flyTo([lat, lng], 16, { duration: 0.7 });
      placePin(lat, lng);
    });

    setTimeout(function () {
      map.invalidateSize();
    }, 350);
  }

  /* ----------------------------------------------------------
     Severity ↔ water level sync
     Passable/Rainy locks water level to "none"
  ---------------------------------------------------------- */
  function initSeverityWaterLevelSync() {
    var radios = document.querySelectorAll('input[name="severity"]');
    var waterSel = document.getElementById("water-level");
    var waterGroup = document.getElementById("water-level-group");
    var waterHintEl = document.getElementById("water-level-hint");
    var rescueSection = document.getElementById("rescue-section");
    var rescueNeeded = document.querySelector(
      'input[name="rescue_status"][value="Rescue Needed"]',
    );
    var rescueNotRequired = document.querySelector(
      'input[name="rescue_status"][value="Not Required"]',
    );
    var rescueOptions = document.querySelectorAll(
      'input[name="rescue_status"]',
    );
    var rescueDetails = document.getElementById("rescue-details");
    var rescuePeopleInput = document.getElementById("rescue-people");
    var rescueDescriptionInput = document.getElementById("rescue-note");

    if (!radios.length || !waterSel) return;

    var previousSeverity = null;

    var severityWaterMap = {
      high: ["above", "chest"],
      moderate: ["waist", "knee"],
      passable: ["ankle", "none"],
    };
    var severityHintMap = {
      high: "Allowed for High: Above head, Chest-deep.",
      moderate: "Allowed for Moderate: Waist-deep, Knee-deep.",
      passable:
        "Allowed for Passable / Rainy: Ankle-deep, No flooding / Rainy only.",
    };

    function applyWaterLevelConstraints(allowedLevels) {
      var hasValidCurrent = false;
      Array.from(waterSel.options).forEach(function (option) {
        if (option.value === "") return;
        var isAllowed = allowedLevels.includes(option.value);
        option.disabled = !isAllowed;
        if (isAllowed && option.value === waterSel.value) {
          hasValidCurrent = true;
        }
      });

      if (!hasValidCurrent) {
        waterSel.value = allowedLevels[0] || "";
      }
    }

    function syncRescueDetailsUI(rescueValue) {
      if (rescueValue === "Rescue Needed") {
        if (rescueDetails) rescueDetails.classList.add("visible");
        if (rescuePeopleInput) {
          rescuePeopleInput.disabled = false;
          rescuePeopleInput.required = true;
        }
        if (rescueDescriptionInput) {
          rescueDescriptionInput.disabled = false;
        }
      } else {
        if (rescueDetails) rescueDetails.classList.remove("visible");
        if (rescuePeopleInput) {
          rescuePeopleInput.value = "";
          rescuePeopleInput.disabled = true;
          rescuePeopleInput.required = false;
        }
        if (rescueDescriptionInput) {
          rescueDescriptionInput.value = "";
          rescueDescriptionInput.disabled = true;
        }
      }
    }

    function applyRules(value) {
      var allowedLevels = severityWaterMap[value] || [];
      applyWaterLevelConstraints(allowedLevels);
      if (waterHintEl) {
        waterHintEl.textContent =
          severityHintMap[value] ||
          "Select flood severity first to see allowed water levels.";
      }

      waterSel.disabled = false;
      if (waterGroup) waterGroup.style.opacity = "1";

      if (value === "passable") {
        if (rescueNotRequired) rescueNotRequired.checked = true;
        if (rescueSection) rescueSection.classList.add("hidden");
        rescueOptions.forEach(function (radio) {
          radio.disabled = true;
        });
        syncRescueDetailsUI("Not Required");

        var numPeoplePassable = document.getElementById("rescue-people");
        var rescueNotePassable = document.getElementById("rescue-note");
        if (numPeoplePassable) numPeoplePassable.value = "";
        if (rescueNotePassable) rescueNotePassable.value = "";
      } else {
        if (rescueSection) rescueSection.classList.remove("hidden");
        rescueOptions.forEach(function (radio) {
          radio.disabled = false;
        });

        var checkedRescue = document.querySelector(
          'input[name="rescue_status"]:checked',
        );
        if (previousSeverity === "passable" || !checkedRescue) {
          if (rescueNeeded) rescueNeeded.checked = true;
        }

        checkedRescue = document.querySelector(
          'input[name="rescue_status"]:checked',
        );
        syncRescueDetailsUI(
          checkedRescue ? checkedRescue.value : "Rescue Needed",
        );
      }

      previousSeverity = value;
    }

    radios.forEach(function (radio) {
      radio.addEventListener("change", function () {
        applyRules(this.value);
      });
    });

    // Apply on load for page-reload state
    var checked = document.querySelector('input[name="severity"]:checked');
    if (checked) applyRules(checked.value);
  }

  /* ----------------------------------------------------------
     Rescue toggle
     Shows extra details panel when "Rescue Needed" is picked
  ---------------------------------------------------------- */
  function initRescueToggle() {
    var rescueRadios = document.querySelectorAll('input[name="rescue_status"]');
    var details = document.getElementById("rescue-details");
    if (!rescueRadios.length || !details) return;

    function applyRescue(value) {
      if (value === "Rescue Needed") {
        details.classList.add("visible");
        var peopleInputNeeded = document.getElementById("rescue-people");
        var noteInputNeeded = document.getElementById("rescue-note");
        if (peopleInputNeeded) {
          peopleInputNeeded.disabled = false;
          peopleInputNeeded.required = true;
        }
        if (noteInputNeeded) {
          noteInputNeeded.disabled = false;
        }
      } else {
        details.classList.remove("visible");
        // Clear rescue detail fields
        var numPeople = document.getElementById("rescue-people");
        var rescueNote = document.getElementById("rescue-note");
        if (numPeople) {
          numPeople.value = "";
          numPeople.disabled = true;
          numPeople.required = false;
        }
        if (rescueNote) {
          rescueNote.value = "";
          rescueNote.disabled = true;
        }
      }
    }

    rescueRadios.forEach(function (radio) {
      radio.addEventListener("change", function () {
        applyRescue(this.value);
      });
    });

    // Apply on load
    var checkedRescue = document.querySelector(
      'input[name="rescue_status"]:checked',
    );
    if (checkedRescue) applyRescue(checkedRescue.value);
  }

  /* ----------------------------------------------------------
   Photo preview (JPG/PNG only)
---------------------------------------------------------- */
  function initPhotoAttach() {
    var photoBtn = document.getElementById("attach-photo-btn");
    var photoInput = document.getElementById("photo-input");
    var photoPreview = document.getElementById("photo-preview");
    var photoImg = document.getElementById("photo-img");

    if (!photoBtn || !photoInput) return;

    photoBtn.addEventListener("click", function () {
      photoInput.click();
    });

    photoInput.addEventListener("change", function (e) {
      var file = e.target.files[0];
      if (!file) return;

      // ✅ ONLY JPG + PNG
      var allowed = ["image/jpeg", "image/png"];

      if (!allowed.includes(file.type)) {
        alert("Only JPG and PNG images are allowed.");
        photoInput.value = "";
        photoPreview.style.display = "none";
        return;
      }

      // Optional: file size limit (recommended for LGU system)
      var maxSizeMB = 3;
      if (file.size > maxSizeMB * 1024 * 1024) {
        alert("Image must be less than 3MB.");
        photoInput.value = "";
        photoPreview.style.display = "none";
        return;
      }

      var reader = new FileReader();
      reader.onload = function (ev) {
        photoImg.src = ev.target.result;
        photoPreview.style.display = "block";
      };

      reader.readAsDataURL(file);
    });
  }
  /* ----------------------------------------------------------
     Prevent double-submit
  ---------------------------------------------------------- */
  function initFormSubmit() {
    var form = document.getElementById("report-form");
    var submitBtn = document.getElementById("submit-btn");
    if (!form || !submitBtn) return;
    form.addEventListener("submit", function () {
      submitBtn.disabled = true;
      submitBtn.textContent = "Submitting…";
    });
  }

  /* ----------------------------------------------------------
     Success modal
  ---------------------------------------------------------- */
  function initSuccessModal() {
    var okBtn = document.getElementById("ok-btn");
    var overlay = document.getElementById("success-overlay");
    if (!okBtn || !overlay) return;
    okBtn.addEventListener("click", function () {
      overlay.classList.remove("visible");
    });
    overlay.addEventListener("click", function (e) {
      if (e.target === overlay) overlay.classList.remove("visible");
    });
  }

  /* ----------------------------------------------------------
     Boot
  ---------------------------------------------------------- */
  function init() {
    if (!document.getElementById("report-map")) return;
    initMap();
    initSeverityWaterLevelSync();
    initRescueToggle();
    initPhotoAttach();
    initFormSubmit();
    initSuccessModal();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();

/* =============================================================
   hotlines.js — Hotlines page logic
   Data from ../api/fetch_hotlines.php (shared with LGU / Rescuer)
   ============================================================= */

(function () {
  "use strict";

  var TAG_LABELS = {
    emergency: "Emergency",
    medical: "Medical",
    police: "Police",
    lgu: "LGU",
    rescue: "Rescue",
    barangay: "Barangay",
  };

  var allHotlines = [];
  var activeCategory = "all";

  function mapGroupedApiData(data) {
    var rows = [];
    if (!data || typeof data !== "object") return rows;
    Object.keys(data).forEach(function (barangay) {
      (data[barangay] || []).forEach(function (h) {
        rows.push({
          name: h.hotline_name,
          number: h.contact_number,
          barangay: barangay,
          category: inferCategory(h.hotline_name),
        });
      });
    });
    return rows.map(applyDisplayMeta);
  }

  function inferCategory(name) {
    var n = String(name || "").toLowerCase();
    if (/police|pnp|bantay/.test(n)) return "police";
    if (/medical|health|hospital|ambulance|red cross/.test(n)) return "medical";
    if (/rescue|fire|search|coast guard/.test(n)) return "rescue";
    if (/emergency|mdrrmo|ndrrmc|911/.test(n)) return "emergency";
    return "lgu";
  }

  function applyDisplayMeta(h) {
    var cat = h.category || inferCategory(h.name);
    var icons = {
      police: { icon: "local_police", iconClass: "icon-police", tagClass: "tag-police" },
      medical: { icon: "local_hospital", iconClass: "icon-medical", tagClass: "tag-medical" },
      rescue: { icon: "medical_services", iconClass: "icon-rescue", tagClass: "tag-rescue" },
      emergency: { icon: "emergency", iconClass: "icon-emergency", tagClass: "tag-emergency" },
      lgu: { icon: "account_balance", iconClass: "icon-lgu", tagClass: "tag-lgu" },
    };
    var meta = icons[cat] || icons.lgu;
    return {
      name: h.name,
      number: h.number,
      barangay: h.barangay || "",
      category: cat,
      icon: h.icon || meta.icon,
      iconClass: h.iconClass || meta.iconClass,
      tagClass: h.tagClass || meta.tagClass,
    };
  }

  function loadHotlines() {
    showLoading(true);
    hideError();

    fetch("../api/fetch_hotlines.php")
      .then(function (r) {
        if (!r.ok) throw new Error("HTTP " + r.status);
        return r.json();
      })
      .then(function (json) {
        showLoading(false);
        if (!json.success) {
          showError(json.message || "Could not load hotlines from the database.");
          allHotlines = [];
          renderCards([]);
          return;
        }
        allHotlines = mapGroupedApiData(json.data);
        if (!allHotlines.length) {
          showError("No hotlines found in the database.");
        }
        renderCards(getFiltered());
      })
      .catch(function () {
        showLoading(false);
        showError("Could not load hotlines from the database.");
        allHotlines = [];
        renderCards([]);
      });
  }

  /* ----------------------------------------------------------
     Filter hotlines by active category + search query
  ---------------------------------------------------------- */
  function getFiltered() {
    var q = "";
    var searchInput = document.getElementById("hl-search-input");
    if (searchInput) q = searchInput.value.toLowerCase().trim();

    return allHotlines.filter(function (h) {
      var matchCat = activeCategory === "all" || h.category === activeCategory;
      var matchText =
        h.name.toLowerCase().includes(q) ||
        h.number.includes(q) ||
        (h.barangay && h.barangay.toLowerCase().includes(q));
      return matchCat && matchText;
    });
  }

  /* ----------------------------------------------------------
     Render card list
  ---------------------------------------------------------- */
  function renderCards(data) {
    var list = document.getElementById("hl-list");
    var noRes = document.getElementById("hl-no-results");
    if (!list) return;

    list.innerHTML = "";

    if (!data.length) {
      if (noRes) noRes.style.display = "block";
      return;
    }
    if (noRes) noRes.style.display = "none";

    // Group by barangay (empty string = national/static)
    var groups = {};
    data.forEach(function (h) {
      var key = h.barangay || "__national__";
      if (!groups[key]) groups[key] = [];
      groups[key].push(h);
    });

    // Render national group first (no header), then barangay groups
    var order = Object.keys(groups).sort(function (a, b) {
      if (a === "__national__") return -1;
      if (b === "__national__") return 1;
      return a.localeCompare(b);
    });

    order.forEach(function (groupKey) {
      // Barangay group header
      if (groupKey !== "__national__") {
        var header = document.createElement("div");
        header.className = "hl-barangay-header";
        header.textContent = groupKey;
        list.appendChild(header);
      }

      groups[groupKey].forEach(function (h) {
        var card = document.createElement("div");
        card.className = "hl-card";

        var cleanNumber = h.number.replace(/\D/g, "");
        var tagLabel = TAG_LABELS[h.category] || h.category;

        card.innerHTML =
          '<div class="hl-card-icon ' +
          h.iconClass +
          '">' +
          '<span class="material-symbols-outlined">' +
          h.icon +
          "</span>" +
          "</div>" +
          '<div class="hl-card-info">' +
          '<div class="hl-card-name">' +
          escHtml(h.name) +
          "</div>" +
          '<div class="hl-card-number">' +
          escHtml(h.number) +
          "</div>" +
          (h.barangay
            ? '<div class="hl-card-barangay">' + escHtml(h.barangay) + "</div>"
            : "") +
          '<span class="hl-card-tag ' +
          h.tagClass +
          '">' +
          tagLabel +
          "</span>" +
          "</div>" +
          '<a class="hl-call-btn" href="tel:' +
          cleanNumber +
          '">' +
          '<span class="material-symbols-outlined">call</span>' +
          "<span>Call</span>" +
          "</a>";

        list.appendChild(card);
      });
    });
  }

  /* ----------------------------------------------------------
     UI state helpers
  ---------------------------------------------------------- */
  function showLoading(show) {
    var el = document.getElementById("hl-loading");
    if (el) el.style.display = show ? "flex" : "none";
  }

  function showError(msg) {
    var el = document.getElementById("hl-error");
    if (!el) return;
    el.textContent = msg;
    el.style.display = "block";
  }

  function hideError() {
    var el = document.getElementById("hl-error");
    if (el) el.style.display = "none";
  }

  function escHtml(str) {
    if (!str) return "";
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  /* ----------------------------------------------------------
     Event listeners
  ---------------------------------------------------------- */
  function initEvents() {
    // Tab filter
    document.querySelectorAll(".hl-tab").forEach(function (tab) {
      tab.addEventListener("click", function () {
        document.querySelectorAll(".hl-tab").forEach(function (t) {
          t.classList.remove("active");
        });
        tab.classList.add("active");
        activeCategory = tab.dataset.cat;
        renderCards(getFiltered());
      });
    });

    // Search input
    var searchInput = document.getElementById("hl-search-input");
    if (searchInput) {
      searchInput.addEventListener("input", function () {
        renderCards(getFiltered());
      });
    }
  }

  /* ----------------------------------------------------------
     Boot
  ---------------------------------------------------------- */
  function init() {
    if (!document.getElementById("hl-list")) return;
    initEvents();
    loadHotlines();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})();

/* =============================================================
   safety-centers.js — Safety Centers page logic
   Fetches from api/fetch-safety-centers.php
   Depends on: Leaflet (loaded in section), resident.js
   ============================================================= */

(function () {
  "use strict";

  var CFG = {
    available: { label: "Available", emoji: "🟢", dot: "g", badge: "available", bar: "#22c55e", pin: "#22c55e" },
    limited:   { label: "Nearly Full", emoji: "🟡", dot: "y", badge: "limited", bar: "#f59e0b", pin: "#f59e0b" },
    full:      { label: "Full", emoji: "🔴", dot: "r", badge: "full", bar: "#ef4444", pin: "#ef4444" },
  };

  var allCenters = [];
  var filteredCenters = [];
  var markerLayer = null;
  var markerByCenterId = {};
  var selectedCenterId = null;
  var activeSearchQuery = "";
  var userPosition = null;
  var userLocationMarker = null;
  var map = null;
  var refreshIntervalId = null;
  var modalEventsBound = false;
  var modalCenter = null;
  var geocodeCache = {};

  function getStatus(occupancy, capacity) {
    if (capacity <= 0) return "available";
    var pct = (occupancy / capacity) * 100;
    if (pct >= 100) return "full";
    if (pct >= 75) return "limited";
    return "available";
  }

  function makePin(color, isSelected) {
    var size = isSelected ? 34 : 28;
    var height = isSelected ? 44 : 38;
    var cx = isSelected ? 17 : 16;
    var cy = isSelected ? 17 : 16;
    var cr = isSelected ? 8 : 7;
    return L.divIcon({
      className: "sc-map-marker-icon",
      html: '<svg xmlns="http://www.w3.org/2000/svg" width="' + size + '" height="' + height + '" viewBox="0 0 32 42">' +
              '<path d="M16 0C7.163 0 0 7.163 0 16c0 10.5 16 26 16 26S32 26.5 32 16C32 7.163 24.837 0 16 0z" fill="' + color + '" stroke="' + (isSelected ? "#0f172a" : "none") + '" stroke-width="' + (isSelected ? "1.2" : "0") + '"/>' +
              '<circle cx="' + cx + '" cy="' + cy + '" r="' + cr + '" fill="white"/>' +
            '</svg>',
      iconSize:    [size, height],
      iconAnchor:  [Math.round(size / 2), height],
      popupAnchor: [0, -40],
    });
  }

  function getCenterLatLng(center) {
    var lat = Number(center.lat);
    var lng = Number(center.lng);
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
      return null;
    }
    return [lat, lng];
  }

  function hasValidMapCoords(center) {
    return getCenterLatLng(center) !== null;
  }

  function centersWithMapPosition(data) {
    return data.filter(hasValidMapCoords);
  }

  function fitSafetyMapView(markerLatLngs) {
    if (!map) return;
    var points = (markerLatLngs || []).filter(function (ll) {
      return ll && Number.isFinite(ll[0]) && Number.isFinite(ll[1]);
    });

    if (points.length === 1) {
      map.setView(points[0], 15);
    } else if (points.length > 1) {
      map.fitBounds(L.latLngBounds(points), {
        padding: [40, 40],
        maxZoom: 16,
      });
    } else {
      fitMapToBocaueBoundary(map);
    }
    refreshMapSize();
  }

  function delayMs(ms) {
    return new Promise(function (resolve) {
      setTimeout(resolve, ms);
    });
  }

  function resolveCenterCoordinates(centers) {
    var pending = [];

    centers.forEach(function (center) {
      if (hasValidMapCoords(center)) {
        return;
      }

      var cached = geocodeCache[center.center_id];
      if (cached) {
        center.lat = cached[0];
        center.lng = cached[1];
        center.coordsSource = "geocoded";
        return;
      }

      if (String(center.address || "").trim() !== "") {
        pending.push(center);
      }
    });

    if (!pending.length) {
      return Promise.resolve();
    }

    var chain = Promise.resolve();
    pending.forEach(function (center, index) {
      chain = chain
        .then(function () {
          if (index > 0) {
            return delayMs(1100);
          }
          return null;
        })
        .then(function () {
          return geocodeAddressQuery(center.address).then(function (coords) {
            if (!coords) {
              return;
            }
            center.lat = coords[0];
            center.lng = coords[1];
            center.coordsSource = "geocoded";
            geocodeCache[center.center_id] = coords;
          });
        });
    });

    return chain;
  }

  function refreshMapSize() {
    if (!map) return;
    map.invalidateSize();
  }

  function initMap() {
    var mapEl = document.getElementById("safety-map");
    if (!mapEl || typeof L === "undefined") return;

    if (mapEl._leafletMap) {
      map = mapEl._leafletMap;
      refreshMapSize();
      return;
    }

    map = createBocaueLeafletMap("safety-map", {
      scrollWheelZoom: true,
      zoomControl: true,
      minZoom: 11,
    });
    mapEl._leafletMap = map;

    if (!map.getPane("scMarkerPane")) {
      var markerPane = map.createPane("scMarkerPane");
      markerPane.style.zIndex = "650";
    }

    addUseCurrentLocationButton(map, function onCurrentLocation(lat, lng) {
      userPosition = [lat, lng];

      if (userLocationMarker) {
        userLocationMarker.setLatLng([lat, lng]);
      } else {
        userLocationMarker = L.marker([lat, lng]).addTo(map);
      }
      userLocationMarker.bindPopup("Your current location").openPopup();
      renderCards(filteredCenters);
      map.flyTo([lat, lng], 16, { duration: 0.7 });
    });

    refreshMapSize();
    requestAnimationFrame(refreshMapSize);
    setTimeout(refreshMapSize, 300);
    setTimeout(refreshMapSize, 700);

    if (!mapEl._scResizeBound) {
      mapEl._scResizeBound = true;
      window.addEventListener("resize", refreshMapSize);
    }
  }

  function initModalEvents() {
    if (modalEventsBound) return;
    modalEventsBound = true;

    var modal = document.getElementById("sc-center-modal");
    var backdrop = document.getElementById("sc-modal-backdrop");
    var closeBtn = document.getElementById("sc-modal-close");
    var mapBtn = document.getElementById("sc-modal-map-btn");

    function closeModal() {
      if (!modal) return;
      modal.classList.remove("is-open");
      modal.setAttribute("aria-hidden", "true");
      document.body.style.overflow = "";
      modalCenter = null;
    }

    if (closeBtn) closeBtn.addEventListener("click", closeModal);
    if (backdrop) backdrop.addEventListener("click", closeModal);
    if (modal) {
      modal.addEventListener("click", function (e) {
        if (e.target === modal) closeModal();
      });
    }

    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && modal && modal.classList.contains("is-open")) {
        closeModal();
      }
    });

    if (mapBtn) {
      mapBtn.addEventListener("click", function () {
        if (!modalCenter) return;
        closeModal();
        focusCenter(modalCenter.center_id, false);
      });
    }
  }

  function openCenterModal(center) {
    var modal = document.getElementById("sc-center-modal");
    if (!modal || !center) return;

    modalCenter = center;
    var style = CFG[center.status] || CFG.available;
    var pct = getOccupancyPct(center);
    var availablePct = Math.max(0, 100 - pct);

    var titleEl = document.getElementById("sc-modal-title");
    var subtitleEl = document.getElementById("sc-modal-subtitle");
    var addressEl = document.getElementById("sc-modal-address");
    var contactEl = document.getElementById("sc-modal-contact");
    var capacityEl = document.getElementById("sc-modal-capacity");
    var occupancyEl = document.getElementById("sc-modal-occupancy");
    var availabilityEl = document.getElementById("sc-modal-availability");
    var fillEl = document.getElementById("sc-modal-capacity-fill");
    var distanceEl = document.getElementById("sc-modal-distance");
    var badgeEl = document.getElementById("sc-modal-status-badge");
    var iconWrap = document.getElementById("sc-modal-status-icon");
    var callBtn = document.getElementById("sc-modal-call-btn");

    if (titleEl) titleEl.textContent = center.name || "Safety Center";
    if (subtitleEl) {
      var locationParts = [center.barangay, center.municipality, center.province]
        .map(function (part) {
          return String(part || "").trim();
        })
        .filter(Boolean);
      subtitleEl.textContent = locationParts.length
        ? locationParts.join(", ")
        : "";
      subtitleEl.style.display = locationParts.length ? "" : "none";
    }
    if (addressEl) addressEl.textContent = center.address || "—";
    if (contactEl) {
      contactEl.textContent = center.contact
        ? center.contact
        : "No contact on file — check barangay hotlines.";
    }

    var hoursRow = document.getElementById("sc-modal-hours-row");
    var hoursEl = document.getElementById("sc-modal-hours");
    if (hoursRow && hoursEl) {
      if (center.operating_hours) {
        hoursEl.textContent = center.operating_hours;
        hoursRow.style.display = "";
      } else {
        hoursRow.style.display = "none";
      }
    }

    var descRow = document.getElementById("sc-modal-description-row");
    var descEl = document.getElementById("sc-modal-description");
    if (descRow && descEl) {
      if (center.description) {
        descEl.textContent = center.description;
        descRow.style.display = "";
      } else {
        descRow.style.display = "none";
      }
    }

    if (capacityEl) capacityEl.textContent = String(center.capacity);
    if (occupancyEl) occupancyEl.textContent = String(center.occupancy);
    if (availabilityEl) {
      availabilityEl.textContent = availablePct + "% slots open";
    }
    if (fillEl) {
      fillEl.style.width = pct + "%";
      fillEl.style.background = style.bar;
    }
    if (distanceEl) {
      var dist = getDistanceLabel(center);
      distanceEl.textContent = dist ? dist : "";
      distanceEl.style.display = dist ? "block" : "none";
    }
    if (badgeEl) {
      badgeEl.textContent = style.emoji + " " + style.label;
      badgeEl.className = "sc-modal-status-badge " + center.status;
    }
    if (iconWrap) {
      iconWrap.className = "sc-modal-icon " + center.status;
    }
    if (callBtn) {
      if (center.contact) {
        callBtn.href = "tel:" + center.contact.replace(/\D/g, "");
        callBtn.style.display = "inline-flex";
      } else {
        callBtn.style.display = "none";
      }
    }

    modal.classList.add("is-open");
    modal.setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
  }

  function initMarkerLayer() {
    if (!map) return;
    if (markerLayer) {
      map.removeLayer(markerLayer);
      markerLayer = null;
    }
    markerLayer = L.layerGroup();
    markerLayer.addTo(map);
  }

  function updateCountPill(total, onMap) {
    var el = document.getElementById("sc-count-pill");
    if (!el) return;
    if (typeof onMap === "number" && onMap !== total) {
      el.textContent = total + " centers · " + onMap + " on map";
      return;
    }
    el.textContent = total + (total === 1 ? " center" : " centers");
  }

  function getOccupancyPct(center) {
    if (!center.capacity) return 0;
    var pct = Math.round((center.occupancy / center.capacity) * 100);
    return Math.max(0, Math.min(pct, 100));
  }

  function getDistanceLabel(center) {
    if (!map || !userPosition || !center.lat || !center.lng) return "";
    var meters = map.distance(userPosition, [center.lat, center.lng]);
    if (!isFinite(meters)) return "";
    if (meters >= 1000) return (meters / 1000).toFixed(2) + " km away";
    return Math.round(meters) + " m away";
  }

  function clearSelectedCard() {
    document.querySelectorAll(".center-card").forEach(function (el) {
      el.classList.remove("active");
      el.classList.remove("selected");
    });
  }

  function highlightCard(centerId) {
    clearSelectedCard();
    var card = document.querySelector('.center-card[data-id="' + centerId + '"]');
    if (card) {
      card.classList.add("active");
      card.classList.add("selected");
      card.scrollIntoView({ behavior: "smooth", block: "nearest" });
    }
  }

  function updateMarkerIcons() {
    Object.keys(markerByCenterId).forEach(function (id) {
      var marker = markerByCenterId[id];
      var center = allCenters.find(function (item) {
        return String(item.center_id) === String(id);
      });
      if (!marker || !center) return;
      var style = CFG[center.status] || CFG.available;
      marker.setIcon(makePin(style.pin, String(center.center_id) === String(selectedCenterId)));
    });
  }

  function focusCenter(centerId, openModal) {
    var center = allCenters.find(function (item) {
      return String(item.center_id) === String(centerId);
    });
    if (!center) return;
    selectedCenterId = center.center_id;
    highlightCard(selectedCenterId);
    updateMarkerIcons();
    if (openModal) {
      openCenterModal(center);
    }
    var latLng = getCenterLatLng(center);
    if (map && latLng) {
      map.flyTo(latLng, 16, { duration: 0.7 });
    }
  }

  function renderMarkers(data) {
    if (!map || !markerLayer) return;
    markerLayer.clearLayers();
    markerByCenterId = {};

    data.forEach(function (center) {
      var latLng = getCenterLatLng(center);
      if (!latLng) return;

      var style = CFG[center.status] || CFG.available;
      var marker = L.marker(latLng, {
        icon: makePin(
          style.pin,
          String(center.center_id) === String(selectedCenterId),
        ),
        pane: "scMarkerPane",
        riseOnHover: true,
        riseOffset: 250,
      });

      marker.on("click", function (e) {
        if (e.originalEvent) {
          L.DomEvent.stopPropagation(e.originalEvent);
        }
        focusCenter(center.center_id, true);
      });

      markerLayer.addLayer(marker);
      markerByCenterId[String(center.center_id)] = marker;
    });
  }

  function renderCards(data) {
    var list = document.getElementById("sc-list");
    var noRes = document.getElementById("sc-no-results");
    if (!list) return;

    list.innerHTML = "";

    if (!data.length) {
      if (noRes) noRes.style.display = "block";
      return;
    }
    if (noRes) noRes.style.display = "none";

    data.forEach(function (c) {
      var s   = CFG[c.status] || CFG.available;
      var pct = getOccupancyPct(c);
      var cardStatusClass = c.status !== "available" ? " " + c.status : "";
      var distanceLabel = getDistanceLabel(c);

      var card = document.createElement("div");
      card.className   = "center-card" + cardStatusClass;
      card.dataset.id = c.center_id;

      card.innerHTML =
        '<div class="center-top">' +
          '<div class="center-name">' + escHtml(c.name) + '</div>' +
          '<span class="cap-badge ' + s.badge + '">' + s.emoji + " " + s.label + '</span>' +
        '</div>' +
        '<div class="center-addr">' +
          '<span class="material-symbols-outlined">location_on</span>' +
          escHtml(c.address) +
        '</div>' +
        (distanceLabel ? '<div class="center-distance">Distance: ' + escHtml(distanceLabel) + '</div>' : "") +
        '<div class="cap-row">' +
        '<div class="cap-bar-bg">' +
        '<div class="cap-bar-fill" style="width:' +
        pct +
        "%;background:" +
        s.bar +
        ';"></div>' +
        "</div>" +
        '<span class="cap-count">' +
        c.occupancy +
        "/" +
        c.capacity +
        "</span>" +
        "</div>" +
        '<div class="center-bottom">' +
          '<div class="status-pill ' + c.status + '">' +
            '<div class="sdot ' + s.dot + '"></div>' + s.emoji + " " + s.label +
          '</div>' +
          (c.contact
            ? '<a class="center-phone" href="tel:' + escHtml(c.contact.replace(/\D/g, "")) + '">' +
                '<span class="material-symbols-outlined">call</span>' + escHtml(c.contact) +
              '</a>'
            : '') +
        '</div>';

      card.addEventListener("click", function () {
        focusCenter(c.center_id, true);
      });
      card.addEventListener("keydown", function (e) {
        if (e.key === "Enter" || e.key === " ") {
          e.preventDefault();
          focusCenter(c.center_id, true);
        }
      });
      card.setAttribute("tabindex", "0");
      card.setAttribute("role", "button");

      list.appendChild(card);

      if (String(c.center_id) === String(selectedCenterId)) {
        card.classList.add("active");
        card.classList.add("selected");
      }
    });
  }

  function applyFilter(opts) {
    var fitToResults = opts && opts.fitToResults;
    var query = activeSearchQuery.toLowerCase().trim();
    filteredCenters = allCenters.filter(function (center) {
      if (!query) return true;
      return center.name.toLowerCase().includes(query)
        || center.address.toLowerCase().includes(query);
    });
    var mapCenters = centersWithMapPosition(filteredCenters);
    renderCards(filteredCenters);
    renderMarkers(mapCenters);
    updateCountPill(allCenters.length, mapCenters.length);
    if (fitToResults) {
      fitSafetyMapView(
        mapCenters.map(getCenterLatLng).filter(function (ll) {
          return ll !== null;
        }),
      );
    }

    var mapNotice = document.getElementById("sc-map-notice");
    if (mapNotice) {
      var unmapped = filteredCenters.filter(function (center) {
        return !hasValidMapCoords(center);
      }).length;
      if (unmapped > 0) {
        mapNotice.textContent =
          unmapped +
          " center(s) are not on the map yet. Save latitude and longitude in the database, or provide a complete address for automatic placement.";
        mapNotice.style.display = "block";
      } else {
        mapNotice.style.display = "none";
      }
    }
  }

  function fetchCenters(silent) {
    if (!silent) {
      showLoading(true);
      hideError();
    }

    fetch("api/fetch-safety-centers.php")
      .then(function (r) {
        if (!r.ok) throw new Error("Server returned HTTP " + r.status);
        return r.json();
      })
      .then(function (json) {
        if (!silent) showLoading(false);

        if (!json.success) {
          showError("Error: " + (json.message || "Unknown error from server."));
          return;
        }

        allCenters = (json.data || []).map(function (c) {
          var occ = parseInt(c.occupancy, 10) || 0;
          var cap = parseInt(c.capacity, 10) || 0;
          var lat = Number(c.latitude);
          var lng = Number(c.longitude);
          if (!Number.isFinite(lat)) lat = null;
          if (!Number.isFinite(lng)) lng = null;

          return {
            center_id: c.center_id,
            name: c.center_name,
            address:
              c.address ||
              c.full_address ||
              [c.barangay, c.municipality, c.province]
                .filter(Boolean)
                .join(", "),
            barangay: c.barangay || "",
            municipality: c.municipality || "",
            province: c.province || "",
            contact: c.contact || "",
            description: c.description || "",
            operating_hours: c.operating_hours || "",
            occupancy: occ,
            capacity: cap,
            lat: lat,
            lng: lng,
            coordsSource: lat !== null && lng !== null ? "database" : null,
            status: getStatus(occ, cap),
          };
        });

        return resolveCenterCoordinates(allCenters).then(function () {
          applyFilter({ fitToResults: !silent });
          refreshMapSize();

          if (!allCenters.length) {
            var noRes = document.getElementById("sc-no-results");
            if (noRes) {
              noRes.textContent = "No evacuation centers in the database yet.";
              noRes.style.display = "block";
            }
          }
        });
      })
      .catch(function (err) {
        if (!silent) showLoading(false);
        showError(
          "Could not load safety centers. " +
            "Check that api/fetch-safety-centers.php exists and db.php path is correct. " +
            "(" +
            err.message +
            ")",
        );
      });
  }

  function showLoading(show) {
    var el = document.getElementById("sc-loading");
    if (el) el.style.display = show ? "flex" : "none";
  }

  function showError(msg) {
    var el = document.getElementById("sc-error");
    if (!el) return;
    el.textContent = msg;
    el.style.display = "block";
  }

  function hideError() {
    var el = document.getElementById("sc-error");
    if (el) el.style.display = "none";
  }

  function escHtml(str) {
    if (!str) return "";
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function initSearch() {
    var input = document.getElementById("sc-search-input");
    if (!input) return;

    input.addEventListener("input", function () {
      activeSearchQuery = input.value || "";
      applyFilter({ fitToResults: false });
      if (activeSearchQuery.trim() && filteredCenters.length === 1) {
        focusCenter(filteredCenters[0].center_id, true);
      }
    });
  }

  function init() {
    if (!document.getElementById("safety-map")) return;
    initModalEvents();
    initMap();
    initMarkerLayer();
    fitSafetyMapView([]);
    initSearch();
    fetchCenters(false);
    if (refreshIntervalId) clearInterval(refreshIntervalId);
    refreshIntervalId = setInterval(function () {
      fetchCenters(true);
    }, 30000);
  }

  function bootSafetyCenters() {
    if (!document.getElementById("safety-map")) return;
    if (typeof L === "undefined") {
      setTimeout(bootSafetyCenters, 80);
      return;
    }
    init();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", function () {
      setTimeout(bootSafetyCenters, 100);
    });
  } else {
    setTimeout(bootSafetyCenters, 100);
  }
})();
