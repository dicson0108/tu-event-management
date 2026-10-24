# Taylor's University Event Management Web App

A PHP campus event application for Taylor's University students and staff, with event discovery, registration, waitlists, feedback and an administrator workspace. The current project combines database-backed workflows with October–November 2026 demo content.

---

## Features

### Student & Staff Experience
- **Domain-Restricted Authentication**: Only `@taylors.edu.my` and `@sd.taylors.edu.my` email addresses are accepted. Session-based authentication with bcrypt password hashing and CSRF protection.
- **Event Discovery**: Filter by keyword, category, upcoming date range (today, this week, October, November), and format (Physical, Online, Hybrid). Sort by date or popularity.
- **Registration & Automated Waitlist**: Join or leave using a confirmation prompt and a fetch request, followed by a page reload. Joining checks current capacity; leaving a confirmed seat or removing a confirmed attendee promotes the first waitlisted user. Concurrent capacity enforcement is not yet guaranteed.
- **Event Timing & Organiser Notes**: Date, time range, venue and organiser details come from the database. The organiser note is static; there is no agenda or announcement editor.
- **My Events**: Lists upcoming confirmed seats, active waitlist positions and past registrations. This is a list view, not a calendar integration.
- **Feedback & 1–5 Star Ratings**: Event Detail displays community reviews and their average rating. The review form appears for confirmed users after the event date; feedback is saved through a normal form POST. Owners can edit/delete their comments. Server-side eligibility enforcement remains a PRD gap.
- **Profile Management**: Update personal info, preferred name, faculty, phone number, and change account password.

### Administrator Workspace
- **Dashboard**: Upcoming-event and recent-feedback lists use database records. KPI cards and the six-week registration chart contain fixed demo values.
- **Event Lifecycle**: Create, edit, publish, draft, cancel (with attendee notice reason), or delete events.
- **Posters**: Custom JPG/PNG uploads up to 5 MB are offered by the form; the backend also accepts JPEG and WebP extensions. Uploaded files take priority over bundled event images, followed by generated fallback markup. Six theme names are selectable, but distinct theme CSS is not implemented.
- **Attendee Management & CSV Export**: Inspect registrations, manually promote waitlisted attendees, remove attendees, and download full CSV rosters.
- **User Governance**: View registered accounts, toggle between `user` and `admin` roles, suspend/reactivate accounts, or delete users.
- **Categories & Content Moderation**: Add categories, delete categories offered as unused by the UI, and moderate reviews. Category editing is not implemented.

### Current limitations

- Discovery and My Events include fixed October 2026 date boundaries; Event Detail uses the server's current date. Demo dates and counts are not consistently live.
- Home, Explore events, Event Detail, Privacy and Terms are public. Account actions require login, and admin pages require the admin role. Event Detail currently looks up any event ID without a published-status filter.
- Search, filters, pagination and feedback use page navigation or form POSTs. Registration uses fetch and then reloads the page. There is no email notification service, editable announcement feed or calendar integration.
- Capacity checks and writes are not protected by a transaction/row lock; manual promotion does not check capacity. Post-event attendance eligibility is checked for form display but not enforced in the feedback POST handler.
- Upload checks currently use filename extension and size, not image-content validation. These and the remaining requirement gaps are recorded in section 13 of the local PRD.

---

## Tech Stack

