# Bocaue Flood Information System (BFIS)

Web application for the Municipality of Bocaue focused on flood monitoring, community communication, evacuation coordination, and role-based access for local government, responders, and residents.

## Features

- **Authentication** — Email and password login with bcrypt verification, session hardening (regenerate ID, cookie flags), and multi-step resident self-registration.
- **Account verification** — New registrations are created with `is_verified = 0`; an LGU administrator must verify accounts before users can sign in.
- **Role-based portals**
  - **LGU** — Dashboard, user management, announcements, evacuation centers and evacuees, hotlines, flood map, data monitoring, report verification, and community views.
  - **Rescuer** — Operational dashboard, evacuation center tools, flood monitoring map, hotlines, and community information.
  - **Resident** — Dashboard with live flood monitoring map and weather widget, full flood map with search/filters, flood report submission (map pin + photo), database-driven emergency hotlines, and safety centers map with capacity/occupancy modals.
- **LGU report verification workflow**
  - Report list now loads from relational status tables (`reports.status_id` + `report_status.status_name`).
  - LGU actions use explicit **Approve** / **Reject** confirmation before update.
  - Verification table supports report image preview (click-to-open lightbox) and rescue detail columns (`rescue_people_count`, `rescue_description`).
  - Status badges are color-coded (Pending/Approved/Rejected) and actions are disabled after final status.
- **Resident notification center (Facebook-style panel)**
  - Larger, modern, scrollable dropdown with sticky header and unread highlighting.
  - Supports **single-item read** and **mark all as read** actions.
  - Uses lazy loading (`limit` + `offset`) for better performance on large feeds.
  - Supports multiple notification types: `report_update`, `announcement`, and `alert` (nearby flood activity).
- **Location-aware notifications**
  - Announcement notifications are filtered to resident-relevant scope (global + barangay-targeted).
  - Nearby flood alerts are generated from reports in the resident's barangay (or coordinate-near fallback when available).
- **Relational notifications integrity**
  - Notifications are tied to valid `users` and `reports` via foreign keys where schema allows.
  - Notification inserts and updates use prepared statements and user ownership checks.
- **REST-style PHP endpoints** — JSON and form operations under `api/` and `resident/api/` for announcements, users, centers, evacuees, hotlines, safety centers, and related actions.

## Requirements

