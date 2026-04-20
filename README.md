# Bocaue Flood Information System (BFIS)

Web application for the Municipality of Bocaue focused on flood monitoring, community communication, evacuation coordination, and role-based access for local government, responders, and residents.

## Features

- **Authentication** — Email and password login with bcrypt verification, session hardening (regenerate ID, cookie flags), and multi-step resident self-registration.
- **Account verification** — New registrations are created with `is_verified = 0`; an LGU administrator must verify accounts before users can sign in.
- **Role-based portals**
  - **LGU** — Dashboard, user management, announcements, evacuation centers and evacuees, hotlines, flood map, data monitoring, report verification, and community views.
  - **Rescuer** — Operational dashboard, evacuation center tools, flood monitoring map, hotlines, and community information.
  - **Resident** — Personal dashboard, flood map, flood reporting, emergency hotlines, and safety centers.
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
- **REST-style PHP endpoints** — JSON and form operations under `api/` for announcements, users, centers, evacuees, hotlines, and related actions.

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
   - `http://localhost/bocaue-flood-system/main/login.php`

Adjust the URL path if your folder name or virtual host differs.

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
```

## Security notes

- Keep `generate_hash.php` **out of production** or delete it after local use; it generates password hashes and should not be publicly accessible on a live server.
- Use **HTTPS** in production so session cookies can use the `Secure` flag consistently with `config/db.php` and related session setup.
- Restrict file permissions on `config/db.php` and any files that contain secrets.
- Keep SQL table/column names aligned with this project schema (`users.user_id`, `reports.report_id`, `report_status.status_id`) before applying custom migrations.

## Development utilities

- **`generate_hash.php`** — Local helper to produce a bcrypt hash for seeding or updating a user password in the database (edit the `$password` variable inside the file, run once in the browser, then remove or protect the file).

## License

No license file is included in this repository. Add a `LICENSE` file and update this section if you intend to distribute or open-source the project.
