# Intramurals Management System — Master Implementation TODO

## Clean-reset direction — approved

- [x] Replace the legacy event-registration structure with an edition-centered configuration: `edition → configured sports → teams/members → athlete sport entries → schedule → live competition`.
- [x] Reset the database schema and seed data. Legacy events, registrations, fixtures, results, flags, and coordinator assignments are replaced by configured edition sports, athlete entries, schedules, schedule participants, competition results, and tallies.
- [ ] Keep automatic codes, capitalization/sanitization, course and roster filtering, authorization, audit logs, and server-side validation.
- [ ] Do not restore removed legacy inputs such as manual event code, event name, division, venue, or sport-level schedule date.
- [x] Simplify the admin navigation to Dashboard, Events/Editions, Students, Live Competition, and System Logs. Detailed functions will be reached within their parent module.
- [x] Update the dashboard and Live Competition landing page to use the reset edition-sport and schedule tables instead of legacy events and fixtures.
- [x] Add edition-scoped sport configuration with automatic custom-sport codes, name normalization, participant type, game mechanic, description, and rules; excluded legacy event inputs remain removed.
- [x] Make team management edition-scoped from the Events/Editions workspace while retaining roster assignment, course filtering, and audited team changes.
- [x] Add student sport assignment against configured edition sports, with server-side participant-type, team-edition, roster, duplicate, and dual-pair validation.
- [x] Automate course-team membership for both existing students at team creation and newly created or course-updated active students.
- [x] Synchronize existing active course students when a course is selected or changed on an existing team in Team Management.
- [x] Add course/name-filtered team-roster search with Select All and bulk member removal.
- [x] Standardize successful admin saves and assignments to return to the current module's first page.
- [x] Enforce the post-save return rule centrally for all current and future admin modules while preserving validation-error redirects.
- [x] Provide repeatable test seed data: admin credentials, the requested 2026 SLSU Bontoc-campus edition, four courses, and five randomized students per course.
- [x] Expose Sports as a dedicated sidebar module using the edition-configuration model.
- [x] Fix Sport Edit validation and retained mechanic selections after removing manual sport-code input.
- [x] Add per-sport participant management that reads athlete entries and groups active participants by team, with a validated Blade view for empty and populated lists.
- [x] Add a dynamic elimination preview to Sport participant management, with single/double-elimination seeding and registered-team cards showing roster metrics.
- [x] Move bracket visualization to a dedicated clickable Sport-card destination; keep Manage Participants focused on registration and roster administration.
- [x] Add persistent elimination matches, click-to-declare win/loss controls, automatic byes, and automatic winner advancement to the next matchup.
- [x] Correct single-elimination progression so a future match cannot auto-advance until both source matches are resolved.
- [x] Implement scalable single- and double-elimination brackets for any field of at least two teams, dual pairs, or individual athletes, including odd-field byes, winners/losers progression, grand finals, and conditional reset finals.
- [x] Keep the dynamic participant view Blade-safe by using explicitly nested conditional markup for dual-pair labels.
- [x] Add bulk participant registration inside each sport's Manage Participants module, with automatic participant-type status, course/team filters, filtered multi-select, and Select All.
- [x] Verify bulk participant registration through isolated end-to-end tests; team/dual registration requires a team before eligible roster students are shown, and already-registered students are excluded.
- [x] Present sports as individual module cards with status, edition configuration count, participant management, and edit actions.
- [x] Automatically synchronize default sports to every new or re-seeded edition and allow Sports-module edition switching without mixing participants or brackets across editions.
- [x] Group team and dual participants in a fixed Manage Participants workspace: horizontally scrollable team filters switch a static, vertically scrollable roster panel with live name search and audited bulk removal that protects completed brackets.
- [x] Redesign the elimination-bracket page as a scalable two-axis scrolling tournament canvas with game-style/tournament headers, connected match modules, a final winner container, bye states, and a dynamic loser-bracket section for double elimination.
- [x] Add interactive bracket-path guidance: SVG right-angle winner/loser connectors resize with the canvas, and game or competitor hover highlights the directly related cards and paths.
- [x] Keep a clicked, result-eligible team visibly selected in the bracket, with its connected progression path highlighted until another team is selected.
- [x] Add a universal persisted bracket-competitor layer so teams, dual pairs, and individual athletes use the same validated bracket progression and completed brackets cannot be changed through participant registration.
- [x] Add an audited, confirmation-protected Reset bracket action that rebuilds only the current edition sport's bracket from existing registrations without changing students, teams, or athlete entries.
- [x] Preserve the administrator's current page for non-save actions such as participant bulk removal; only Save-button submissions return to their module list. Keep the desktop sidebar fixed while the display panel scrolls independently.

