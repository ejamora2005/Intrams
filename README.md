# INTRAMURAL MS

A Laravel application for managing a school intramurals program. Its redesigned flow is edition-centered: configure edition sports, teams and team members, athlete sport participation, then schedules, live competition, results, tallies, and system activity history.

The dashboard reflects that flow with active-edition, configured-sport, and scheduled-competition metrics.

Sports are available through a dedicated sidebar module.

The Sports landing page presents each sport as a card with its status, edition configuration count, and direct management actions.

Use the edition selector at the top of Sports to switch the sport catalogue, participant management, and brackets to another intramurals edition. Default sports are synchronized automatically when an edition is created and whenever default sports are seeded again, without overwriting an edition's existing mechanics.

Sport editing updates the catalogue details and current edition mechanics without requiring a manual sport code.

Each sport includes a participant manager that groups active registered athletes by team, including a safe empty state when no athletes are registered.

For automated tournament sports, a dedicated bracket page generates and persists current seeding or round-robin pairings from registrations. Brackets treat a team, a two-athlete dual pair, or an individual athlete as one competitor.

Each Sports card opens its dedicated dynamic bracket page. Manage Participants is kept separate for participant registration and roster review.

For single- and double-elimination configurations, the bracket persists generated matches. Administrators select a competing competitor and declare a win or loss; the winner advances automatically and odd-sized fields receive automatic byes. Double elimination generates the complete winners bracket, losers bracket, grand final, and conditional reset final for any field size of at least two competitors.

Round-robin configurations use the free, MIT-licensed `heroyt/tournament-generator` v0.5 API locally through Composer. It requires no account, API key, subscription, or runtime network access. Every competitor plays every other competitor once; odd fields receive one rest slot per round, real games are persisted in the existing bracket tables, and recording a result affects only that game. The existing single- and double-elimination progression code remains unchanged.

The bracket header includes a guarded **Reset bracket** action for single elimination, double elimination, and round robin. It requires confirmation, records an audit entry, removes only that edition sport's generated tournament results, and then rebuilds the matches from the existing registrations. It never changes students, teams, or athlete registrations.

The bracket page is a fixed, two-axis scrollable tournament canvas for large fields. It labels the game/elimination style and tournament name, renders connected game cards through the finals and winner container, and shows a separate **Loser Bracket if needed** section only for double-elimination sports. Right-angle connector lines follow each winner and loser path; hover a game or competitor to highlight its immediate connected path and dim unrelated games. Clicking a team awaiting a result keeps it visibly marked **Selected** and preserves its path focus while the administrator chooses its result.

Basketball sport cards include an editable **Score sheet** with team/course autofill, displayed on a white **A4 portrait paper (210 × 297 mm)** against a gray background. The sheet fits proportionally within 4.25 mm paper margins. Roster columns remain the unlabeled 1–12 counter, Players, No., Course, and four foul fields. **Download** opens a drawer for Word (.docx), Excel (.xlsx), and PDF; all three directly download the same high-resolution image of the full A4 paper. The downloaded sheet is image-based, not editable cells or tables, so its lines and layout remain fixed. JavaScript and PHP ext-zip are required; exports are audited.

Dual-sport registration uses two independently searchable roster columns in the lower student-selection section while retaining the existing course/team filters above it. The administrator selects one distinct student from each column and saves them as one pair; a team may register multiple pairs. Both athlete entries share one pair key and remain tied as one bracket competitor. Individual athletes use the same progression engine without needing a team record.

Administrators register participants from each sport's Manage Participants page. The sport's configured participant type is displayed automatically, and course/team filters support selecting multiple eligible students at once.

For team and dual sports, Manage Participants uses horizontally scrollable team filters and one fixed-height roster panel. Selecting a team changes the displayed roster without changing the page layout; the student list scrolls vertically, supports live name/student-number search and visible-student Select All. Administrators can bulk-remove registrations; removing a dual athlete removes that complete pair. Adding or removing participants rebuilds an unfinished bracket, while all participant changes are blocked once a bracket result exists.

