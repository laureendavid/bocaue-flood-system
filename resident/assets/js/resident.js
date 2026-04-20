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
const BOCAUE_BOUNDS = typeof L !== "undefined" && typeof L.latLngBounds === "function"
  ? L.latLngBounds([14.747, 120.865], [14.845, 120.99])
  : null;
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
  for (let i = 0, j = BOCAUE_POLYGON.length - 1; i < BOCAUE_POLYGON.length; j = i++) {
    const yi = BOCAUE_POLYGON[i][0];
    const xi = BOCAUE_POLYGON[i][1];
    const yj = BOCAUE_POLYGON[j][0];
    const xj = BOCAUE_POLYGON[j][1];
    const intersect = (yi > y) !== (yj > y)
      && x < ((xj - xi) * (y - yi)) / (yj - yi + Number.EPSILON) + xi;
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

  if (BOCAUE_BOUNDS) {
    map.setMaxBounds(BOCAUE_BOUNDS);
  }
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
  const display = (result.display_name || "").split(",").slice(0, 4).join(",").trim();
  if (title && display && !display.toLowerCase().startsWith(title.toLowerCase())) {
    return `${title} - ${display}`;
  }
  return display || title;
}

function formatReverseAddress(data, fallbackLat, fallbackLng) {
  if (!data) return `${fallbackLat.toFixed(6)}, ${fallbackLng.toFixed(6)}`;
  const address = data.address || {};
  const primary = address.amenity
    || address.tourism
    || address.building
    || address.shop
    || address.leisure
    || address.attraction
    || address.hotel
    || address.resort
    || data.name
    || "";
  const road = address.road || address.pedestrian || address.footway || address.path || "";
  const locality = address.suburb
    || address.neighbourhood
    || address.quarter
    || address.hamlet
    || address.village
    || "";
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
    return Number.isFinite(lat) && Number.isFinite(lon) && isPointInsideBocaue(lat, lon);
  });
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
  if (pct >= 75)  return { cls: "badge-nearfull", label: "Near Full" };
  return { cls: "badge-available", label: "Available" };
}

function getProgressBarClass(current, max) {
  const pct = max > 0 ? (current / max) * 100 : 0;
  if (pct >= 100) return "full";
  if (pct >= 75)  return "nearfull";
  return "available";
}

/* ===========================================================
   DASHBOARD — SAFETY CENTERS (right column widget)
   =========================================================== */
function renderDashboardSafetyCenters(centers) {
  const list = document.getElementById("dash-safety-list");
  if (!list) return;
  if (!centers || centers.length === 0) {
    list.innerHTML = '<li class="empty-state-inline">No safety centers available.</li>';
    return;
  }
  list.innerHTML = centers.map((c) => {
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
  }).join("");
}

/* ===========================================================
   DASHBOARD — COMMUNITY FEED
   =========================================================== */