> Maintenance rule: after every code, database, or documentation change, update this file to mark the affected work as complete/in progress and update `README.md` when the change affects system behavior, setup, architecture, or usage.

## Current implementation status (2026-09-20)

- [x] Laravel 10, Jetstream, Livewire, Sanctum, Tailwind, Vite, and Pest project foundation is installed.
- [x] MySQL database `intramsproj` has been created and all current migrations have run successfully.
- [x] Initial intramurals schema exists: access fields, master data, registrations, coordinator operations, competition/results, tally, flags, and audit-log tables.
- [x] Universal blue-and-white email/password login is implemented for active administrators and coordinators; public registration is disabled.
- [x] Admin-only application shell is implemented with a blue-and-white sidebar, landing dashboard, live database summary counts, and module routes for Events, Students, Teams, Coordinators, Sports, and System Logs.
- [x] Local-development test administrator is seeded with an active `admin` role for dashboard testing.
- [x] Students/Athletes module is implemented with search, validation, create/edit, archive/restore, and audit history.
- [x] Student entry uses sanitized, normalized names and controlled school-year, year-level, section, and gender selections.
- [x] Courses are reusable name-only master data with generated internal codes; they can be selected or used to filter student records.
- [x] Teams module is implemented with edition-scoped team management, roster assignment, status controls, archive/restore, and audit history.
- [x] Intramurals Editions module is implemented with school-year/date validation, lifecycle status management, archive protection, and audit history.
- [x] Sports and Events module is implemented with sport archival, event configuration, lifecycle validation, and audit history.
- [x] Default sports seed data includes Basketball 3x3/5x5, Volleyball, Badminton, Table Tennis, Baseball, Softball, and Russian Softball.
- [x] Creating an intramurals edition automatically links the default sports to that edition through `edition_sports`.
- [x] The Sports module lists default and administrator-created sports in one standard management panel, with event management available per sport.
- [x] Athlete and participation-rule administration is implemented with transactional limit checks, team-roster validation, withdrawal history, and audit records. The admin-facing module is named **Athletes**; the underlying `event_registrations` records retain their technical name.
- [x] Athlete entry is sport-first: team and dual events filter athletes to the chosen team's roster, support name/student-number search, enforce exactly two athletes for a dual entry, and provide selector data from the controller for reliable rendering.
- [x] Competition administration now lists fixtures and lets administrators create fixtures for scheduled events and review flags.
- [x] Coordinator module is implemented with accounts, event assignments, assignment-change requests, administrator review, one trusted device, and device reset.
- [x] Existing automated test suite passes: 53 passed, 8 skipped; production frontend build is verified after student-data controls and the System Logs module update.
- [x] Administrator System Logs browser is implemented with actor, role, action, outcome, and date filters.
- [ ] Remaining modules include competition results, reports, and live monitoring.

## 1. Purpose and scope

Build a Laravel web application for administering a school intramurals program. It must let administrators manage students, teams, sports, events, coordinators, registrations, schedules, results, disqualification cases, live monitoring, tallying, reports, and audit history. Coordinators must only see and operate on events assigned to them.