Bulk registration is covered by end-to-end tests for team roster assignment and team-filtered student selection. Team and dual sports only show an eligible selected-team roster, and students already registered to the sport are excluded.

Within an edition, administrators can configure default or custom sports with a participant type, game mechanic, description, and rules. Sport codes remain automatic and custom names are normalized before saving.

Edition workspaces also link directly to their filtered team management, retaining the existing reusable course filter and multi-athlete roster assignment.

Students can be assigned to configured edition sports from their profile. Team and dual sports require a matching edition team and roster membership; dual assignments save both selected athletes as one pair.

Course teams and students stay synchronized: creating a course team adds its existing active students, while creating or updating an active student automatically adds that student to matching active course teams.

Selecting or changing a course on an existing active team also adds the course's current active students to that team roster.

Team Management supports course and name filtering of roster members, then bulk removal of selected members with audit records.

After a successful save or assignment, the administrator is returned to that module's main list page.

This behavior is enforced centrally for explicit **Save** submissions only. Other successful actions, such as participant bulk removal, assignment, withdrawal, and result declaration, return to the current page and retain its filters/edition context. Validation errors still return to their original form.

On desktop, the sidebar stays fixed while only the main display panel scrolls, so navigation remains visible in long participant lists and tournament canvases.

## Test data

`php artisan db:seed` provides the **SLSUBC INTRAMURALS 2026** edition, default factions/teams, sports, cultural events, schedules, point systems, and the supplied student roster. Operation accounts are seeded when `INTRAMURALS_DEFAULT_ACCOUNT_PASSWORD` is set.

## Current implementation state

The project has its Laravel foundation, initial MySQL database schema, universal staff login, admin dashboard, edition, student, team/roster, sport/event management, and coordinator/device management in place. Remaining intramurals modules are still in development.

- Framework: Laravel 10.50.3 / PHP 8.1+
- Authentication stack: Laravel Jetstream, Fortify, Livewire, Sanctum
- Frontend tooling: Blade, Tailwind CSS, Vite
- Database: MySQL 8 (`intramsproj`)
- Tests: Pest

See [TODO.md](TODO.md) for the complete functional requirements, schema design, business rules, and delivery checklist.

## Prerequisites

- PHP 8.1 or newer
- Composer
- Node.js and npm
- MySQL 8 or compatible MariaDB

## Local setup

1. Install PHP and JavaScript dependencies:

   ```powershell
   composer install
   npm install
   ```

2. Copy and configure the environment file if it does not already exist:

   ```powershell
   Copy-Item .env.example .env
   php artisan key:generate
   ```

3. Create the configured database, then apply migrations:

   ```powershell
   mysql -u root -e "CREATE DATABASE IF NOT EXISTS intramsproj CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   php artisan migrate
   ```

   The current `.env` is configured for `DB_CONNECTION=mysql` and database `intramsproj`. Do not commit credentials or `.env` files.

4. Build frontend assets and start the application:

   ```powershell
   npm run dev
   php artisan serve
   ```

5. Run tests:

   ```powershell
   php artisan test
   ```

## Database schema

Laravel migrations create the following system tables in addition to Jetstream/Sanctum tables:

| Area | Tables |
|---|---|
| Identity and access | `users` (with `role` and `status`), `sessions`, `password_reset_tokens`, `personal_access_tokens` |
| Master data | `students`, `sports`, `intramural_editions`, `teams`, `events` |
| Registration and staffing | `participation_rules`, `event_registrations`, `team_members`, `coordinator_assignments`, `coordinator_requests`, `coordinator_devices` |
| Competition | `event_scoring_rules`, `scoring_point_rules`, `fixtures`, `fixture_competitors`, `result_submissions`, `result_entries`, `team_tallies` |
| Compliance and history | `team_flags`, `audit_logs` |

