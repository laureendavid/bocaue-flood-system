# Bocaue Flood Information System (BFIS)

Web application for the **Municipality of Bocaue, Bulacan** focused on flood monitoring, community communication, evacuation coordination, and role-based access for local government units (LGU), responders, and residents.

---

## Table of contents

1. [Overview](#overview)
2. [System architecture](#system-architecture)
3. [Technology stack](#technology-stack)
4. [User roles and portals](#user-roles-and-portals)
5. [Core features](#core-features)
6. [Data model](#data-model)
7. [Notification system](#notification-system)
8. [Registration and verification](#registration-and-verification)
9. [File uploads and media](#file-uploads-and-media)
10. [API and shared endpoints](#api-and-shared-endpoints)
11. [Maps and geospatial scope](#maps-and-geospatial-scope)
12. [Project structure](#project-structure)
13. [Requirements](#requirements)
14. [Installation](#installation)
15. [Configuration](#configuration)
16. [Entry points](#entry-points)
17. [Security notes](#security-notes)
18. [Development utilities](#development-utilities)
19. [License](#license)

---

## Overview

BFIS is a **PHP + MySQL** monolith with three authenticated portals (LGU, Rescuer, Resident) and a public authentication/registration area. Each portal uses a **shell page** (`main.php`) that loads **section fragments** based on a `?page=` query parameter. JSON endpoints under `api/`, `includes/`, and `resident/api/` supply data to dashboards, maps, and modals.

Primary goals:

- Let residents **report floods** with map pins, severity, water level, optional rescue details, and photos.
- Let LGU staff **verify reports** (approve/reject) before they appear on public monitoring maps.
- Broadcast **announcements** and **nearby flood alerts** to residents via an in-app notification center.
- Provide **evacuation center** monitoring, **hotlines**, and **community feeds** across roles.
- Enforce **role-based access** and **account verification** before login.

---

## System architecture

### High-level components

```mermaid
flowchart TB
    subgraph clients [Browser clients]
        Public[Public auth / registration]
        LGU[LGU portal]
        Rescuer[Rescuer portal]
        Resident[Resident portal]
    end

    subgraph app [BFIS PHP application]
        Auth[config/auth.php + session]
        Sections[Portal sections PHP fragments]
        API[api/ + includes/ JSON endpoints]
        Services[includes/*_service.php helpers]
    end

    subgraph external [External services]
        OSM[OpenStreetMap tiles]
        Nominatim[Nominatim geocoding]
        Cloudinary[Cloudinary image CDN]
        SMTP[SMTP mail Brevo / PHPMailer]
        OpenMeteo[Open-Meteo weather API]
    end

    subgraph data [MySQL]
        DB[(flood_information)]
    end

    Public --> Auth
    LGU --> Auth
    Rescuer --> Auth
    Resident --> Auth
    Auth --> Sections
    Sections --> API
    API --> Services
    Services --> DB
    API --> DB
    Sections --> DB
    Resident --> OSM
    Resident --> Nominatim
    Resident --> OpenMeteo
    Services --> Cloudinary
    Services --> SMTP
```

### Request flow (authenticated portal page)

```mermaid
sequenceDiagram
    participant B as Browser
    participant M as main.php
    participant A as config/auth.php
    participant S as sections/*.php
    participant J as resident.js / role JS
    participant E as includes/*.php API

    B->>M: GET main.php?page=dashboard
    M->>A: Require session + role
    A-->>M: user_id, role OK
    M->>S: include section fragment
    S-->>B: HTML shell + content
    B->>J: DOMContentLoaded
    J->>E: fetch JSON (maps, notifications, etc.)
    E-->>J: JSON response
    J-->>B: Render UI
```

### Notification architecture (event-driven)

Notifications are **created only when a business event occurs**, not when a page is loaded or refreshed.

```mermaid
flowchart LR
    subgraph events [Trigger events]
        A1[LGU posts announcement]
        A2[Resident submits flood report]
        A3[LGU approves/rejects report]
    end

    subgraph create [Creation layer]
        NS[includes/notifications_service.php]
    end

    subgraph store [Storage]
        N[(notifications table)]
    end

    subgraph read [Read layer]
        FN[fetch_notifications.php]
        MR[mark_notification_read.php]
        MA[mark_notifications_read.php]
    end

    subgraph ui [Resident UI]
        JS[resident.js notification panel]
    end

    A1 --> NS
    A2 --> NS
    A3 --> NS
    NS -->|INSERT with NOT EXISTS| N
    JS --> FN
    FN -->|SELECT only| N
    JS --> MR
    JS --> MA
    MR --> N
    MA --> N
```

| Event | Created by | Type | Recipients |
|-------|------------|------|------------|
| Announcement published | `api/add_announcement.php` | `announcement` | All residents, or residents in target barangay |
| Flood report submitted | `resident/sections/report-flood.php` | `alert` | Other residents in reporter's barangay (excludes reporter) |
| Report approved/rejected | `includes/update_report_status.php` | `report_update` | Resident who submitted that report |

`includes/fetch_notifications.php` is **read-only** (SELECT + counts). `includes/sync_notifications.php` is a legacy no-op endpoint kept for compatibility; it does **not** insert rows.

---

## Technology stack

| Layer | Technology |
|-------|------------|
| Runtime | PHP 7.4+ (recommended 8.x) |
| Database | MySQL / MariaDB via **mysqli** and **PDO** |
| Frontend | HTML, CSS, vanilla JavaScript |
| Maps | [Leaflet 1.9.4](https://leafletjs.com/) + OpenStreetMap tiles |
| Geocoding | [Nominatim](https://nominatim.openstreetmap.org/) (search/reverse within Bocaue) |
| Weather | [Open-Meteo](https://open-meteo.com/) (resident dashboard widget) |
| Email | PHPMailer + SMTP (Brevo by default) |
| Images | Cloudinary PHP SDK + HTTP upload fallback; local `uploads/` fallback |
| Dependencies | Composer (`cloudinary/cloudinary_php`); dev: Psalm, PHP_CodeSniffer |

---

## User roles and portals

| Role | Portal path | Purpose |
|------|-------------|---------|
| **LGU** | `lgu/main.php` | Administration, verification, data management, monitoring |
| **Rescuer** | `rescuer/main.php` | Operational flood map, evacuation centers, hotlines, community |
| **Resident** | `resident/main.php` | Report floods, view maps, safety centers, hotlines, notifications |
| **Public** | `main/login.php`, `main/register_step*.php` | Login and self-registration |

Access control is enforced in `config/auth.php`: unauthenticated users redirect to login; wrong role redirects to that role's dashboard.

### LGU pages (`?page=`)

| Page | Description |
|------|-------------|
| `dashboard` | Overview stats and quick links |
| `user-management` | Create, edit, verify, and manage users |
| `report-verification` | Review pending flood reports; approve/reject with confirmation |
| `data-monitoring` | Flood severity map (approved reports) + evacuation monitor |
| `data-management` | Evacuation centers, hotlines, announcements CRUD |
| `community` | Community feed with report location maps |

### Rescuer pages

| Page | Description |
|------|-------------|
| `dashboard` | Operational summary |
| `flood-monitoring-map` | Approved flood reports on Bocaue map |
| `evacuation-center` | Evacuation center list and map modals |
| `hotlines` | Emergency hotlines by barangay |
| `community` | Community reports and announcements |

### Resident pages

| Page | Description |
|------|-------------|
| `dashboard` | Flood map, weather widget, safety center summary, community feed |
| `flood-map` | Full-screen map with severity filters and location search |
| `report-flood` | Submit flood report (map pin, severity, water level, rescue option, photo) |
| `safety-centers` | Evacuation centers list + map from database |
| `hotlines` | Barangay hotlines (loaded via `api/fetch_hotlines.php`) |
| `account-settings` | Profile, password, partial field updates, profile photo |

---

## Core features

### Authentication and sessions

- Email/password login with **bcrypt** verification (`backend/login.php`).
- Session hardening: `HttpOnly`, `SameSite=Lax`, `Secure` when HTTPS, 30-minute GC lifetime.
- Role stored in `$_SESSION['role']`; portals set `$requiredRole` before including `config/auth.php`.

### Flood reporting (resident)

- Map pin within Bocaue bounds; optional **Use My Current Location**.
- Severity: **High** (impassable), **Moderate** (limited access), **Passable / Rainy**.
- Water level constrained by severity; rescue section shown for high/moderate (defaults to **Rescue Needed**, user may choose **No Rescue Needed**).
- Optional report photo uploaded to Cloudinary (or local `uploads/reports/` fallback).
- Creates `locations` + `reports` rows; triggers nearby **alert** notifications for other residents in the barangay.

### Report verification (LGU)

- Pending reports listed from `reports` joined to `report_status`.
- **Approve** / **Reject** with confirmation modal.
- Updates `reports.status_id`, `verified_by`, `verified_at`.
- Creates one **`report_update`** notification for the reporting resident.
- Only **Approved** reports appear on flood monitoring maps.

### Announcements

- LGU creates announcements (global or barangay-targeted) via `api/add_announcement.php`.
- Fan-out creates per-resident **`announcement`** notifications (idempotent insert).

### Evacuation and hotlines

- Evacuation centers linked to `locations` and `barangays` (capacity, occupancy, contact).
- Hotlines stored per barangay; shared JSON API for all portals.

### Account settings

- Partial profile updates (only changed fields validated/saved).
- Profile photos via Cloudinary with local fallback (`uploads/profiles/`).

---

## Data model

The repository does not ship a SQL dump; schema is maintained in your team database. Core tables and relationships:

```mermaid
erDiagram
    users ||--o{ reports : submits
    users ||--o{ notifications : receives
    users }o--|| barangays : belongs_to
    reports }o--|| locations : at
    reports }o--|| report_status : has
    reports }o--|| flood_severity : has
    reports }o--|| water_levels : has
    reports }o--|| rescue_status : has
    locations }o--|| barangays : in
    evacuation_centers }o--|| locations : at
    announcements }o--o| barangays : targets
    notifications }o--o| reports : references

    users {
        int user_id PK
        string email
        string role
        int barangay_id FK
        tinyint is_verified
    }
    reports {
        int report_id PK
        int user_id FK
        int location_id FK
        int status_id FK
    }
    notifications {
        int id PK
        int user_id FK
        int report_id FK
        string type
        tinyint is_read
    }
```

| Table / area | Purpose |
|--------------|---------|
| `users` | Accounts (LGU, Rescuer, Resident); `is_verified` gates login |
| `barangays` | Bocaue barangay reference data |
| `locations` | Lat/lng, address, barangay for reports and centers |
| `reports` | Flood reports with severity, water level, rescue fields, image URL |
| `report_status` | Pending / Approved / Rejected lookup |
| `flood_severity`, `water_levels`, `rescue_status` | Report classification lookups |
| `announcements` | LGU posts; optional `barangay_id` for targeting |
| `evacuation_centers` | Safety centers with capacity/occupancy |
| `hotlines` | Emergency numbers per barangay |
| `notifications` | Per-user notification feed |
| `email_verifications` | Registration and account verification tokens |

---

## Notification system

### What residents see

`fetch_notifications.php` returns **all notification rows** for the logged-in `user_id`, newest first, paginated (`limit` default 20, max 50).

| Type | Meaning |
|------|---------|
| `announcement` | LGU announcement relevant to the resident (global or their barangay) |
| `alert` | Nearby flood report from another resident in the same barangay |
| `report_update` | The resident's own report was approved or rejected |

### UI behavior (`resident/assets/js/resident.js`)

- Facebook-style dropdown: unread highlighting, **mark one read**, **mark all read**.
- Lazy loading with `offset` / `has_more`.
- Badge count from server `unread_count` (SQL `COUNT` where `is_read = 0`).
- Client-side `seenNotificationIds` deduplication during pagination only (display safety).

### Idempotent creation (`includes/notifications_service.php`)

All inserts use **`INSERT … SELECT … WHERE NOT EXISTS`** (or equivalent) so duplicate events do not create duplicate rows:

- **Announcements:** unique per `user_id` + title + message + `created_at`
- **Alerts:** unique per `user_id` + `report_id`
- **Report updates:** unique per `user_id` + `report_id` + type `report_update`

### Endpoints

| Path | Method | Description |
|------|--------|-------------|
| `includes/fetch_notifications.php` | GET | List notifications, unread count, pagination (**read-only**) |
| `includes/mark_notification_read.php` | POST | Mark one notification read (`notification_id`) |
| `includes/mark_notifications_read.php` | POST | Mark all unread as read; returns updated `unread_count` |
| `includes/update_report_status.php` | POST | LGU approve/reject report + create `report_update` notification |
| `includes/sync_notifications.php` | GET | Auth check only; **no inserts** (legacy compatibility) |

---

## Registration and verification

Residents register through a **three-step wizard**:

```mermaid
flowchart TD
    S1[Step 1: Personal info + barangay + map pin]
    S2[Step 2: Password + valid ID upload]
    S3[Step 3: Review + email verification trigger]
    Mail[Verification email with token link]
    V[main/verify.php]
  Complete[Account created is_verified=1]
    Login[main/login.php]

    S1 --> S2 --> S3 --> Mail --> V
    V -->|Confirm token| Complete --> Login
```

| Step | File | Details |
|------|------|---------|
| 1 | `main/register_step1.php` | Name, email, phone, DOB, barangay, address, map coordinates, profile photo |
| 2 | `main/register_step2.php` | Password, valid ID image |
| 3 | `main/register_step3.php` | Review; sends verification email |
| Verify | `main/verify.php` | Token confirmation; completes account via `includes/registration_service.php` |

Session staging holds registration data between steps. Email uses PHPMailer (`config/mail.php`). Expired verification tokens and stale unverified accounts are cleaned by `includes/db_cron.php` (invoked from `config/db.php` on each request).

LGU administrators can also verify accounts from **User Management** (`is_verified = 0` until approved).

---

## File uploads and media

| Use case | Primary storage | Fallback |
|----------|-----------------|----------|
| Registration profile / valid ID | Cloudinary (`config/cloudinary.php`) | `uploads/reg_temp/`, staged files |
| Account profile photo | Cloudinary | `uploads/profiles/` |
| Flood report photo | Cloudinary | `uploads/reports/` |

Upload pipeline (`includes/cloudinary_upload.php`):

1. Validate MIME/size; stage to temp.
2. Try Cloudinary SDK or HTTP API upload.
3. On failure, copy to local `uploads/` and store relative path in DB.

Configure via environment variable `CLOUDINARY_URL` or credentials in `config/cloudinary.php` (use env vars in production).

---

## API and shared endpoints

### `api/` (LGU-focused JSON/form handlers)

| Endpoint | Purpose |
|----------|---------|
| `add_announcement.php` | Create announcement + notify residents |
| `delete_announcement.php`, `edit_announcement.php` | Announcement CRUD |
| `save_center.php`, `edit_center.php`, `delete_center.php` | Evacuation centers |
| `add_hotlines.php`, `edit_hotline.php`, `delete_hotline.php` | Hotlines |
| `handle_add_user.php`, `delete_user.php`, `change_role.php` | User management |
| `add_evacuee.php`, `remove_evacuee.php`, `remove_all_evacuees.php`, `get_evacuees.php` | Evacuee tracking |
| `update_rescue_status.php` | Update report rescue status |
| `fetch_hotlines.php` | Hotlines JSON (LGU, Rescuer, Resident) |

### `includes/` (shared read/update scripts)

| Endpoint | Purpose |
|----------|---------|
| `fetch_flood_severity_map.php` | Approved reports for maps |
| `fetch_evac_monitor.php` | Evacuation centers for LGU/Rescuer |
| `fetch_communityReports.php`, `fetch_commAnnouncement.php` | Community feeds |
| `fetch_reports.php`, `fetch_report_stats.php` | Report lists and stats |
| `fetch_users.php`, `fetch_barangays.php` | User/barangay data |
| `fetch_announcements.php`, `fetch_archived_announcements.php` | Announcements |
| `notifications_service.php` | Notification creation helpers (included, not called directly) |

### `resident/api/`

| Endpoint | Purpose |
|----------|---------|
| `fetch-safety-centers.php` | Evacuation centers for resident safety page |
| `fetch-hotlines.php` | Alternate hotlines path (primary client uses `api/fetch_hotlines.php`) |

### `backend/`

| File | Purpose |
|------|---------|
| `login.php` | POST login handler |
| `update_account_settings.php` | Account settings POST (shared pattern) |
| `set_new_password.php` | Password reset completion |

---

## Maps and geospatial scope

All operational maps are scoped to **Bocaue, Bulacan, Philippines** using shared bounds, a municipal boundary polygon, and coordinate validation on report submission.

| Setting | Value |
|---------|--------|
| Default center | `14.7982, 120.926` |
| `maxBounds` (SW → NE) | `14.747, 120.865` → `14.845, 120.99` |
| Default zoom | 14 (min 13, max 19) |
| Coverage check (reports) | Lat `14.747–14.845`, Lng `120.865–120.990` |

### Map implementations by portal

| Portal | Key JS | Flood map data |
|--------|--------|----------------|
| LGU | `lgu/assets/js/flood-map.js` | `fetch_flood_severity_map.php` |
| Rescuer | `rescuer/assets/js/flood-map-rescuer.js` | Same |
| Resident | `resident/assets/js/resident.js` | Same |

**Flood map markers** — only **Approved** reports with valid coordinates. Severity colors:

| `severity_id` | Label | Color |
|---------------|--------|-------|
| 1 | Passable | Green `#22c55e` |
| 2 | Limited Access | Yellow `#eab308` |
| 3 | Impassable | Red `#ef4444` |

Municipal polygon vertices and per-screen DOM targets are documented inline in map JS files under each portal's `assets/js/` directory.

---

## Project structure

```
bocaue-flood-system/
├── api/                      # LGU JSON/form endpoints (announcements, centers, users, …)
├── assets/                   # Shared account-settings CSS/JS
├── backend/                  # Login and account POST handlers
├── config/
│   ├── auth.php              # Session gate + role redirect
│   ├── cloudinary.php        # Cloudinary credentials bootstrap
│   ├── db.php                # mysqli + PDO; loads db_cron
│   ├── mail.php              # SMTP / app URL helpers
│   ├── registration.php      # Registration constants
│   ├── session.php           # Session lifetime headers
│   └── uploads.php           # Upload paths and helpers
├── includes/
│   ├── notifications_service.php   # Idempotent notification inserts
│   ├── registration_service.php    # Multi-step registration + email
│   ├── cloudinary_upload.php       # Staging + Cloudinary/local upload
│   ├── fetch_*.php                 # Shared JSON/data endpoints
│   ├── mark_notification_*.php   # Read-state updates
│   ├── update_report_status.php    # LGU verification + notification
│   ├── account_settings_*.php    # Account settings partials
│   └── db_cron.php                 # Expired verification cleanup
├── lgu/                      # LGU portal (main.php, sections/, assets/)
├── main/                     # Public login, registration, verify, shared CSS
├── phpmailer/                # PHPMailer library
├── rescuer/                  # Rescuer portal
├── resident/
│   ├── main.php              # Resident shell router
│   ├── sections/             # Page fragments (dashboard, report-flood, …)
│   ├── assets/js/resident.js # Maps, notifications, hotlines, safety centers
│   ├── api/                  # Resident-specific JSON endpoints
│   └── backend/              # Resident account settings handler
├── uploads/                  # Local media fallbacks (profiles, reports, reg_temp)
├── composer.json             # PHP dependencies
├── psalm.xml                 # Static analysis config
└── README.md
```

---

## Requirements

- [XAMPP](https://www.apachefriends.org/) (or equivalent): **PHP 7.4+** (8.x recommended) and **MySQL / MariaDB**
- MySQL database (default name: `flood_information`)
- [Composer](https://getcomposer.org/) for PHP dependencies (`composer install`)
- Web server document root pointing at this project folder
- Optional: Cloudinary account, SMTP credentials (Brevo or other)

---

## Installation

1. Clone or copy the project into your web root, e.g. `C:\xampp\htdocs\bocaue-flood-system`.
2. Run `composer install` in the project root.
3. Create the MySQL database and import your schema/seed data (not included in this repo).
4. Copy credentials into `config/db.php` (use placeholders locally; never commit production secrets):

```php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'flood_information';
```

5. Configure optional services (see [Configuration](#configuration)).
6. Ensure `uploads/profiles/`, `uploads/reports/`, and `uploads/reg_temp/` are writable by the web server.
7. Start Apache and MySQL; open:
   - Login: `http://localhost/bocaue-flood-system/main/login.php`
   - Resident portal: `http://localhost/bocaue-flood-system/resident/main.php?page=dashboard`

Adjust paths if your folder or virtual host differs. Resident JS is cache-busted via `filemtime` in `resident/main.php` — hard-refresh after JS changes if needed.

---

## Configuration

| File | Purpose |
|------|---------|
| `config/db.php` | MySQL mysqli (`$conn`) and PDO (`$pdo`) |
| `config/auth.php` | Login required; optional `$requiredRole` enforcement |
| `config/session.php` | Session cache headers for protected areas |
| `config/mail.php` | SMTP and public app URL for verification emails |
| `config/cloudinary.php` | Image CDN credentials |
| `config/registration.php` | Registration role ID and debug flag |
| `config/uploads.php` | Upload directory helpers |

### Recommended environment variables (production)

| Variable | Purpose |
|----------|---------|
| `CLOUDINARY_URL` | `cloudinary://key:secret@cloud_name` |
| `BFIS_MAIL_HOST`, `BFIS_MAIL_PORT`, `BFIS_MAIL_USERNAME`, `BFIS_MAIL_PASSWORD` | SMTP |
| `BFIS_MAIL_FROM`, `BFIS_MAIL_FROM_NAME` | Sender identity |
| `BFIS_DEBUG` | `1` / `true` for registration debug logging |

Do not commit live database passwords, SMTP keys, or Cloudinary secrets to version control.

---

## Entry points

| Path | Description |
|------|-------------|
| `main/login.php` | Login form |
| `backend/login.php` | POST authentication handler |
| `main/register_step1.php` | Registration wizard step 1 |
| `main/register_step2.php` | Registration wizard step 2 |
| `main/register_step3.php` | Registration wizard step 3 |
| `main/verify.php` | Email verification token handler |
| `main/forgot_password.php` | Password reset request |
| `lgu/index.php` | Redirect to LGU portal |
| `rescuer/index.php` | Redirect to Rescuer portal |
| `resident/index.php` | Redirect to Resident portal |
| `lgu/main.php` | LGU shell router |
| `rescuer/main.php` | Rescuer shell router |
| `resident/main.php` | Resident shell router |

---

## Security notes

- Use **HTTPS** in production so session cookies can use the `Secure` flag consistently.
- Restrict filesystem permissions on `config/db.php` and any file containing secrets.
- Store production credentials in **environment variables**, not in tracked config files.
- All notification and report SQL should use **prepared statements** (enforced in current code paths).
- Flood report and registration map pins are rejected outside Bocaue coverage bounds.
- Keep upload directories non-executable; validate MIME types and size limits on uploads.
- LGU-only endpoints must set `$requiredRole = 'LGU'` (or equivalent) before processing.

---

## Development utilities

```bash
# Install dependencies
composer install

# Static analysis (config in psalm.xml)
vendor/bin/psalm

# Code style (PSR-12 oriented)
vendor/bin/phpcs
```

To seed a bcrypt password hash locally, use a one-off PHP script or `password_hash('your-password', PASSWORD_BCRYPT)` — do not leave hash generators publicly accessible on production.

---

## License

No license file is included in this repository. Add a `LICENSE` file and update this section if you intend to distribute or open-source the project.