### 1.1 Roles

| Role | Scope |
|---|---|
| `admin` | Full configuration and operational control; approves requests, results, and disqualification decisions. |
| `coordinator` | Operates only assigned events: views schedule, enters results, and raises team flags/assignment requests. |

Do not add student-facing accounts in version 1 unless the project requirements change.

### 1.2 Definitions

| Term | Meaning |
|---|---|
| Intramurals edition | A named program period, e.g. “2026 Intramurals”. All competition records belong to one edition. |
| Sport | A discipline, e.g. Basketball or Track and Field. |
| Event | A competition category under a sport and edition, e.g. “Men’s Basketball” or “100m Dash – Girls”. |
| Team | A competing school group/house/department. |
| Registration | A student’s enrollment in one event, optionally on behalf of a team. |
| Fixture | A scheduled contest or heat within an event. |
| Result | The submitted outcome for a fixture or final placement for an event. |

## 2. Functional requirements

### FR-01 Authentication and access control

- [x] Provide one blue-and-white email/password login page for active `admin` and `coordinator` accounts; no role selector is required.
- [x] Reject inactive/suspended accounts and unsupported roles during authentication, while retaining Fortify login rate limiting.
- [x] Disable public self-registration; accounts will be provisioned through administrator functions when that module is built.
- [ ] Provide logout, password reset (if institution requires it), session expiry, and login rate limiting review.
- [ ] Store passwords only with Laravel’s password hashing.
- [ ] Protect every private route with authentication and role middleware.
- [ ] Use policies for record-level access; a coordinator must receive `403` for any unassigned event, even if its ID is changed in the URL.

### FR-02 Admin master data

#### Team and edition implementation status

- [x] Admin can create, view, search, update, archive, and restore edition-specific teams.
- [x] Teams support `active`, `inactive`, and `disqualified` states; only active teams can receive athletes.
- [x] Admin can assign and remove active athletes from a team roster. Database constraints enforce one team per athlete per edition.
- [x] Team, roster, archive, and restore actions create audit records.
- [x] Team roster assignment supports course-filtered multi-select and selecting all currently filtered students.
- [x] Admin can create, search, update, and archive editions with `draft`, `active`, `closed`, and `archived` lifecycle statuses.
- [x] Edition dates are validated and the system permits only one active edition at a time.
- [x] Student names are stripped of markup/control characters, whitespace-normalized, and title-cased before storage; student number and section are normalized to uppercase.
- [x] Student forms require a `YYYY-YYYY` school year, year level (`1st`–`4th`), section (`A`–`C`), and gender (`Male`, `Female`, `LGBTQ+`).
- [x] Admin can manage sports and configure edition-specific team or individual events with sport, division, venue, capacity, schedule, result mode, and lifecycle status.
- [x] Event creation is simplified: internal event codes are generated automatically, division is omitted, and capacity is enforced per team for team events or per athlete for individual events.
- [x] Sport creation uses an automatically generated internal code, and sport is inferred when creating an event from a sport panel; venue and schedule are handled later at fixture level.
- [x] Event names are generated from edition, sport, and competition type; supported types include team, dual, and individual.
- [x] Participant capacity applies only to individual events. Team events have no per-team count field, while dual events allow multiple two-athlete pairs per team.

- [ ] Admin can create, view, update, archive, and search students, teams, sports, editions, and events.
- [ ] Students use a unique institutional `student_number`; retain historical records rather than hard-deleting participants.
- [ ] Teams support `active`, `inactive`, and `disqualified` states.
- [ ] Events support type (`team` or `individual`), division/category, venue, capacity, start/end time, and lifecycle state.

### FR-03 Registrations and participation limits

#### Implementation status

- [x] Admin can register active students in scheduled events and withdraw registrations without deleting history.
- [x] Active participation rules enforce maximum events and distinct sports per student within an edition in a transaction.
- [x] Duplicate registrations, inactive students, closed events, invalid team/event pairings, and team-roster mismatches are rejected.