The clean-reset migrations enforce edition-specific sports, one athlete entry per student/sport, schedule participant uniqueness, team codes within an edition, and lookup indexes. Application services will enforce participant eligibility, pair composition, schedule windows, and coordinator authorization.

## Roles

| Role | Intended capability |
|---|---|
| `admin` | Manages all system data, reviews requests/results/flags, and accesses reports and logs. |
| `coordinator` | Works only with events that have an active coordinator assignment. |

Role and status columns are enforced by route middleware. Active administrators use `/admin/*`; active coordinators use `/coordinator/*` and cannot open administration routes.

## Universal staff login

The public `/login` screen is also the read-only daily competition board. It shows the active edition and today's non-cancelled schedules with sport, Morning/Afternoon period, competitors, and assigned facilitator. The table is rendered entirely on the server and has no polling, AJAX refresh, or Livewire updates; changes appear only after the browser page is manually refreshed. On narrow screens, the same schedule becomes stacked cards without horizontal scrolling. Pressing **Login** opens the email/password form in an accessible, viewport-bounded modal over a blurred backdrop, and validation errors reopen that modal automatically.

When one sport has multiple games on the same day, the board groups those games under one sport cell while retaining a separate chronological row for every game's time and competitors. A shared facilitator is also shown once for the group; differing facilitator assignments remain visible on their corresponding game rows.

Bracket scheduling is handled directly on every ready match card. **Schedule game** is available without selecting a winner; the administrator chooses only a date and Morning/Afternoon. The match supplies its competitors automatically, including repeated round-robin appearances, and saving again updates the same schedule instead of creating a duplicate.

On the public daily board, bracket-scheduled matchups show their bracket game number directly above the competing names (for example, **Game 1** above **Team A VS Team B**).

Feature coverage verifies both initial bracket scheduling and rescheduling without duplicate competition records.

The guest browser title uses `INTRAMURAL MS`, keeping the public board and login modal aligned with the application branding.

The application uses one shared `/login` page for both administrators and coordinators. It uses the system-wide slate-blue interface and accepts only an email address and password—users do not choose a role on the form.

The visual system is centrally defined in `tailwind.config.js` and `resources/css/app.css` from the Color Hunt palette `#355872`, `#7AAACE`, `#9CD5FF`, and `#F7F8F0`. `#355872` is the dark base for navigation and primary actions; the two lighter blues provide highlights, focus states, and supporting surfaces; `#F7F8F0` is the application background. Existing blue, indigo, slate, and gray utilities inherit this mapping across public, authentication, administrator, coordinator, and Jetstream screens. Red, amber, and green remain reserved for destructive, warning, and success feedback. Printable score-sheet paper remains white for accurate output.

- Only accounts with `status = active` and role `admin` or `coordinator` can sign in.
- Suspended, inactive, and unsupported-role accounts are rejected with the standard generic login error.
- Fortify limits login attempts to five per minute per email/IP combination.
- Public `/register` access is disabled. Administrator account-provisioning screens will be added in a later module.
- Password reset remains available through Jetstream/Fortify for the current setup.

### Coordinator trusted device policy

The first successful coordinator login registers one server-generated trusted-device token. Only its SHA-256 hash is stored in `coordinator_devices`; the raw token is an HTTP-only cookie and is never written to the database or audit log. A different device is denied until an administrator selects **Reset trusted device** from that coordinator’s management page. The next successful coordinator login can then register a new device.

Coordinator assignment requests support add, remove, and reassign actions. A reassignment stores both the source event and requested target event; when approved, the system revokes the source assignment and activates the target assignment in one database transaction.

For local development, create staff accounts with a securely hashed password and an explicit role/status; do not store plaintext passwords.

### Local test administrator

Running `php artisan db:seed` creates (or resets) the default operations accounts when `INTRAMURALS_DEFAULT_ACCOUNT_PASSWORD` is set. For local testing, set it to `password`.

