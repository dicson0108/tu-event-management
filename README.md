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
- **Environment**: Compatible with local PHP built-in server (`php -S`) or XAMPP/WAMP (`htdocs`)

---

## Getting Started

### 1. Prerequisites
- PHP 8.0 or higher with `pdo_mysql` extension enabled
- MySQL / MariaDB server running on port `3306`

### 2. Database Setup
Import the schema and seed data into your MySQL server:

`schema.sql` drops and recreates the application's tables. Use it for initial setup or a deliberate database reset, after backing up any data you need.

```bash
# Using MySQL CLI
mysql -u root -p < database/schema.sql
mysql -u root -p < database/seed.sql
```

*(If running via XAMPP MySQL binary in PowerShell):*
```powershell
Get-Content database\schema.sql -Raw | & "C:\xampp\mysql\bin\mysql.exe" -u root
Get-Content database\seed.sql -Raw | & "C:\xampp\mysql\bin\mysql.exe" -u root
```

### 3. Database Configuration
Edit [config/db.php](config/db.php) if your local MySQL credentials differ:
```php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'tu_event_management');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', 3306);
```

### 4. Run the Application
For XAMPP with the project in `C:\xampp\htdocs\TUEventManagement`, open
`http://localhost/TUEventManagement/`. The shared URL prefix is configured as
`APP_BASE_PATH` in `config/app.php`. Set it to an empty string when serving the
project at a domain root, including the built-in PHP server below.

Start the built-in PHP development server in the project root:

```bash
php -S localhost:8000
```

Open your browser and navigate to:
```
http://localhost:8000
```

---

## Test Accounts

All pre-seeded test accounts use the password: `Password123!`

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
│   │   ├── style.css          # Shared design system
│   │   └── *.css              # Page styles for home, events, auth, profile, legal, admin
│   ├── images/                # Bundled posters, avatars and logo
│   ├── js/
│   │   └── main.js            # Client-side interactions and AJAX
│   └── uploads/               # Uploaded event posters
├── config/
│   ├── app.php                # Installation URL prefix and app_url()
│   └── db.php                 # PDO database connection helper
├── database/
│   ├── schema.sql             # Table definitions and constraints
│   └── seed.sql               # Pre-populated events, users, reviews, waitlists
├── includes/
│   ├── auth.php               # Authentication, session, role guards
│   ├── csrf.php               # CSRF token generation and validation
│   ├── footer.php             # Global public footer
│   ├── header.php             # Global navbar and user menu
│   └── helpers.php            # Date/seat formatting, FIFO waitlist promotion, poster art
├── event-detail.php           # Event detail view and sticky registration card
├── events.php                 # Explore and search events
├── index.php                  # Landing home page
├── login.php                  # User and admin login
├── logout.php                 # Session termination
├── my-events.php              # User registrations and waitlists
├── past-event.php             # Legacy 301 redirect to Event Detail reviews
├── privacy.php                # Public privacy page
├── profile.php                # Personal information and password change
├── register.php               # Domain-validated account creation
└── terms.php                  # Public terms page
```

---

## License
MIT License. Developed for Taylor's University event management.