- [ ] Admin can register a student to an event and, for a team event, associate the correct team.
- [ ] Configure one active participation rule per edition: maximum distinct sports and maximum events per student.
- [ ] Validate limits on the server and in a database transaction; the UI check is only a convenience.
- [ ] Prevent duplicate registration of the same student in the same event.
- [ ] Reject registrations for inactive students/teams, closed events, or events in cancelled/completed state.

### FR-04 Coordinator management and device policy

#### Implementation status

- [x] Admin creates coordinator accounts from the Coordinators sidebar module and can assign/revoke one or more event assignments.
- [x] Coordinators submit add, remove, or reassign requests from `/coordinator/dashboard`; administrators approve or reject them.
- [x] A coordinator's first successful login registers one trusted device. Later logins require its HTTP-only token; an administrator reset revokes it and permits a replacement device.
- [x] The database stores only the SHA-256 device-token hash in `coordinator_devices`, never a browser fingerprint or raw token.
- [x] Reassignment requests store both `source_event_id` and the requested target `event_id`; approval atomically revokes the source assignment and activates the target assignment.

- [ ] Admin creates coordinator user accounts and assigns coordinators to one or more events.
- [ ] Coordinators may request an added, removed, or reassigned event; admin approves or rejects the request.
- [ ] Enforce at most one active trusted device per coordinator account. A new device is denied until an admin resets the prior device, except when logging in from the registered device.
- [ ] Store a server-generated, revocable device token/hash—not a browser fingerprint as the only control.

### FR-05 Event lifecycle and scheduling

- [ ] Permit only these lifecycle transitions: `draft → scheduled → live → completed`; `draft/scheduled/live → cancelled`; admin may set `disqualified` only when justified by a resolved case.
- [ ] Admin creates fixtures/heats for scheduled events, assigns schedule, venue, and participating teams/students.
- [ ] Coordinators can start and close only their assigned scheduled/live events; an admin may override with an audit reason.

### FR-06 Results, scoring, and tally

- [ ] Coordinator submits a draft result only for an assigned event that is live (or a scheduled event if explicitly enabled by policy).
- [ ] Validate each result against the event scoring configuration; do not force identical scoring fields on all sports.
- [ ] Admin approves, rejects, or corrects submitted results. Corrections require a reason and audit entry.
- [ ] A result is idempotent per fixture/result revision so double-clicks and retries cannot create duplicate outcomes.
- [ ] Recalculate team tally from approved results only. The database is the source of truth.
- [ ] Support configurable placement points (e.g. gold/silver/bronze) and optional win-based points per sport/event.

### FR-07 Flags and disqualification

- [ ] A coordinator may flag a team or registration in an assigned event, with a required reason and supporting notes.
- [ ] Flag states: `open`, `under_review`, `cleared`, `upheld`.
- [ ] Only an admin can uphold a flag, disqualify a team/registration, or reverse the decision; each resolution needs notes.
- [ ] Exclude disqualified competitors from future fixtures and tally calculations according to the rule configured for the event.

### FR-08 Dashboards, live updates, reports