| Email | Password | Role |
|---|---|---|
| `admin@example.com` | value of `INTRAMURALS_DEFAULT_ACCOUNT_PASSWORD` | `admin` |
| `gam@example.com` | value of `INTRAMURALS_DEFAULT_ACCOUNT_PASSWORD` | `gam` |
| `tabulator@example.com` | value of `INTRAMURALS_DEFAULT_ACCOUNT_PASSWORD` | `tabulator` |

These accounts are intentionally for local testing and initial deployment only. Change or remove them before handing the application to production users.

The feature test suite uses `RefreshDatabase`; if you run tests against the local MySQL database, run `php artisan db:seed` again afterward to restore this development account.

## Admin landing page and navigation

Active administrators are redirected from `/dashboard` to `/admin/dashboard`. The login screen and admin sidebar share one reusable brand component, displaying the Intramurals 2026 emblem and `INTRAMURAL MS` name consistently. The admin shell keeps its fixed sidebar and independently scrolling content panel on desktop. On phones and tablets, the sidebar becomes a vertically scrollable off-canvas menu with a backdrop, close control, Escape-key support, and body-scroll locking; the dashboard cards, buttons, spacing, and headings reflow for narrow screens.

The slate-blue palette sidebar contains:

- Dashboard
- Events
- Students
- Teams
- Coordinators
- Sports
- System Logs

The dashboard shows current database counts for active students, active coordinators, scheduled/live events, and pending coordinator requests. The Editions, Students, Teams, and Coordinators sidebar modules are implemented; Sports & Events and System Logs remain module shells.

## Intramurals edition management

The **Editions** sidebar module manages each school-year intramurals period. Administrators provide the edition name, school year, and start/end dates; the end date cannot be before the start date. An edition can be `draft`, `active`, `closed`, or `archived`, and only one edition can be active at one time.

Archiving changes the edition lifecycle status instead of deleting it, preserving teams and all future historical competition records. Edition create, update, and archive actions are recorded in the system activity log.

## Team management

The **Teams** sidebar module manages teams within a specific intramurals edition. An edition must already exist in the database before a team can be created. Team codes are unique per edition, and teams can be active, inactive, or disqualified.

From a team's Manage page, an administrator can assign active students to its roster or remove them. A student can belong to only one team in the same edition; this roster membership is separate from later event registration. Teams can be archived and restored without losing their historical roster, and all team and roster changes are written to the activity log.

When assigning a roster, administrators can filter available athletes by course, select multiple athletes, or select every currently filtered athlete at once.

## Sports and events

The **Sports & Events** module now manages active/inactive sports and their edition-specific events. Event codes are generated internally, so administrators only provide the event name, competition type, venue, schedule, result mode, and capacity. For team events, capacity is the maximum number of athletes each team may register; for individual events, it is the maximum number of athletes overall. The event lifecycle only allows `draft → scheduled → live → completed`, with cancellation available before completion. Every change is audited.

`php artisan db:seed` also creates the default sports, athletics events, cultural events, default schedules, and the supplied student roster. Food Committee-only names are intentionally excluded from that roster seed.

When an administrator creates a new intramurals edition, these default sports are automatically linked to that edition. Additional events can then be created for that edition under its linked sports.

The **Sports** page lists the default and administrator-created sports in one standard management panel. Each sport has a **Manage events** action, so events are created and maintained within their related sport. Editions remain the separate program-period configuration module.

When adding a custom sport, administrators provide the name, status, and optional description; its internal code is generated automatically. Event creation is launched from that sport's event panel, so the sport is identified automatically. Venue and schedule are configured later for fixtures rather than during event creation.

Event names are generated automatically from the edition, sport, and competition type. Competition types are Team, Dual (paired participants from the same team), and Individual.

Only Individual events use a maximum participant count. Team events have no count field. Dual events use two athletes per pair and can include multiple pairs from the same team.

## Athletes and participation rules