function renderCommunityFeed(posts) {
  const feed = document.getElementById("community-feed");
  if (!feed) return;
  if (!posts || posts.length === 0) {
    feed.innerHTML = '<div class="feed-card" style="padding:32px;text-align:center;color:var(--text-muted);font-style:italic;">No posts yet.</div>';
    return;
  }

  const badgeMap = {
    rescue:     { cls: "badge-rescue",     label: "Rescue Needed" },
    lgu:        { cls: "badge-lgu",        label: "Official" },
    rescued:    { cls: "badge-rescued",    label: "Rescued" },
    inprogress: { cls: "badge-inprogress", label: "In Progress" },
  };

  feed.innerHTML = posts.map((p) => {
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
        ${p.location  ? `<span class="feed-tag"><span class="material-symbols-outlined">location_on</span>${escHtml(p.location)}</span>` : ""}
        ${p.severity  ? `<span class="feed-tag"><span class="dot dot-red"></span>${escHtml(p.severity)}</span>` : ""}
        ${p.waterLevel? `<span class="feed-tag"><span class="dot dot-orange"></span>${escHtml(p.waterLevel)}</span>` : ""}
      </div>
      <div class="feed-actions">
        <button class="feed-action"><span class="material-symbols-outlined">verified</span>Trusted Report</button>
        <button class="feed-action"><span class="material-symbols-outlined">chat_bubble</span>Comment</button>
        <button class="feed-action"><span class="material-symbols-outlined">repeat</span>Repost</button>
      </div>
    </article>`;
  }).join("");
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
    const time = now.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
    const date = now.toLocaleDateString([], { month: "short", day: "2-digit" });
    return `${time}\n${date}`;
  }

  try {
    if (weatherErrorEl) {
      weatherErrorEl.style.display = "none";
      weatherErrorEl.textContent = "";
    }

    const endpoint = "https://api.open-meteo.com/v1/forecast?latitude=14.7982&longitude=120.9260&current=temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m,precipitation_probability&daily=weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max&timezone=Asia%2FManila&forecast_days=5";
    const response = await fetch(endpoint, { headers: { Accept: "application/json" } });
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
    weatherTempEl.textContent = Number.isFinite(current.temperature_2m) ? Math.round(current.temperature_2m) : "--";
    if (weatherRangeEl) {
      const high = Array.isArray(daily.temperature_2m_max) ? daily.temperature_2m_max[0] : null;
      const low = Array.isArray(daily.temperature_2m_min) ? daily.temperature_2m_min[0] : null;
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
      weatherForecastEl.innerHTML = daily.time.slice(0, 5).map((dateStr, idx) => {
        const code = Array.isArray(daily.weather_code) ? daily.weather_code[idx] : null;
        const meta = getWeatherMeta(code);
        const label = new Date(dateStr).toLocaleDateString([], { weekday: "short" }).toUpperCase();
        return `
          <div class="forecast-day">
            <div class="day-label">${label}</div>
            <span class="material-symbols-outlined">${meta.icon}</span>
          </div>
        `;
      }).join("");
    }
  } catch (error) {
    conditionTextEl.textContent = "Weather currently unavailable";
    weatherIconEl.textContent = "cloud_off";
    if (weatherErrorEl) {
      weatherErrorEl.style.display = "block";
      weatherErrorEl.textContent = error.message || "Failed to load weather data.";
    }
  }
}

/* ===========================================================
   INIT
   =========================================================== */
document.addEventListener("DOMContentLoaded", () => {
  /* Logout prompt — sidebar button */
  const logoutSidebarBtn = document.getElementById("logout-trigger-btn");
  if (logoutSidebarBtn) logoutSidebarBtn.addEventListener("click", confirmLogout);

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
  const notificationPageSize = 20;

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
      notificationList.innerHTML = '<div class="notification-empty">No notifications yet.</div>';
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
      notificationList.insertAdjacentHTML("beforeend", '<div class="notification-loading">Loading notifications...</div>');
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
      notificationList.insertAdjacentHTML("beforeend", '<div class="notification-end">You are all caught up.</div>');
    }
  }

  async function loadNotifications(reset = false) {
    if (!notificationList) return;
    if (notificationsLoading) return;

    if (reset) {
      notificationOffset = 0;
      notificationsHasMore = true;
      notificationsCache = [];
      notificationList.innerHTML = '<div class="notification-loading">Loading notifications...</div>';
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
      const response = await fetch(`../includes/fetch_notifications.php?${query.toString()}`, {
        headers: { Accept: "application/json" },
      });
      const data = await response.json();
      if (!response.ok || !data.success) {
        throw new Error(data.message || "Unable to load notifications.");
      }
      const incoming = Array.isArray(data.notifications) ? data.notifications : [];
      notificationsHasMore = Boolean(data.has_more);
      notificationOffset += incoming.length;
      notificationsCache = reset ? incoming : notificationsCache.concat(incoming);
      clearNotificationLoadingState();
      renderNotifications(incoming, !reset);
      renderNotificationEndState();
      updateNotificationBadge(data.unread_count || 0);
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
        throw new Error(data.message || "Unable to mark notifications as read.");
      }
      loadNotifications(true);
    } catch (error) {
      alert(error.message || "Unable to mark notifications as read.");
    }
  }

  async function markSingleAsRead(notificationId) {
    if (!notificationId) return;
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
      const item = event.target.closest(".notification-item[data-notification-id]");
      if (!item) return;

      const notificationId = Number(item.dataset.notificationId || 0);
      const isRead = item.dataset.notificationRead === "1";
      if (notificationId <= 0 || isRead) return;

      try {
        await markSingleAsRead(notificationId);
        item.dataset.notificationRead = "1";
        item.classList.remove("unread");
        const currentBadge = Number(notificationBadge?.textContent || 0);
        if (notificationBadge && Number.isFinite(currentBadge) && currentBadge > 0) {
          updateNotificationBadge(Math.max(currentBadge - 1, 0));
        } else {
          loadNotifications(true);
        }
      } catch (error) {
        alert(error.message || "Unable to mark notification as read.");
      }
    });

    notificationList.addEventListener("scroll", () => {
      const remaining = notificationList.scrollHeight - notificationList.scrollTop - notificationList.clientHeight;
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
  }
});

/* =============================================================
   flood-map.js — Flood Map page logic
   Depends on: Leaflet 1.9.4, script.js (sidebar/theme/modal)
   ============================================================= */

(function () {
  "use strict";

  /* ----------------------------------------------------------
     Road & incident data
     Replace these arrays with fetch() calls to your PHP API
     endpoints when ready (e.g. api/get_flood_roads.php)
  ---------------------------------------------------------- */
  const roads = [
    {
      name: "MacArthur Highway (Bocaue Proper)",
      status: "impassable",
      coords: [[14.806, 120.893], [14.801, 120.901], [14.796, 120.910]],
    },
    {
      name: "Bocaue–Sta. Maria Road",
      status: "impassable",
      coords: [[14.813, 120.906], [14.808, 120.913], [14.803, 120.919]],
    },
    {
      name: "National Road (South Bocaue)",
      status: "impassable",
      coords: [[14.789, 120.899], [14.784, 120.906], [14.780, 120.912]],
    },
    {
      name: "Balagtas Access Road",
      status: "limited",
      coords: [[14.821, 120.888], [14.815, 120.893], [14.810, 120.898]],
    },
    {
      name: "San Juan Road",
      status: "limited",
      coords: [[14.796, 120.882], [14.801, 120.888], [14.806, 120.894]],
    },
    {
      name: "Tambobong Road",
      status: "passable",
      coords: [[14.799, 120.911], [14.794, 120.917], [14.790, 120.923]],
    },
    {
      name: "Bagbaguin Road",
      status: "passable",
      coords: [[14.816, 120.916], [14.811, 120.921], [14.807, 120.927]],
    },
    {
      name: "Manggahan Road",
      status: "passable",
      coords: [[14.823, 120.921], [14.818, 120.927], [14.814, 120.933]],
    },
  ];

  const incidents = [
    { lat: 14.813, lng: 120.906, level: "impassable", desc: "Chest-deep flooding" },
    { lat: 14.801, lng: 120.900, level: "impassable", desc: "Waist-deep flooding" },
    { lat: 14.796, lng: 120.916, level: "impassable", desc: "Flash flood — road closed" },
    { lat: 14.808, lng: 120.921, level: "limited",    desc: "Ankle-deep flooding" },
    { lat: 14.820, lng: 120.911, level: "limited",    desc: "Minor flooding, slow down" },
    { lat: 14.815, lng: 120.894, level: "passable",   desc: "Slight water on road" },
  ];

  /* ----------------------------------------------------------
     Colour map
  ---------------------------------------------------------- */
  const colors = {
    impassable: "#dc2626",
    limited:    "#f59e0b",
    passable:   "#22c55e",
  };

  /* ----------------------------------------------------------
     Status label helper
  ---------------------------------------------------------- */
  function statusLabel(status) {
    if (status === "limited")    return "Limited Access";
    if (status === "impassable") return "Impassable";
    return status.charAt(0).toUpperCase() + status.slice(1);
  }

  /* ----------------------------------------------------------
     Map initialisation
  ---------------------------------------------------------- */
  function initFloodMap() {
    const mapEl = document.getElementById("flood-map");
    if (!mapEl) return;

    const map = L.map("flood-map", {
      zoomControl: true,
      minZoom: 13,
      maxZoom: 19,
    }).setView(BOCAUE_CENTER, 14);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: "© OpenStreetMap contributors",
      maxZoom: 19,
    }).addTo(map);

    applyBocaueBoundaryMask(map);

    let userMarker = null;
    addUseCurrentLocationButton(map, function onCurrentLocation(lat, lng) {
      if (userMarker) {
        userMarker.setLatLng([lat, lng]);
      } else {
        userMarker = L.marker([lat, lng]).addTo(map);
      }
      userMarker.bindPopup("Your current location").openPopup();
      map.flyTo([lat, lng], 16, { duration: 0.7 });
    });

    /* -- Layer buckets -- */
    const roadLayers   = { impassable: [], limited: [], passable: [] };
    const markerLayers = { impassable: [], limited: [], passable: [] };

    /* -- Draw road polylines -- */
    roads.forEach((road) => {
      const line = L.polyline(road.coords, {
        color:     colors[road.status],
        weight:    7,
        opacity:   0.88,
        lineCap:   "round",
        lineJoin:  "round",
      }).bindPopup(
        `<div class="popup-title">${road.name}</div>
         <span class="popup-status ${road.status}">${statusLabel(road.status)}</span>`
      ).addTo(map);

      roadLayers[road.status].push(line);
    });

    /* -- Draw incident circle markers -- */
    incidents.forEach((m) => {
      const marker = L.circleMarker([m.lat, m.lng], {
        radius:      10,
        fillColor:   colors[m.level],
        color:       "#fff",
        weight:      2.5,
        opacity:     1,
        fillOpacity: 0.92,
      }).bindPopup(
        `<div class="popup-title">Flood Incident</div>
         <span class="popup-status ${m.level}">${statusLabel(m.level)}</span>
         <div style="margin-top:5px;font-size:0.75rem;color:#475569;">${m.desc}</div>`
      ).addTo(map);

      markerLayers[m.level].push(marker);
    });

    /* -- Filter toggle logic -- */
    const activeFilters = new Set(["impassable", "limited", "passable"]);

    document.querySelectorAll(".filter-btn").forEach((btn) => {
      btn.addEventListener("click", () => {
        const f = btn.dataset.filter;
        if (activeFilters.has(f)) {
          activeFilters.delete(f);
          btn.classList.remove("active");
          roadLayers[f].forEach((l) => map.removeLayer(l));
          markerLayers[f].forEach((l) => map.removeLayer(l));
        } else {
          activeFilters.add(f);
          btn.classList.add("active");
          roadLayers[f].forEach((l) => l.addTo(map));
          markerLayers[f].forEach((l) => l.addTo(map));
        }
      });
    });

    let searchMarker = null;
    let activeSuggestionResults = [];

    function placeSearchMarker(lat, lng, label) {
      if (searchMarker) {
        searchMarker.setLatLng([lat, lng]);
      } else {
        searchMarker = L.marker([lat, lng]).addTo(map);
      }
      searchMarker.bindPopup(label || `${lat.toFixed(6)}, ${lng.toFixed(6)}`).openPopup();
      map.flyTo([lat, lng], 16, { duration: 0.6 });
    }

    /* -- Search (Nominatim geocoder, Bocaue-bounded) -- */
    const searchInput = document.getElementById("map-search");
    if (searchInput) {
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
              .map((row, idx) => `<option value="${escapeHtml(formatNominatimLabel(row) || query)}" data-idx="${idx}"></option>`)
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
          let target = activeSuggestionResults.find((row) => formatNominatimLabel(row) === query);
          if (!target) {
            const rows = await searchPlacesInBocaue(query, 1);
            target = rows[0];
          }
          if (target) {
            const lat = Number.parseFloat(target.lat);
            const lng = Number.parseFloat(target.lon);
            const label = formatNominatimLabel(target);
            placeSearchMarker(lat, lng, label);
          } else {
            alert("Location not found.");
          }
        } catch (error) {
          alert(error.message || "Search unavailable. Check your connection.");
        }
      });
    }

    /* -- Invalidate map size after sidebar transitions -- */
    setTimeout(() => map.invalidateSize(), 300);
  }

  /* ----------------------------------------------------------
     Boot on DOMContentLoaded
  ---------------------------------------------------------- */
  document.addEventListener("DOMContentLoaded", initFloodMap);
})();

/* =============================================================
   report-flood.js — Report Flood page logic
   Depends on: Leaflet (loaded in section), resident.js
   ============================================================= */

(function () {
  "use strict";

  var map, pinMarker;
  var reverseRequestToken = 0;
  var BOCAUE = [14.7983, 120.9067];

  /* ----------------------------------------------------------
     Custom red pin icon
  ---------------------------------------------------------- */
  function makePinIcon() {
    return L.divIcon({
      className: "",
      html: '<span class="material-symbols-outlined" style="font-size:36px;color:#dc2626;filter:drop-shadow(0 2px 5px rgba(0,0,0,0.4));display:block;line-height:1;margin-left:-4px;">location_on</span>',
      iconSize:    [32, 36],
      iconAnchor:  [16, 36],
      popupAnchor: [0, -38],
    });
  }

  /* ----------------------------------------------------------
     Update the pinned-location field and pin-info strip
  ---------------------------------------------------------- */
  function updateLocationField(address, lat, lng) {
    var display = document.getElementById("pinned-location-display");
    var icon    = document.getElementById("pinned-location-icon");
    var text    = document.getElementById("pinned-location-text");
    var pinInfo    = document.getElementById("pin-info");
    var pinAddress = document.getElementById("pin-address");
    var pinCoords  = document.getElementById("pin-coords");
    var fieldAddr  = document.getElementById("field-address");

    var addressText = address || (lat.toFixed(5) + ", " + lng.toFixed(5));

    if (display) display.classList.add("filled");
    if (icon)    icon.textContent = "location_on";
    if (text)    text.textContent = addressText;
    if (pinInfo)    pinInfo.classList.add("has-pin");
    if (pinAddress) pinAddress.textContent = address || "Location pinned";
    if (pinCoords)  pinCoords.textContent  = lat.toFixed(6) + ", " + lng.toFixed(6);
    if (fieldAddr)  fieldAddr.value = addressText;
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
      }).addTo(map).bindPopup("📍 Your flood report location").openPopup();

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

    map = L.map("report-map", {
      zoomControl: true,
      minZoom: 13,
      maxZoom: 19,
    }).setView(BOCAUE_CENTER, 14);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: "© OpenStreetMap contributors",
      maxZoom: 19,
    }).addTo(map);

    applyBocaueBoundaryMask(map);

    map.on("click", function (e) {
      placePin(e.latlng.lat, e.latlng.lng);
    });

    addUseCurrentLocationButton(map, function onCurrentLocation(lat, lng) {
      map.flyTo([lat, lng], 16, { duration: 0.7 });
      placePin(lat, lng);
    });

    setTimeout(function () { map.invalidateSize(); }, 350);
  }

  /* ----------------------------------------------------------
     Severity ↔ water level sync
     Passable/Rainy locks water level to "none"
  ---------------------------------------------------------- */
  function initSeverityWaterLevelSync() {
    var radios      = document.querySelectorAll('input[name="severity"]');
    var waterSel    = document.getElementById("water-level");
    var waterGroup  = document.getElementById("water-level-group");
    var waterHintEl = document.getElementById("water-level-hint");
    var rescueSection = document.getElementById("rescue-section");
    var rescueNeeded = document.querySelector('input[name="rescue_status"][value="Rescue Needed"]');
    var rescueNotRequired = document.querySelector('input[name="rescue_status"][value="Not Required"]');
    var rescueOptions = document.querySelectorAll('input[name="rescue_status"]');
    var rescueDetails = document.getElementById("rescue-details");
    var rescuePeopleInput = document.getElementById("rescue-people");
    var rescueDescriptionInput = document.getElementById("rescue-note");

    if (!radios.length || !waterSel) return;

    var severityWaterMap = {
      high: ["above", "chest"],
      moderate: ["waist", "knee"],
      passable: ["ankle", "none"],
    };
    var severityHintMap = {
      high: "Allowed for High: Above head, Chest-deep.",
      moderate: "Allowed for Moderate: Waist-deep, Knee-deep.",
      passable: "Allowed for Passable / Rainy: Ankle-deep, No flooding / Rainy only.",
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

    function applyRules(value) {
      var allowedLevels = severityWaterMap[value] || [];
      applyWaterLevelConstraints(allowedLevels);
      if (waterHintEl) {
        waterHintEl.textContent = severityHintMap[value] || "Select flood severity first to see allowed water levels.";
      }

      waterSel.disabled = false;
      if (waterGroup) waterGroup.style.opacity = "1";

      if (value === "passable") {
        if (rescueNotRequired) rescueNotRequired.checked = true;
        if (rescueSection) rescueSection.classList.add("hidden");
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
      } else {
        if (rescueNeeded) rescueNeeded.checked = true;
        if (rescueSection) rescueSection.classList.remove("hidden");
        if (rescueDetails) rescueDetails.classList.add("visible");
        if (rescuePeopleInput) {
          rescuePeopleInput.disabled = false;
          rescuePeopleInput.required = true;
        }
        if (rescueDescriptionInput) {
          rescueDescriptionInput.disabled = false;
        }
      }

      rescueOptions.forEach(function (radio) {
        radio.disabled = true;
      });

      if (value === "passable") {
        var numPeoplePassable = document.getElementById("rescue-people");
        var rescueNotePassable = document.getElementById("rescue-note");
        if (numPeoplePassable) numPeoplePassable.value = "";
        if (rescueNotePassable) rescueNotePassable.value = "";
      }
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
    var details      = document.getElementById("rescue-details");
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
        var numPeople  = document.getElementById("rescue-people");
        var rescueNote = document.getElementById("rescue-note");
        if (numPeople)  {
          numPeople.value  = "";
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
    var checkedRescue = document.querySelector('input[name="rescue_status"]:checked');
    if (checkedRescue) applyRescue(checkedRescue.value);
  }

  /* ----------------------------------------------------------
     Photo preview
  ---------------------------------------------------------- */
  function initPhotoAttach() {
    var photoBtn     = document.getElementById("attach-photo-btn");
    var photoInput   = document.getElementById("photo-input");
    var photoPreview = document.getElementById("photo-preview");
    var photoImg     = document.getElementById("photo-img");
    if (!photoBtn || !photoInput) return;

    photoBtn.addEventListener("click", function () { photoInput.click(); });

    photoInput.addEventListener("change", function (e) {
      var file = e.target.files[0];
      if (!file) return;
      var allowed = ["image/jpeg", "image/png", "image/webp", "image/gif"];
      if (!allowed.includes(file.type)) {
        alert("Please select a JPG, PNG, WEBP, or GIF image.");
        photoInput.value = "";
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
    var form      = document.getElementById("report-form");
    var submitBtn = document.getElementById("submit-btn");
    if (!form || !submitBtn) return;
    form.addEventListener("submit", function () {
      submitBtn.disabled    = true;
      submitBtn.textContent = "Submitting…";
    });
  }

  /* ----------------------------------------------------------
     Success modal
  ---------------------------------------------------------- */
  function initSuccessModal() {
    var okBtn   = document.getElementById("ok-btn");
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
   Fetches from api/fetch-hotlines.php (grouped by barangay)
   and merges with static emergency hotlines.
   Depends on: resident.js (already loaded by main.php)
   ============================================================= */

(function () {
  "use strict";

  /* ----------------------------------------------------------
     Static / national hotlines that don't come from the DB.
     These are always shown and can be filtered by category.
  ---------------------------------------------------------- */
  var STATIC_HOTLINES = [
    { name: "PNP Emergency",        number: "117",           category: "police",    icon: "shield_person",     iconClass: "icon-police",    tagClass: "tag-police",    barangay: "" },
    { name: "NDRRMC Hotline",       number: "8-911",         category: "emergency", icon: "emergency",         iconClass: "icon-emergency", tagClass: "tag-emergency", barangay: "" },
    { name: "Red Cross Bulacan",    number: "0922-999-0000", category: "medical",   icon: "health_and_safety", iconClass: "icon-medical",   tagClass: "tag-medical",   barangay: "" },
    { name: "MDRRMO Bocaue",        number: "0917-111-2222", category: "emergency", icon: "crisis_alert",      iconClass: "icon-emergency", tagClass: "tag-emergency", barangay: "" },
    { name: "Bocaue Police Station",number: "0920-555-6666", category: "police",    icon: "local_police",      iconClass: "icon-police",    tagClass: "tag-police",    barangay: "" },
    { name: "Bocaue Rural Health Unit", number: "0921-777-8888", category: "medical", icon: "local_hospital",  iconClass: "icon-medical",   tagClass: "tag-medical",   barangay: "" },
    { name: "Search & Rescue Team", number: "0923-112-3334", category: "rescue",    icon: "medical_services",  iconClass: "icon-rescue",    tagClass: "tag-rescue",    barangay: "" },
    { name: "Ambulance Services",   number: "0924-445-6667", category: "medical",   icon: "ambulance",         iconClass: "icon-medical",   tagClass: "tag-medical",   barangay: "" },
    { name: "Coast Guard Bulacan",  number: "0926-001-1122", category: "rescue",    icon: "directions_boat",   iconClass: "icon-rescue",    tagClass: "tag-rescue",    barangay: "" },
  ];

  var TAG_LABELS = {
    emergency: "Emergency", medical: "Medical",
    police: "Police", lgu: "LGU", rescue: "Rescue",
    barangay: "Barangay"
  };

  /* All hotlines (static + DB), populated after fetch */
  var allHotlines   = [];
  var activeCategory = "all";

  /* ----------------------------------------------------------
     Fetch DB hotlines from the API
  ---------------------------------------------------------- */
  function fetchHotlines() {
    showLoading(true);
    hideError();

    fetch("api/fetch-hotlines.php")
      .then(function (r) {
        if (!r.ok) throw new Error("HTTP " + r.status);
        return r.json();
      })
      .then(function (json) {
        showLoading(false);
        var dbHotlines = [];

        if (json.success && json.data) {
          // json.data is an object keyed by barangay name
          Object.keys(json.data).forEach(function (barangay) {
            json.data[barangay].forEach(function (h) {
              dbHotlines.push({
                name:      h.hotline_name,
                number:    h.contact_number,
                category:  "lgu",           // DB hotlines are barangay/LGU contacts
                icon:      "account_balance",
                iconClass: "icon-lgu",
                tagClass:  "tag-lgu",
                barangay:  barangay,
              });
            });
          });
        }

        // Merge: static first, then DB entries
        allHotlines = STATIC_HOTLINES.concat(dbHotlines);
        renderCards(getFiltered());
      })
      .catch(function (err) {
        showLoading(false);
        showError("Could not load barangay hotlines. Showing national lines only.");
        // Fallback to static only
        allHotlines = STATIC_HOTLINES.slice();
        renderCards(getFiltered());
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
      var matchCat  = activeCategory === "all" || h.category === activeCategory;
      var matchText = h.name.toLowerCase().includes(q) ||
                      h.number.includes(q) ||
                      (h.barangay && h.barangay.toLowerCase().includes(q));
      return matchCat && matchText;
    });
  }

  /* ----------------------------------------------------------
     Render card list
  ---------------------------------------------------------- */
  function renderCards(data) {
    var list    = document.getElementById("hl-list");
    var noRes   = document.getElementById("hl-no-results");
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
        var tagLabel    = TAG_LABELS[h.category] || h.category;

        card.innerHTML =
          '<div class="hl-card-icon ' + h.iconClass + '">' +
            '<span class="material-symbols-outlined">' + h.icon + '</span>' +
          '</div>' +
          '<div class="hl-card-info">' +
            '<div class="hl-card-name">' + escHtml(h.name) + '</div>' +
            '<div class="hl-card-number">' + escHtml(h.number) + '</div>' +
            (h.barangay ? '<div class="hl-card-barangay">' + escHtml(h.barangay) + '</div>' : '') +
            '<span class="hl-card-tag ' + h.tagClass + '">' + tagLabel + '</span>' +
          '</div>' +
          '<a class="hl-call-btn" href="tel:' + cleanNumber + '">' +
            '<span class="material-symbols-outlined">call</span>' +
            '<span>Call</span>' +
          '</a>';

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
    if (!document.getElementById("hl-list")) return; // not on this page
    initEvents();
    fetchHotlines();
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

  /* ----------------------------------------------------------
     Status config
  ---------------------------------------------------------- */
  var CFG = {
    available: { label: "Available", dot: "g", badge: "available", bar: "#22c55e", pin: "#22c55e" },
    limited:   { label: "Limited",   dot: "y", badge: "limited",   bar: "#f59e0b", pin: "#f59e0b" },
    full:      { label: "Full",      dot: "r", badge: "full",      bar: "#ef4444", pin: "#ef4444" },
  };

  var allCenters = [];
  var mapMarkers = [];
  var map        = null;

  /* ----------------------------------------------------------
     Derive status from occupancy / capacity
  ---------------------------------------------------------- */
  function getStatus(occupancy, capacity) {
    if (capacity <= 0) return "available";
    var pct = (occupancy / capacity) * 100;
    if (pct >= 100) return "full";
    if (pct >= 75)  return "limited";
    return "available";
  }

  /* ----------------------------------------------------------
     SVG drop-pin icon
  ---------------------------------------------------------- */
  function makePin(color) {
    return L.divIcon({
      className: "",
      html: '<svg xmlns="http://www.w3.org/2000/svg" width="28" height="38" viewBox="0 0 32 42">' +
              '<path d="M16 0C7.163 0 0 7.163 0 16c0 10.5 16 26 16 26S32 26.5 32 16C32 7.163 24.837 0 16 0z" fill="' + color + '"/>' +
              '<circle cx="16" cy="16" r="7" fill="white"/>' +
            '</svg>',
      iconSize:    [28, 38],
      iconAnchor:  [14, 38],
      popupAnchor: [0, -40],
    });
  }

  /* ----------------------------------------------------------
     Init Leaflet map
  ---------------------------------------------------------- */
  function initMap() {
    var mapEl = document.getElementById("safety-map");
    if (!mapEl || typeof L === "undefined") return;

    map = L.map("safety-map", {
      zoomControl: true,
      scrollWheelZoom: false,
      minZoom: 13,
      maxZoom: 19,
    }).setView(BOCAUE_CENTER, 14);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: "© OpenStreetMap contributors",
      maxZoom: 19,
    }).addTo(map);

    applyBocaueBoundaryMask(map);

    var userLocationMarker = null;
    addUseCurrentLocationButton(map, function onCurrentLocation(lat, lng) {
      if (userLocationMarker) {
        userLocationMarker.setLatLng([lat, lng]);
      } else {
        userLocationMarker = L.marker([lat, lng]).addTo(map);
      }
      userLocationMarker.bindPopup("Your current location").openPopup();
      map.flyTo([lat, lng], 16, { duration: 0.7 });
    });

    setTimeout(function () { map.invalidateSize(); }, 300);
  }

  /* ----------------------------------------------------------
     Refresh map markers
  ---------------------------------------------------------- */
  function refreshMarkers() {
    mapMarkers.forEach(function (m) { if (map) map.removeLayer(m); });
    mapMarkers = [];

    allCenters.forEach(function (c, i) {
      if (!c.lat || !c.lng || !map) { mapMarkers.push(null); return; }
      var s = CFG[c.status] || CFG.available;
      var m = L.marker([parseFloat(c.lat), parseFloat(c.lng)], {
        icon: makePin(s.pin),
      }).bindPopup(
        '<div style="font-family:Inter,sans-serif;min-width:150px;">' +
          '<strong style="font-size:0.83rem;color:#0f172a;display:block;margin-bottom:2px;">' + escHtml(c.name) + '</strong>' +
          '<span style="font-size:0.7rem;color:#64748b;">' + escHtml(c.address) + '</span><br>' +
          '<span style="font-size:0.7rem;font-weight:700;color:' + s.pin + ';margin-top:4px;display:inline-block;">● ' + s.label + '</span>' +
        '</div>'
      ).addTo(map);

      (function (idx) {
        m.on("click", function () { highlightCard(idx); });
      })(i);

      mapMarkers.push(m);
    });
  }

  /* ----------------------------------------------------------
     Highlight a card
  ---------------------------------------------------------- */
  function highlightCard(idx) {
    document.querySelectorAll(".center-card").forEach(function (el) {
      el.classList.remove("active");
    });
    var card = document.querySelector('.center-card[data-idx="' + idx + '"]');
    if (card) {
      card.classList.add("active");
      card.scrollIntoView({ behavior: "smooth", block: "nearest" });
    }
  }

  /* ----------------------------------------------------------
     Render card list
  ---------------------------------------------------------- */
  function renderCards(data) {
    var list  = document.getElementById("sc-list");
    var noRes = document.getElementById("sc-no-results");
    if (!list) return;

    list.innerHTML = "";

    if (!data.length) {
      if (noRes) noRes.style.display = "block";
      return;
    }
    if (noRes) noRes.style.display = "none";

    data.forEach(function (c) {
      var idx = allCenters.indexOf(c);
      var s   = CFG[c.status] || CFG.available;
      var pct = c.capacity > 0
        ? Math.min(Math.round((c.occupancy / c.capacity) * 100), 100)
        : 0;
      var cardStatusClass = c.status !== "available" ? " " + c.status : "";

      var card = document.createElement("div");
      card.className   = "center-card" + cardStatusClass;
      card.dataset.idx = idx;

      card.innerHTML =
        '<div class="center-top">' +
          '<div class="center-name">' + escHtml(c.name) + '</div>' +
          '<span class="cap-badge ' + s.badge + '">' + s.label + '</span>' +
        '</div>' +
        '<div class="center-addr">' +
          '<span class="material-symbols-outlined">location_on</span>' +
          escHtml(c.address) +
        '</div>' +
        '<div class="cap-row">' +
          '<div class="cap-bar-bg">' +
            '<div class="cap-bar-fill" style="width:' + pct + '%;background:' + s.bar + ';"></div>' +
          '</div>' +
          '<span class="cap-count">' + c.occupancy + '/' + c.capacity + '</span>' +
        '</div>' +
        '<div class="center-bottom">' +
          '<div class="status-pill ' + c.status + '">' +
            '<div class="sdot ' + s.dot + '"></div>' + s.label +
          '</div>' +
          (c.contact
            ? '<a class="center-phone" href="tel:' + escHtml(c.contact.replace(/\D/g, "")) + '">' +
                '<span class="material-symbols-outlined">call</span>' + escHtml(c.contact) +
              '</a>'
            : '') +
        '</div>';

      card.addEventListener("click", function () {
        if (map && c.lat && c.lng) {
          map.flyTo([parseFloat(c.lat), parseFloat(c.lng)], 16, { duration: 0.7 });
          if (mapMarkers[idx]) mapMarkers[idx].openPopup();
        }
        highlightCard(idx);
      });

      list.appendChild(card);
    });
  }

  /* ----------------------------------------------------------
     Fetch centers from API
  ---------------------------------------------------------- */
  function fetchCenters() {
    showLoading(true);
    hideError();

    fetch("api/fetch-safety-centers.php")
      .then(function (r) {
        if (!r.ok) throw new Error("Server returned HTTP " + r.status);
        return r.json();
      })
      .then(function (json) {
        showLoading(false);

        // API returned an error message — show it directly
        if (!json.success) {
          showError("Error: " + (json.message || "Unknown error from server."));
          return;
        }

        // Success but no data yet — show friendly empty state
        if (!json.data || json.data.length === 0) {
          var noRes = document.getElementById("sc-no-results");
          if (noRes) {
            noRes.textContent = "No safety centers have been added yet.";
            noRes.style.display = "block";
          }
          return;
        }

        allCenters = json.data.map(function (c) {
          var occ = parseInt(c.occupancy) || 0;
          var cap = parseInt(c.capacity)  || 0;
          return {
            center_id: c.center_id,
            name:      c.center_name,
            address:   c.full_address || (c.barangay + ", " + c.municipality),
            contact:   c.contact || "",
            occupancy: occ,
            capacity:  cap,
            lat:       c.latitude,
            lng:       c.longitude,
            status:    getStatus(occ, cap),
          };
        });

        refreshMarkers();
        renderCards(allCenters);
      })
      .catch(function (err) {
        showLoading(false);
        // Show the actual JS/network error so it's easier to debug
        showError(
          "Could not load safety centers. " +
          "Check that api/fetch-safety-centers.php exists and db.php path is correct. " +
          "(" + err.message + ")"
        );
      });
  }

  /* ----------------------------------------------------------
     UI helpers
  ---------------------------------------------------------- */
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

  /* ----------------------------------------------------------
     Search
  ---------------------------------------------------------- */
  function initSearch() {
    var input = document.getElementById("sc-search-input");
    if (!input) return;
    input.addEventListener("input", function () {
      var q = input.value.toLowerCase().trim();
      var filtered = allCenters.filter(function (c) {
        return c.name.toLowerCase().includes(q) ||
               c.address.toLowerCase().includes(q);
      });
      renderCards(filtered);
    });
  }

  /* ----------------------------------------------------------
     Boot
  ---------------------------------------------------------- */
  function init() {
    if (!document.getElementById("safety-map")) return;
    initMap();
    initSearch();
    fetchCenters();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }

})();