- [x] Admin landing dashboard and protected sidebar navigation exist for Dashboard, Students, Coordinators, Sports & Events, and System Logs.
- [x] Dashboard shows live counts for active students, active coordinators, scheduled/live events, and pending coordinator requests.
- [ ] Expand the dashboard with unresolved flags, recent result activity, current tally, and live-event monitoring as those modules are implemented.
- [x] Coordinator dashboard lists active event assignments and supports assignment-change requests; sport-operation actions remain pending.
- [ ] Public/admin live monitor shows event, coordinator, status, current score/placements, and last update.
- [ ] Use broadcast/WebSockets when available; provide polling every 3–5 seconds as a safe fallback.
- [ ] Generate printable score sheets and reports for events, coordinators, athlete participation, and overall tally. Queue large exports.
- [x] Provide an editable Basketball score sheet from Basketball sport cards, matching the supplied FIBA reference as a fixed A4-portrait preview and downloadable layout with compact Team A/B grids, four A/B running-score blocks, period/final score, official-signature fields, selected-edition roster prefilling, and a one-page A4 PDF download.
- [x] Replace the Basketball scoresheet licence-number field with **Yr. & Sec.** prefilled from each registered student's profile, while leaving the player **No.** fields blank for game-day entry.
- [x] Replace the Basketball scoresheet's temporary CSS logo with the supplied FIBA Basketball WebP asset for both screen preview and print output.
- [x] Add an audited Basketball scoresheet PDF preview that carries the current editable form values into a dedicated one-page A4-portrait Dompdf template, opens in a new browser tab, and exposes the browser PDF viewer's download control.
- [x] Expand Basketball scoresheet player-name space by reducing the player **No.** field and each foul cell to 5 px squares; adapt player-name text to fit and place each running-score number inside its sole compact editable box under the A/B columns, without an extra score box or gap, in both preview and PDF output.

### FR-09 Auditability and operations

#### Implementation status

- [x] Administrators can browse append-only audit records and filter by actor, role, action, outcome, and date range.

- [ ] Provide a System Activity Log screen for administrators, with filters for date range, actor, role, action, subject type, and outcome.
- [ ] Log authentication activity: successful login, logout, failed login, password reset, password change, two-factor challenge, account suspension, device registration, and device reset/revocation.
- [ ] Log major admin and coordinator actions: create, update/edit, archive/delete, restore, status changes, assignments, registrations, lifecycle changes, result submissions/approvals/rejections/corrections, flags, and disqualifications.
- [ ] Record both successful and denied major operations, especially unauthorized event access attempts and validation-sensitive result actions.
- [x] Audit schema stores actor, actor role, action, subject, before/after values, IP address, user agent, request ID, outcome, and timestamp. Never store passwords, raw device tokens, or other secrets in logs.
- [ ] Use soft deletes/archive states for master data with historical competition use.
- [ ] Schedule daily database backups and pre-event backups; regularly test restoration.

## 3. Database schema

Use Laravel migrations, foreign keys, timestamps, and `softDeletes()` where marked. Prefer `bigint` keys unless the existing project standard differs.

### 3.1 Identity and reference data

| Table | Key columns | Constraints / purpose |
|---|---|---|
| `users` | `id`, `name`, `email`, `password`, `role`, `status` | `email` unique; role: `admin`/`coordinator`; status: `active`/`inactive`/`suspended`. |
| `students` | `id`, `student_number`, `first_name`, `middle_name`, `last_name`, `school_year`, `gender`, `year_level`, `section`, `status` | `student_number` unique; soft delete; school year is `YYYY-YYYY`; controlled year/section/gender values; status `active`/`inactive`. |
| `courses` | `id`, `name`, `code`, `status` | reusable course reference; `code` unique; soft delete. |
| `intramural_editions` | `id`, `name`, `school_year`, `starts_on`, `ends_on`, `status` | unique `(name, school_year)`; status `draft`/`active`/`closed`/`archived`. |
| `teams` | `id`, `edition_id`, `name`, `code`, `description`, `status` | unique `(edition_id, code)`; soft delete. Teams are edition-specific. |
| `sports` | `id`, `name`, `code`, `description`, `status` | unique `name` and `code`; soft delete. |
| `events` | `id`, `edition_id`, `sport_id`, `name`, `code`, `competition_type`, `division`, `venue`, `starts_at`, `ends_at`, `status`, `result_mode` | unique `(edition_id, code)`; `competition_type`: `team`/`individual`; state enforced by service. |

### 3.2 Registration, rules, and staffing