- **Backend**: PHP 8.0+ (PDO, prepared statements, session-based auth)
- **Database**: MariaDB (XAMPP 10.4 series) / MySQL-compatible SQL; the schema uses InnoDB and `utf8mb4`.
- **Frontend**: Semantic HTML5, CSS3 (Taylor's brand red design system), Vanilla JavaScript (ES6+ fetch/AJAX)
- **Environment**: XAMPP or MAMP, serving the project from `htdocs`

---

## Getting Started

These steps assume **XAMPP or MAMP is already installed**, with PHP 8.0+ and `pdo_mysql`. No Composer, npm, terminal commands or manual database import are needed.

### 1. Put the project in `htdocs`

Copy or extract the project into your web server's document root and name the folder **`TUEventManagement`**. It should contain `index.php`, `config/`, `database/` and `assets/` directly, without an extra nested folder.

| Installation | Project folder |
| --- | --- |
| XAMPP on Windows | `C:\xampp\htdocs\TUEventManagement` |
| XAMPP on macOS (native installer) | `/Applications/XAMPP/xamppfiles/htdocs/TUEventManagement` |
| MAMP on macOS | `/Applications/MAMP/htdocs/TUEventManagement` |
| MAMP on Windows | `C:\MAMP\htdocs\TUEventManagement` |

If you changed the document root, use its configured location. For XAMPP-VM, use its mounted `htdocs` directory (inside the VM at `/opt/lampp/htdocs`).

### 2. Start the servers

In XAMPP's control panel / **Manage Servers**, start **Apache** and **MySQL**. In MAMP, start the web and MySQL servers. Check the ports in your server settings.

The **web port** goes in the browser URL. The **MySQL port** goes in `config/db.php`. They are separate values.

### 3. Edit `config/db.php`

Open [config/db.php](config/db.php) and match these settings to your local database server:

```php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'tu_event_management');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', 3306);
```

| Setting | What to enter |
| --- | --- |
| `DB_HOST` | Keep `127.0.0.1` when PHP and MySQL run on the same machine or inside the same XAMPP VM. |
| `DB_NAME` | Keep `tu_event_management`, or choose a new name. The app creates the database automatically if missing. |
| `DB_USER` | Your MySQL username, commonly `root` for local XAMPP/MAMP. |
| `DB_PASS` | Your MySQL password: commonly `''` for XAMPP or `'root'` for MAMP. Use your configured password if changed. |
| `DB_PORT` | Your **MySQL** port: commonly `3306` for XAMPP or `8889` for MAMP. |

For a typical MAMP installation, change these two lines and keep the other settings above:

```php
define('DB_PASS', 'root');
define('DB_PORT', 8889);
```

Check your installation if you changed credentials or ports. MAMP documents its default ports in the [macOS settings](https://documentation.mamp.info/en/MAMP-Mac/Reference/Settings/Ports/) and [Windows settings](https://documentation.mamp.info/en/MAMP-Windows/Preferences/Ports/).

### 4. Open the website

Use your web server's port and the project folder name:

| Web port | Application URL |
| --- | --- |
| `80` (typical XAMPP default) | [http://localhost/TUEventManagement/](http://localhost/TUEventManagement/) |
| `8080` (example custom XAMPP port or VM forwarding port) | [http://localhost:8080/TUEventManagement/](http://localhost:8080/TUEventManagement/) |
| `8888` (typical MAMP default) | [http://localhost:8888/TUEventManagement/](http://localhost:8888/TUEventManagement/) |

The first database-backed page load creates the configured database, builds its tables and loads demo users, events, registrations and reviews. **You do not need to create a database in phpMyAdmin or import SQL files.** Sign in using a [test account](#test-accounts) below.

Your MySQL account must be allowed to create the database and tables during setup. A fresh installation receives an `app_setup` table that records completion. Later visits preserve your data without reimporting demo records. Existing installations with all five application tables are also preserved.

If setup encounters an unrelated or incomplete database, it stops rather than replacing existing tables; choose a new `DB_NAME` for a separate installation. Keep the SQL files in `database/` because first setup reads them. The schema creates missing tables without dropping existing ones. Setup is not a reset or migration tool; failed initial seed changes roll back and setup can retry on the next request.

With the folder named `TUEventManagement`, keep the existing value in [config/app.php](config/app.php):

```php
const APP_BASE_PATH = '/TUEventManagement';
```

No `app.php` change is needed for the layouts above. If you choose another folder name, update this path to match it. Keep the hostname and web port in the browser URL, not in `APP_BASE_PATH`.

### Troubleshooting

| Problem | What to check |
| --- | --- |
| Page cannot be reached | Start the web server and use its actual port. With XAMPP-VM, use the HTTP forwarding rule's Mac-side port. |
| Page not found | Confirm the project is directly inside the configured `htdocs`, with `index.php` in `TUEventManagement`. |
| Database connection or setup failed | Start MySQL and check all five settings in `config/db.php`. Use the MySQL port, not Apache's port. |
| Access denied / cannot create database or tables | Check credentials and setup permissions. MAMP commonly uses `root` / `root`; XAMPP commonly uses `root` with an empty password. |
| Incomplete or unrelated schema | Change `DB_NAME` to a new name. The existing database is left intact. |
| Missing styles or broken links | Match `APP_BASE_PATH` to the project folder, preserving its leading `/`. |
| `could not find driver` | Enable `pdo_mysql` for the PHP version selected by XAMPP/MAMP and restart the web server. |

See the [XAMPP macOS FAQ](https://www.apachefriends.org/faq_osx.html) for native and VM configuration details. Setup has been tested using an isolated MariaDB server on Windows; the macOS/MAMP instructions have not been executed on those systems as part of this change.

---

## Test Accounts

These accounts are created automatically on a fresh installation. All use the password: `Password123!`

| Role | Name | Email | Permissions |
|---|---|---|---|
| **Administrator** | Nadia Rahman | `nadia.rahman@taylors.edu.my` | Admin Workspace, Create/Edit Events, Attendees CSV, User Governance, Moderation |
| **Student** | Aisha Tan | `aisha.tan@sd.taylors.edu.my` | Browse, Join/Leave, Waitlist, Past Event Feedback, Profile |
| **Student** | Daniel Lee | `daniel.lee@sd.taylors.edu.my` | Browse, Join/Leave, Waitlist |
| **Student** | Sara Ahmad | `sara.ahmad@sd.taylors.edu.my` | Browse, Join/Leave, Waitlist |

---

## Project Structure

```
.
├── .gitignore                 # Shared exclusions for uploads and temporary files
├── .htaccess                  # Apache routing for 403, 404 and 500 errors
├── 403.php                    # Access-denied page
├── 404.php                    # Page-not-found page
├── 500.php                    # Server-error page
├── README.md                  # Setup, project structure and development checks
├── admin/                     # Administrator workspace pages
│   ├── attendees.php          # Attendee roster, CSV export, manual promotions
│   ├── categories.php         # Category management
│   ├── comments.php           # Feedback moderation
│   ├── event-edit.php         # Event creation and editing
│   ├── events.php             # Event administration and action modals
│   ├── footer.php             # Admin footer template
│   ├── index.php              # Admin dashboard and metrics
│   ├── sidebar.php            # Admin navigation shell
│   └── users.php              # User role and status governance
├── api/                       # Registration JSON endpoint and comment form handler
│   ├── comments.php           # Comment deletion via POST and redirect
│   └── register-event.php     # Join/leave event, waitlist queuing & auto-promotion
├── assets/
│   ├── css/
│   │   ├── admin.css          # Administrator workspace
│   │   ├── auth.css           # Login and registration
│   │   ├── errors.css         # Shared standalone error-page layout
│   │   ├── event-detail.css   # Event detail and registration card
│   │   ├── events.css         # Event discovery
│   │   ├── home.css           # Landing page
│   │   ├── legal.css          # Privacy and terms
│   │   ├── my-events.css      # Registered events and waitlists
│   │   ├── profile.css        # Account profile
│   │   └── style.css          # Shared design system
│   ├── images/
│   │   ├── avatars/           # Bundled user avatars
│   │   ├── events/            # Bundled event posters
│   │   └── logo/              # Site logo
│   ├── js/
│   │   └── main.js            # Client-side interactions and AJAX
│   └── uploads/               # Uploaded posters; only .gitkeep is tracked
├── config/
│   ├── app.php                # URL prefix, app_url() and exception-handler setup
│   ├── db.php                 # Local database settings and PDO connection
│   └── setup.php              # First-run database and demo-data initialization
├── database/
│   ├── schema.sql             # Non-destructive table definitions
│   └── seed.sql               # Pre-populated events, users, reviews, waitlists
├── includes/
│   ├── auth.php               # Authentication, session, role guards
│   ├── csrf.php               # CSRF token generation and validation
│   ├── error.php              # Shared error renderer and exception handler
│   ├── footer.php             # Global public footer
│   ├── header.php             # Global navbar and user menu
│   └── helpers.php            # Formatting, event locks, waitlist promotion and poster art
├── tests/
│   ├── database-setup.php      # Disposable-database setup integration checks
│   ├── error-pages.py         # Isolated Apache error-page HTTP checks
│   └── workflows.py           # HTTP authentication, feedback and registration checks
├── event-detail.php           # Event detail view and sticky registration card
├── events.php                 # Explore and search events
├── index.php                  # Landing home page
├── login.php                  # User and admin login
├── logout.php                 # CSRF-protected POST session termination
├── my-events.php              # User registrations and waitlists
├── past-event.php             # Legacy 301 redirect to Event Detail reviews
├── privacy.php                # Public privacy page
├── profile.php                # Personal information and password change
├── register.php               # Domain-validated account creation
└── terms.php                  # Public terms page
```

---

## Error Pages

`403.php`, `404.php` and `500.php` share a standalone layout that works without a database connection. Missing events and drafts hidden from the current user use the 404 page; restricted admin access uses 403. Unhandled PHP exceptions and database failures show a generic 500 page and log technical details on the server. API exceptions return JSON.

Apache uses the included `.htaccess` for missing URLs and server-generated errors. Allow `FileInfo` overrides for this project (the standard XAMPP configuration allows them). If you change `APP_BASE_PATH` or the installation folder, also update the three `ErrorDocument` paths in `.htaccess`. Broken Apache configuration or a stopped PHP service may prevent custom error pages from loading.

Run `python tests/error-pages.py --xampp C:/xampp` for isolated Apache checks. The test starts a temporary site on an available local port and requires no database server.

## Database Setup Tests

HTTP workflow regressions are available with `python tests/workflows.py --db-port 33079 --php C:/xampp/php/php.exe`. Python 3 and a disposable local database server are required for this development check; neither is required to run the website. Use a dedicated non-default database port with a passwordless local root account. The script copies the application to a temporary directory, adjusts that copy's database port, starts two temporary PHP HTTP servers and verifies authentication, event access, feedback, capacity, waitlists, rollback and logout. It resets fixture records in `tu_event_management` on the selected disposable server. Never point it at a server containing data you need to retain.

For contributors: `php tests/database-setup.php <port>` runs integration checks against a **disposable local MySQL/MariaDB server** with a passwordless root account. Use a dedicated non-default port, such as `33079`. The suite creates and removes randomly named `tu_setup_test_*` databases, independently of the application configuration.

Checks cover first creation, custom names, repeated visits, legacy data preservation, incomplete schemas, seed rollback/retry, interrupted setup markers and concurrent first requests.

## License
MIT License. Developed for Taylor's University event management.