The **Athletes** module first filters scheduled events by sport. Team and dual events require a team and show only its active roster, with name/student-number search. A dual entry must select exactly two athletes and is saved atomically. The page receives its selector data from the controller, avoiding inline view transformations. One active participation rule per edition can limit each athlete's distinct sports and total events; these limits and duplicate checks run inside a database transaction.

## Competition operations

The **Competition** sidebar module provides administrator visibility of fixtures and unresolved flags. Fixtures can be created for scheduled events; flags can be moved into review, cleared, or upheld, with each decision recorded in the activity log.

## Student data standards

Student forms use controlled selections for school year (`YYYY-YYYY`), year level (1st–4th), section (A–C), and gender (Male, Female, LGBTQ+). Before a student record is stored, the system removes HTML/control characters, collapses extra whitespace, title-cases name parts, and normalizes student numbers and sections to uppercase.

Administrators can create reusable **Courses** from `/admin/courses` using only a course name; the system generates the internal code. Courses can be selected on each student record and used to filter the Students list.

All `/admin/*` routes require an authenticated, active account with the `admin` role. Coordinators are denied access to them. The role middleware is registered as `role`, and can be applied as `role:admin` to future admin routes.

## System Activity Log

The system will provide an administrator-only, append-only activity log. It is designed to record:

- Successful and failed logins, logouts, password/two-factor/device actions, and account suspension.
- Major admin and coordinator actions, including create, update, edit, archive/delete, restore, assignments, registrations, result actions, flags, and disqualifications.
- Denied access and major validation-sensitive actions.

Each entry retains the actor, actor role, action, affected record, safe before/after values, IP address, user agent, request ID, outcome, and timestamp. Passwords, raw device tokens, and other secrets are never logged. Administrators can use the **System Logs** sidebar screen to filter recorded activity by actor, role, action, outcome, and date. Authentication-event logging remains pending.

## Development conventions

Basketball score-sheet downloads target **A4 portrait (210 × 297 mm)**. Volleyball uses its supplied reference layout on **A4 landscape (297 × 210 mm)**. Each on-screen paper preview and exported page share the same orientation, scale, and margins; the full sheet is captured without reflowing individual cells.

The volleyball Sports card opens its landscape score-sheet preview and the same download drawer used by basketball. Its Home and Visitor selectors load active registered teams, show each roster with course information, fill the team names, and place the first six athletes into Player Name rows using `Last name. F.` formatting. All formats capture the same A4 paper element, including its whitespace and margins. Word places the image at page origin, Excel embeds it with an A4 one-page print area, and PDF embeds it on an A4 page. The surrounding gray preview background and paper shadow are excluded from exports.

Score-sheet JavaScript is loaded only on score-sheet pages, and Volleyball's roster automation is delivered as a separate cacheable module. Roster queries retrieve only active athlete entries with valid team and student relationships. The reference image declares its intrinsic dimensions and is prioritized to reserve the final layout immediately and avoid preview movement while it decodes.

Team editing loads a roster count and 50 roster rows per page, reuses the course list, and excludes already assigned students with a database subquery. Bulk selection applies to the visible roster page. Admin navigation includes Courses and Coordinators and highlights nested screens; the Blade-only admin layout no longer loads unused Livewire assets.

Build production assets with `npm run build`. On deployment, run `php artisan view:cache` to precompile Blade views; use `php artisan view:clear` when returning to template development. Performance changes are verified with isolated SQLite tests; no production database migration is needed.

- Use Laravel migrations for all database changes; do not alter a migration that has already been run in shared environments—create a new migration instead.
- Keep controllers thin. Put business rules in services, authorization in policies/middleware, and validation in form requests.
- Use database transactions for multi-record competition actions and record significant actions through the audit service.
- After every code, database, or document change, update `TODO.md` and update this README whenever the change affects setup, architecture, behavior, or usage.

## Current next step

Build Sports & Events management so coordinators can be assigned to fully configured competitions.