| Table | Key columns | Constraints / purpose |
|---|---|---|
| `participation_rules` | `id`, `edition_id`, `name`, `max_sports`, `max_events`, `is_active` | at most one active rule per edition (enforce in service; add DB support where available). |
| `event_registrations` | `id`, `event_id`, `student_id`, `team_id`, `status`, `registered_by`, `registered_at` | unique `(event_id, student_id)`; `team_id` nullable only for individual events; statuses `active`/`withdrawn`/`disqualified`. |
| `team_members` | `id`, `edition_id`, `team_id`, `student_id`, `assigned_by`, `assigned_at` | unique `(team_id, student_id)` and `(edition_id, student_id)`; preserves the roster assignment source and prevents an athlete joining two teams in one edition. |
| `coordinator_assignments` | `id`, `coordinator_id`, `event_id`, `assigned_by`, `status`, `approved_at`, `revoked_at` | `coordinator_id` and `assigned_by` FK to `users`; unique active coordinator/event pair; status `active`/`revoked`. |
| `coordinator_requests` | `id`, `coordinator_id`, `event_id`, `request_type`, `reason`, `status`, `reviewed_by`, `reviewed_at`, `review_notes` | types `add_event`/`remove_event`/`reassign`; status `pending`/`approved`/`rejected`/`cancelled`. |
| `coordinator_devices` | `id`, `user_id`, `token_hash`, `device_label`, `last_seen_at`, `revoked_at`, `registered_at` | one non-revoked device per coordinator; never store the raw token. |

### 3.3 Competition and scoring

| Table | Key columns | Constraints / purpose |
|---|---|---|
| `event_scoring_rules` | `id`, `event_id`, `mode`, `config_json`, `is_active` | `mode`: `score`, `placement`, `win_loss`, `custom`; JSON defines valid metric/ranking rules. Version or archive changes after results exist. |
| `scoring_point_rules` | `id`, `edition_id`, `sport_id`, `event_id`, `placement`, `points`, `medal` | sport/event nullable as a precedence rule; unique rule scope + placement. |
| `fixtures` | `id`, `event_id`, `round_name`, `sequence`, `scheduled_at`, `venue`, `status`, `started_at`, `completed_at` | unique `(event_id, sequence)`; status `scheduled`/`live`/`completed`/`cancelled`. |
| `fixture_competitors` | `id`, `fixture_id`, `team_id`, `registration_id`, `lane_or_slot`, `status` | exactly one competitor reference per row (team **or** registration); unique `(fixture_id, lane_or_slot)`. |
| `result_submissions` | `id`, `fixture_id`, `revision`, `status`, `submitted_by`, `submitted_at`, `reviewed_by`, `reviewed_at`, `review_notes`, `payload_json` | unique `(fixture_id, revision)`; `draft`/`submitted`/`approved`/`rejected`/`superseded`. Raw sport-specific entry is retained in `payload_json`. |
| `result_entries` | `id`, `result_submission_id`, `fixture_competitor_id`, `score`, `rank`, `points_awarded`, `is_winner`, `details_json` | unique `(result_submission_id, fixture_competitor_id)`; normalized entries allow tallying while `details_json` keeps sport-specific values. |
| `team_tallies` | `id`, `edition_id`, `team_id`, `gold_count`, `silver_count`, `bronze_count`, `points`, `updated_at` | unique `(edition_id, team_id)`; derived/cached read model rebuilt from approved results. |

### 3.4 Compliance and history