- [XAMPP](https://www.apachefriends.org/) (or equivalent) with **PHP 7.4+** (recommended: PHP 8.x) and **MySQL / MariaDB**
- A MySQL database named **`flood_information`** (configurable)
- Web server document root pointing at this project folder so application paths such as `/main/assets/css/styles.css` resolve correctly

## Installation

1. Clone or copy the project into your web root, for example:
   - `C:\xampp\htdocs\bocaue-flood-system`
2. Create the MySQL database (default name: `flood_information`).
3. Import your schema and seed data. This repository does not ship a SQL dump; use your team’s database backup or migration scripts if you have them.
4. Edit database credentials in `config/db.php`:

```php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "flood_information";
```

5. Start **Apache** and **MySQL** from the XAMPP control panel.
6. Open the app in a browser, for example:
   - Login: `http://localhost/bocaue-flood-system/main/login.php`
   - Resident dashboard (after login): `http://localhost/bocaue-flood-system/resident/main.php?page=dashboard`
   - Resident safety centers: `?page=safety-centers`

Adjust the URL path if your folder name or virtual host differs. Resident assets use cache-busting on `resident.js` via `filemtime` in `resident/main.php` — hard-refresh after JS changes if needed.

## Configuration

| File | Purpose |
|------|---------|
| `config/db.php` | MySQL credentials; exposes `$conn` (mysqli) and `$pdo` (PDO). |
| `config/session.php` | Session lifetime and cache-control headers for protected areas. |
| `config/auth.php` | Requires login; optionally enforces `$requiredRole` before including role-specific pages. |

## Entry points

| Path | Description |
|------|-------------|
| `main/login.php` | Login form |
| `backend/login.php` | POST handler for authentication (not opened directly in normal use) |
| `main/register_step1.php` | Start of resident registration wizard |
| `lgu/index.php` | LGU portal entry (redirects to `lgu/main.php`) |
| `rescuer/index.php` | Rescuer portal entry |
| `resident/index.php` | Resident portal entry |
| `resident/main.php` | Resident shell (`?page=dashboard`, `flood-map`, `report-flood`, `safety-centers`, `hotlines`) |

## Resident portal pages

| Page | URL | Description |
|------|-----|-------------|
| Dashboard | `resident/main.php?page=dashboard` | Flood monitoring map (verified reports), Open-Meteo weather, safety center summary, community feed (announcements + reports from DB) |
| Flood Map | `?page=flood-map` | Full-screen Bocaue map with severity filters, location search (Nominatim), and approved report markers |
| Report Flood | `?page=report-flood` | Submit new flood reports with map pin, address, severity, water level, optional rescue details and image |
| Safety Centers | `?page=safety-centers` | Evacuation center list + interactive map; markers and modal data from `evacuation_centers` / `locations` |
| Emergency Hotlines | `?page=hotlines` | Barangay hotlines loaded from `hotlines` table (search/filter in UI) |

## Notification endpoints

| Path | Method | Description |
|------|--------|-------------|
| `includes/fetch_notifications.php` | `GET` | Returns resident notifications, unread count, pagination metadata, and auto-generates location-relevant announcement/alert notifications when needed. |
| `includes/mark_notification_read.php` | `POST` | Marks a single notification as read for the authenticated resident (`notification_id` required). |
| `includes/mark_notifications_read.php` | `POST` | Marks all unread notifications as read for the authenticated resident. |
| `includes/update_report_status.php` | `POST` | LGU-only report approve/reject action; updates report status and creates corresponding resident notification. |

## Project structure (overview)

```
bocaue-flood-system/
├── api/                 # HTTP endpoints (CRUD, JSON responses)
├── backend/             # Server-side login processor
├── config/              # Database and session/auth helpers
├── includes/            # Shared data-fetch scripts used by dashboards
├── lgu/                 # LGU UI, assets, and sections
├── main/                # Public login, registration, shared main assets
├── phpmailer/           # PHPMailer library (email)
├── rescuer/             # Rescuer portal
└── resident/            # Resident portal
    ├── api/             # Resident JSON endpoints (hotlines, safety centers)
    ├── assets/js/       # resident.js — shared maps, dashboard, hotlines, safety centers
    └── sections/        # Page fragments included by main.php
```

## Maps (LGU, Rescuer, and Resident)

LGU, Rescuer, and Resident portals use **[Leaflet 1.9.4](https://leafletjs.com/)** with **[OpenStreetMap](https://www.openstreetmap.org/)** raster tiles (`https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png`). Map views are scoped to **Bocaue, Bulacan, Philippines** using shared bounds, a municipal boundary polygon, and (on operational maps) a **Use My Current Location** control that rejects coordinates outside Bocaue.

### Geographic scope (Bocaue)

| Setting | Value | Used by |
|--------|--------|---------|
| Default center | `14.7982, 120.926` (lat, lng) | LGU, Rescuer, and Resident flood maps |
| `maxBounds` (SW → NE) | `14.747, 120.865` → `14.845, 120.99` | Flood maps, evac center modals (LGU & Rescuer) |
| Municipal polygon (11 vertices) | Approx. `14.748–14.844` N, `120.867–120.988` E | Flood maps, evac modals (mask outside Bocaue) |
| Default zoom | `14` (min `13`, max `19`) | Flood maps |
| Add-center picker (LGU only) | Center `14.805, 120.95`; bounds `14.77, 120.91` → `14.84, 120.99` | Data Management → Add Evacuation Center modal |

Polygon vertices (lat, lng), in order:

`[14.844, 120.888]`, `[14.839, 120.924]`, `[14.831, 120.963]`, `[14.816, 120.986]`, `[14.787, 120.988]`, `[14.764, 120.975]`, `[14.751, 120.948]`, `[14.748, 120.91]`, `[14.757, 120.882]`, `[14.779, 120.867]`, `[14.809, 120.868]`

### LGU maps

| Screen | Portal page | DOM target | JavaScript | Data source |
|--------|-------------|------------|------------|-------------|
| **Flood Monitoring Map** | Data Monitoring (`?page=data-monitoring`) | `#flood-map` | `lgu/assets/js/flood-map.js` | `includes/fetch_flood_severity_map.php` |
| **Evacuation center location** | Data Monitoring → Evacuation Centers table (click row) | `#evac-modal-map` | `lgu/assets/js/modals/evac-map-modal.js` | `includes/fetch_evac_monitor.php` |
| **Pick center on map** | Data Management → Add Evacuation Center modal | `#center-map` | `lgu/assets/js/modals/datamanagement_modals.js` | User click / GPS; reverse geocode via [Nominatim](https://nominatim.openstreetmap.org/) |
| **Community report location** | Community (`?page=community`) | `#map`, `#full-map` (modals) | Inline script in `lgu/sections/community.php` | Per-post `data-lat` / `data-lng` from `includes/fetch_communityReports.php` |

**Flood map markers** — **Approved** flood reports only (`report_status.status_name = 'Approved'`), with non-null `locations.latitude` / `longitude`. Severity comes from `flood_severity.severity_id`:

| `severity_id` | Label | Marker color |
|---------------|--------|----------------|
| 1 | Passable | Green (`#22c55e`) |
| 2 | Limited Access | Yellow (`#eab308`) |
| 3 | Impassable | Red (`#ef4444`) |

Joined tables: `reports` → `locations` → `barangays`, plus `flood_severity`, `report_status`, optional `water_levels` and `users` (reporter name). Popups show barangay, municipality, address, water level, description, and report date.

**Evacuation center markers** — `evacuation_centers` joined to `locations` and `barangays`; coordinates from `locations.latitude` / `longitude`, address from `locations.full_address` or barangay/municipality/province fallback.

### Rescuer maps

| Screen | Portal page | DOM target | JavaScript | Data source |
|--------|-------------|------------|------------|-------------|
| **Flood Monitoring Map** | Flood Monitoring Map (`?page=flood-monitoring-map`) | `#flood-map-placeholder` | `rescuer/assets/js/flood-map-rescuer.js` | `includes/fetch_flood_severity_map.php` (same API as LGU) |
| **Evacuation center location** | Dashboard evac list & Evacuation Center page (click center) | `#rescuer-evac-modal-map` | `rescuer/assets/js/rescuer-evac.js` | `includes/fetch_evac_monitor.php` |
| **Community report location** | Community (`?page=community`) | `#map`, `#full-map` (modals) | Inline script in `rescuer/sections/community.php` | Per-post coordinates from community feed |

Rescuer flood monitoring reuses the same Bocaue center, bounds, polygon, severity legend, and marker rules as the LGU flood map. The map replaces the `#flood-map-placeholder` element at runtime (Leaflet container).

### Shared map API endpoints

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `includes/fetch_flood_severity_map.php` | GET (JSON) | Approved reports with coordinates, severity colors, water level, address, reporter name |
| `includes/fetch_evac_monitor.php` | GET (JSON) | Evacuation centers with capacity, occupancy, address, and coordinates (LGU/Rescuer tables/modals) |
| `resident/api/fetch-safety-centers.php` | GET (JSON) | All evacuation centers for resident Safety Centers page (list + map) |
| `resident/api/fetch-hotlines.php` | GET (JSON) | Barangay hotlines for resident hotlines page (optional AJAX refresh) |
| `api/fetch_hotlines.php` | GET (JSON) | Hotlines for LGU/Rescuer modules |

### Map-related files (quick reference)

```
includes/fetch_flood_severity_map.php      # Verified flood report markers (all portals)
includes/fetch_evac_monitor.php            # Evacuation center list + coordinates
lgu/assets/js/flood-map.js                 # LGU Data Monitoring flood map
lgu/assets/js/modals/evac-map-modal.js     # LGU evac center map modal
lgu/assets/js/modals/datamanagement_modals.js  # LGU add-center map picker
rescuer/assets/js/flood-map-rescuer.js     # Rescuer flood monitoring map
rescuer/assets/js/rescuer-evac.js          # Rescuer evac center map modal
resident/assets/js/resident.js             # Resident maps, dashboard flood map, hotlines, safety centers
resident/api/fetch-safety-centers.php
resident/api/fetch-hotlines.php
lgu/sections/data-monitoring.php
rescuer/sections/flood-monitoring-map.php
resident/sections/dashboard.php            # Dashboard flood monitoring map
resident/sections/flood-map.php
resident/sections/safety-centers.php
```

### Resident maps

Shared map logic lives in `resident/assets/js/resident.js`: `createBocaueLeafletMap()`, `applyBocaueBoundaryMask()`, `fitMapToBocaueBoundary()`, `addUseCurrentLocationButton()`, and Nominatim helpers for address search/reverse geocode within Bocaue.

| Screen | Portal page | DOM target | Data source | Behavior |
|--------|-------------|------------|-------------|----------|
| **Flood Monitoring Map** | Dashboard (`?page=dashboard`) | `#dashboard-flood-map` | `includes/fetch_flood_severity_map.php` | Same verified reports as LGU; on-map **Flood Severity** legend; filter buttons (Impassable / Limited Access / Passable); auto-refresh every 30s; click marker for popup |
| **Flood Map** | `?page=flood-map` | `#flood-map` | `includes/fetch_flood_severity_map.php` | Full map with filter bar, Bocaue location search, severity-colored pins |
| **Report Flood** | `?page=report-flood` | `#report-map` | User pin + Nominatim | Pick location on map or **Use My Current Location**; submits to `reports` via form POST |
| **Safety Centers** | `?page=safety-centers` | `#safety-map` | `resident/api/fetch-safety-centers.php` | Fully database-driven markers; optional address geocoding when lat/lng missing; click pin for detail modal |

#### Dashboard flood map (verified reports)

- Loads only **Approved** reports with valid coordinates (same rules as LGU Data Monitoring).
- Marker popup shows: severity label (from `flood_severity`), barangay/municipality, address, water level, description, report date, and reporter name.
- UI matches LGU-style monitoring: severity legend overlay, pill legend, and toggle filters below the map.
- Report count label updates dynamically (e.g. `1 verified report on map`).

#### Safety centers (database-driven)

- **No hard-coded** centers, coordinates, or addresses in JavaScript.
- API joins `evacuation_centers` → `locations` → `barangays`; contact from `evacuation_centers.contact_number` when present, else first barangay hotline.
- Optional columns detected at runtime: `description`, `operating_hours`, `contact_number` on `evacuation_centers`.
- Map pins use stored `locations.latitude` / `longitude` when available; otherwise Nominatim geocodes `full_address` (session cache only, does not overwrite DB).
- List + map refresh every 30s; modal shows name, address, contact, capacity, occupancy, availability, and optional hours/description.
- Initial view fits the Bocaue municipal boundary; markers use a raised pane so pins stay above the boundary mask.

#### Emergency hotlines

- `resident/sections/hotlines.php` queries `hotlines` + `barangays` server-side for first paint.
- `resident/api/fetch-hotlines.php` provides the same data as JSON for dynamic reload.
- No static fallback hotline list in JavaScript.

#### Resident flood report popups (shared with flood map page)

Popup header color and label follow `severity_id` (1 Passable / 2 Limited Access / 3 Impassable). Content is built from the API row: `barangay_name`, `municipality`, `full_address`, `water_level`, `description`, `created_at`, `reported_by`.

## Security notes

- Keep `generate_hash.php` **out of production** or delete it after local use; it generates password hashes and should not be publicly accessible on a live server.
- Use **HTTPS** in production so session cookies can use the `Secure` flag consistently with `config/db.php` and related session setup.
- Restrict file permissions on `config/db.php` and any files that contain secrets.
- Keep SQL table/column names aligned with this project schema (`users.user_id`, `reports.report_id`, `report_status.status_id`) before applying custom migrations.

## Development utilities

- **`generate_hash.php`** — Local helper to produce a bcrypt hash for seeding or updating a user password in the database (edit the `$password` variable inside the file, run once in the browser, then remove or protect the file).

## License

No license file is included in this repository. Add a `LICENSE` file and update this section if you intend to distribute or open-source the project.
