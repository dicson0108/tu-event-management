# Taylor's University Event Management Web App

A modern, responsive web application for Taylor's University students and staff to discover, join, and review campus events, with an administrative portal for event lifecycle management, automated waitlist handling, and moderation.

---

## Features

### Student & Staff Experience
- **Domain-Restricted Authentication**: Only `@taylors.edu.my` and `@sd.taylors.edu.my` email addresses are accepted. Session-based authentication with bcrypt password hashing and CSRF protection.
- **Event Discovery**: Filter by keyword, category, upcoming date range (today, this week, October, November), and format (Physical, Online, Hybrid). Sort by date or popularity.
- **Fair Capacity & Automated Waitlist**: One-click registration with real-time seat limits. When an event is full, users can join the waitlist. When a confirmed attendee cancels or is removed, the first waitlisted user is automatically promoted (FIFO queue).
- **Event Schedule & Updates**: Detailed agendas ("The day at a glance") and organiser announcements.
- **My Events & Campus Calendar**: Track upcoming confirmed seats, active waitlist positions, and past attended events.
- **Post-Event Reviews & 1–5 Star Ratings**: Attended students can rate completed events, write feedback, and edit/delete their own comments.
- **Profile Management**: Update personal info, preferred name, faculty, phone number, and change account password.

### Administrator Workspace
- **Dashboard Metrics & Trends**: High-level KPI counters (total events, campus users, confirmed registrations, waitlisted users) and 6-week registration bar chart.
- **Event Lifecycle**: Create, edit, publish, draft, cancel (with attendee notice reason), or delete events.
- **Poster Customization**: Dynamic geometric art themes (Purple, Dark, Yellow, Blue, Green, Peach) or custom JPG/PNG image uploads up to 5 MB.
- **Attendee Management & CSV Export**: Inspect registrations, manually promote waitlisted attendees, remove attendees, and download full CSV rosters.
- **User Governance**: View registered accounts, toggle between `user` and `admin` roles, or suspend/reactivate accounts.
- **Categories & Content Moderation**: Manage event taxonomies and delete spam or inappropriate reviews.

---

## Tech Stack

- **Backend**: PHP 8.2+ (PDO, prepared statements, session-based auth)
- **Database**: MySQL 10.4+ / MariaDB
- **Frontend**: Semantic HTML5, CSS3 (Taylor's brand red design system), Vanilla JavaScript (ES6+ fetch/AJAX)
- **Environment**: Compatible with local PHP built-in server (`php -S`) or XAMPP/WAMP (`htdocs`)

---

## Getting Started

### 1. Prerequisites
- PHP 8.0 or higher with `pdo_mysql` extension enabled
- MySQL / MariaDB server running on port `3306`

### 2. Database Setup
Import the schema and seed data into your MySQL server:

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
├── api/                       # AJAX endpoints
│   ├── comments.php           # Comment deletion and management
│   └── register-event.php     # Join/leave event, waitlist queuing & auto-promotion
├── assets/
│   ├── css/
│   │   └── style.css          # Design system stylesheet
│   ├── js/
│   │   └── main.js            # Client-side interactions and AJAX
│   └── uploads/               # Uploaded event posters
├── config/
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
├── past-event.php             # Past event rating and community feedback
├── profile.php                # Personal information and password change
└── register.php               # Domain-validated account creation
```

---

## License
MIT License. Developed for Taylor's University event management.