| Table | Key columns | Constraints / purpose |
|---|---|---|
| `team_flags` | `id`, `event_id`, `team_id`, `registration_id`, `reported_by`, `reason`, `notes`, `status`, `resolved_by`, `resolved_at`, `resolution_notes` | target exactly one of team/registration; workflow states in FR-07. |
| `audit_logs` | `id`, `user_id`, `actor_role`, `action`, `auditable_type`, `auditable_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `request_id`, `outcome`, `created_at` | append-only; `outcome`: `success`/`denied`/`failed`; JSON values. Never record secrets. |

### 3.5 Required indexes and integrity rules

- [ ] Index every foreign key plus `events(status, starts_at)`, `fixtures(event_id, status, scheduled_at)`, `coordinator_requests(status, created_at)`, and `team_flags(status, created_at)`.
- [ ] Add unique keys listed above; never rely on a disabled submit button for uniqueness.
- [ ] Add database check constraints where supported: non-negative scores/points, dates ordered correctly, and mutually exclusive competitor/flag target columns.
- [ ] Use transactions and row locking when creating registrations, approving results, resolving flags, and rebuilding affected tally rows.

## 4. Application services and key functions

Controllers orchestrate requests; services own business rules; policies own authorization. Keep controllers thin.

| Service | Public functions / responsibility |
|---|---|
| `StudentService` | `create`, `update`, `archive`, `importBatch`; validate unique student number and import errors. |
| `TeamService` | `create`, `update`, `archive`, `restore`, `assignStudent`, `removeStudent`; prevents edition changes while rostered and blocks assignments to inactive/disqualified teams or inactive students. |
| `EditionService` | `create`, `update`, `archive`; validates dates, records lifecycle changes, preserves historical records, and permits only one active edition. |
| `EventService` | `create`, `update`, `transitionStatus`, `createFixture`, `startFixture`, `completeFixture`; enforce lifecycle transitions. |
| `ParticipationService` | `registerStudent`, `withdrawRegistration`, `validateLimits`; calculate distinct sport/event counts inside a transaction. |
| `CoordinatorService` | `assign`, `revoke`, `requestAssignmentChange`, `reviewRequest`, `registerOrVerifyDevice`, `resetDevice`. |
| `ResultService` | `saveDraft`, `submit`, `approve`, `reject`, `correct`; validate assignment, event/fixture status, schema, and revision/idempotency. |
| `TallyService` | `recalculateFixture`, `rebuildEditionTally`, `getLeaderboard`; derive values only from approved result entries. |
| `FlagService` | `create`, `beginReview`, `clear`, `uphold`; apply disqualification consequences atomically. |
| `ReportService` | `eventReport`, `coordinatorReport`, `participationReport`, `tallyReport`, `scoreSheet`; dispatch large documents to queues. |
| `AuditService` | `recordAuthentication`, `recordActivity`, `recordDeniedAction`; append-only logging for sign-ins and all major create/update/archive/delete/status/competition actions. It must redact secrets before storage. |

## 5. Business rules and workflows

### BR-01 Registration

1. Confirm admin authorization, active edition, event, student, and—when supplied—team.
2. Lock the student’s registrations for the edition.
3. Count distinct sports and events among active registrations.
4. Reject if adding the registration exceeds the active rule.
5. Insert the registration and audit the action in one transaction.

### BR-02 Coordinator authorization

A coordinator can read or change an event only when an active `coordinator_assignments` record exists for that user and event. Admins bypass this check. Assignment status must be verified again in every mutating service method.

### BR-02A Team roster assignment

An athlete may belong to only one team in each intramurals edition. Team membership is not event registration: it establishes the athlete's edition roster; a later registration module decides which rostered athlete enters a particular event. Only active students may be assigned, and only active teams can receive assignments. Archiving a team preserves its historical roster.

### BR-03 Event state transitions

Only allowed transitions in FR-05 are accepted. Completed/cancelled events are immutable for coordinators. An admin correction after completion creates a new result revision and an audit record; it never silently overwrites an approved result.

### BR-04 Result approval and tally update

1. Coordinator submits a result for an assigned live fixture.
2. The system validates competitor membership, scoring mode, numeric ranges, rank uniqueness, and duplicate revision.
3. Admin approves or rejects it. Approval locks the relevant fixture/result rows.
4. Tally points and medals are derived from the approved entries and active scoring rules.
5. Recompute only the affected team/edition tally rows, commit, then broadcast a refresh event. If broadcasting fails, the committed database result remains correct and polling recovers the UI.

### BR-05 Flag/disqualification

Coordinator creation of a flag does not disqualify anyone. Admin review is required. On an upheld decision, update the target registration/team status, cancel or amend affected future fixture participation, recalculate affected tally entries, record the decision, and notify relevant staff.

### BR-06 Device restriction

After password authentication, issue a secure device token only if no active device exists or the presented token matches the active device. Otherwise deny sign-in and provide an admin-reset path. Rotate/revoke tokens on reset, suspension, password reset, or suspicious-login response.

### BR-07 System activity logging

Every successful or denied major operation must create an append-only audit entry. Authentication middleware records login/logout/failed-login activity; service methods record business actions after the transaction outcome is known. For edits, save a redacted before/after snapshot. For archive/delete operations, record the affected record and reason where one is required. Logs are administrator-only, searchable, and cannot be edited or deleted through the application.

## 6. Routes and screens

- [ ] Admin: dashboard; students; teams; editions; sports; events/fixtures; registrations; participation rules; coordinators; assignments; requests; flags; live monitor; tally; reports; audit logs; settings.
- [ ] Coordinator: dashboard; my events; fixture/event view; result entry/history; flag form/history; assignment request form.
- [ ] Live monitor: read-only event status and current tally; decide whether it is authenticated-only or public before implementation.
- [ ] Use named web routes for Blade pages and `/api` endpoints only where AJAX/realtime clients genuinely need them.

## 7. Delivery plan

### Milestone 0 — Foundation

- [ ] Confirm school-specific rules: editions, teams, scoring, tie handling, result approval, public scoreboard, and data-retention policy.
- [ ] Configure `.env`, database, mail, queue/cache drivers, storage, and Git branches.
- [x] Create and migrate the initial MySQL schema for authentication/session support and intramurals data.
- [x] Add an idempotent local-development administrator seeder for testing protected admin routes.
- [x] Document how to restore the local development administrator after database-resetting tests.
- [ ] Create base Laravel layout, authentication, roles, policies, audit logging, seeders, and test factories.

### Milestone 1 — Master data and registration

- [x] Migrate identity/reference tables and indexes.
- [x] Deliver sport and event management. Student, team, and edition management are complete.
- [ ] Deliver participation rules and transactional event registration.
- [ ] Add feature tests for duplicate student IDs, duplicate registrations, and both participation limits.

### Milestone 2 — Coordination and scheduling

- [x] Deliver coordinator accounts, assignments, assignment requests, and device reset workflow.
- [ ] Deliver lifecycle management, fixtures, and competitor allocation.
- [ ] Add authorization tests proving cross-coordinator access is denied.

### Milestone 3 — Competition operations

- [ ] Deliver scoring-rule configuration, result draft/submission/approval/revision, and tally computation.
- [ ] Deliver flag investigation and admin disqualification decisions.
- [ ] Add transaction/concurrency tests for simultaneous registration and result approval.

### Milestone 4 — Visibility and reporting

- [ ] Complete Milestone 4: the admin dashboard shell/sidebar is done; live monitor, broadcast/polling fallback, score sheets, and queued reports remain.
- [ ] Add filtering, pagination, exports, and audit-log search.

### Milestone 5 — Quality and deployment

- [ ] Run unit, feature, policy, and browser tests for every business rule above.
- [ ] Security-test CSRF, XSS, IDOR, brute force, unauthorized API access, duplicate submissions, and session/device revocation.
- [ ] Configure scheduled backups, queue worker supervision, error logging, monitoring, and a tested restore procedure.
- [ ] Measure before optimizing; add eager loading, pagination, targeted indexes, cache, and Redis only where evidence supports it.

## 8. Definition of done

A module is done only when its migration/schema, model relationships, form/API validation, service logic, policy checks, routes/UI, audit coverage, and automated tests are complete. A screen that works visually but bypasses authorization, server-side validation, or database constraints is not complete.
