# Intramurals Management System — Master TODO

## 0. Recommended Technology Stack

| Layer | Recommendation |
|---|---|
| Backend | Laravel |
| Language | PHP |
| Database | MySQL/MariaDB |
| Frontend | Blade + Bootstrap/Tailwind + JavaScript |
| API | Laravel API routes/controllers |
| Authentication | Laravel authentication |
| Queue | Redis when available, database queue as fallback |
| Cache | Redis when available |
| Realtime | Laravel broadcasting/WebSockets or lightweight polling fallback |
| Web server | Nginx |
| PHP | PHP-FPM |
| Load balancer | Nginx/HAProxy |
| Deployment | Linux server |
| Reports | Laravel PDF library |
| Database migrations | Laravel migrations |
| Authorization | Policies/Gates |
| Passwords | Laravel Bcrypt |

### Important architecture decision

Do not build the frontend as a huge JavaScript SPA unless there is a strong reason.

For a small-server intramurals system:

> Laravel + Blade + lightweight JavaScript + AJAX/API where necessary

will generally be easier to maintain and less resource-heavy than a large SPA.

---

# PHASE 1 — Understand and Freeze the Requirements

## TODO 1.1 — Define the main actors

Create these roles:

```text
ADMIN
COORDINATOR
```

Do not create unnecessary roles yet.

## TODO 1.2 — Define the system objects

The system should revolve around:

```text
Student/Athlete
Team
Sport
Event
Coordinator
Assignment
Participation
Score
Disqualification
Coordinator Request
Report
```

## TODO 1.3 — Define relationships

The basic relationship should be:

```text
Sport
   ↓
Event
   ↓
Team
   ↓
Athletes
```

while:

```text
Coordinator
   ↓
Coordinator Assignment
   ↓
Sport/Event
```

An important design decision:

**Do not directly put `coordinator_id` inside every sport.**

Instead, create an assignment table because one coordinator can handle multiple events and an event may potentially have multiple coordinators.

---

# PHASE 2 — Database Design

Create the database before building the UI.

## TODO 2.1 — Create users table

Use Laravel's authentication system.

Basic fields:

```text
id
name
email/username
password
role
status
created_at
updated_at
```

Possible status:

```text
active
inactive
suspended
```

## TODO 2.2 — Students/Athletes

Create:

```text
students
```

Example:

```text
id
student_id
first_name
middle_name
last_name
gender
year_level
section
status
created_at
updated_at
```

Use `student_id` as a unique identifier.

---

# PHASE 3 — Sports and Events

Create:

```text
sports
events
```

### Sports

Example:

```text
Basketball
Volleyball
Badminton
Chess
Swimming
Track and Field
```

### Events

An event belongs to a sport.

Example:

```text
Sport:
Basketball

Events:
Men's Basketball
Women's Basketball
```

Database relationship:

```text
sports
   |
   | 1-to-many
   ↓
events
```

---

# PHASE 4 — Teams

Create:

```text
teams
```

Example:

```text
id
team_name
team_code
description
status
created_at
updated_at
```

Status could be:

```text
active
disqualified
inactive
```

Do not permanently delete important competition records simply because an administrator clicks Delete.

For historical competition data, prefer:

```text
soft delete
```

or status changes.

---

# PHASE 5 — Athlete Assignment

This is one of the most important parts.

Create a pivot/assignment table such as:

```text
event_participants
```

Example:

```text
id
event_id
student_id
team_id
status
created_at
updated_at
```

This allows:

```text
Student A
   ↓
Basketball
   ↓
Team Red
```

and:

```text
Student A
   ↓
Badminton
```

---

# PHASE 6 — Athlete Participation Rules

Admin needs to control:

> How many sports/events can one student participate in?

Create something such as:

```text
participation_rules
```

Possible fields:

```text
id
rule_name
max_sports
max_events
description
active
```

Example:

```text
Maximum sports per student = 3
Maximum events per student = 5
```

## TODO 6.1 — Enforce rules in backend

**Very important:**

Do not rely only on JavaScript to prevent violations.

Bad:

```text
JavaScript says student already has 3 sports
```

because users can bypass JavaScript.

Instead:

```text
Request
   ↓
Laravel Controller
   ↓
Validation
   ↓
Business Rule Service
   ↓
Database
```

The server must make the final decision.

---

# PHASE 7 — Coordinator Management

Create:

```text
coordinators
```

or preferably use the `users` table with a coordinator role and a separate coordinator profile if additional information is needed.

Example:

```text
users
   ↓
role = coordinator
```

---

# PHASE 8 — Coordinator Assignment

Create:

```text
coordinator_assignments
```

Example:

```text
id
coordinator_id
event_id
assigned_by
status
approved_at
created_at
updated_at
```

Status:

```text
pending
approved
rejected
revoked
```

This supports:

> Coordinator can be assigned to multiple events.

Example:

```text
Coordinator A
 ├── Basketball
 ├── Volleyball
 └── Badminton
```

---

# PHASE 9 — Coordinator Assignment Requests

Create:

```text
coordinator_requests
```

Example:

```text
id
coordinator_id
event_id
request_type
reason
status
reviewed_by
reviewed_at
created_at
```

Request types:

```text
ADD_EVENT
REMOVE_EVENT
REASSIGN
```

Flow:

```text
Coordinator
     ↓
Request
     ↓
Admin
     ↓
Approve / Reject
     ↓
Assignment updated
```

---

# PHASE 10 — One Phone / One Active Device

This requirement needs careful implementation.

You said:

> one phone only active per coordinator account

Do not simply store:

```text
phone_number
```

and assume that means one device.

Instead, create a device/session concept:

```text
coordinator_devices
```

Example:

```text
id
user_id
device_identifier
device_name
last_active_at
status
created_at
updated_at
```

When a coordinator logs in:

```text
Login
 ↓
Check active device
 ↓
No active device?
 → Register device

Existing device?
 → Allow login

Different active device?
 → Deny / require admin reset
```

Also provide Admin:

```text
Reset Coordinator Device
```

so a coordinator who changes phones does not become permanently locked out.

### Security note

Do not rely on an easily spoofed browser fingerprint as the sole security mechanism.

Use secure authentication/session controls and treat the device restriction as an additional control.

---

# PHASE 11 — Coordinator Permissions

Every coordinator request must be checked.

Example:

```text
Coordinator A
assigned:
Basketball
Volleyball
```

If Coordinator A tries:

```text
POST /events/football/results
```

the backend must return:

```text
403 Forbidden
```

even if they manually modify the URL.

---

# PHASE 12 — Event Status

Create event lifecycle states.

Example:

```text
DRAFT
SCHEDULED
LIVE
COMPLETED
CANCELLED
DISQUALIFIED
```

Flow:

```text
DRAFT
 ↓
SCHEDULED
 ↓
LIVE
 ↓
COMPLETED
```

Do not allow arbitrary status changes.

---

# PHASE 13 — Competition Results

Create tables for results rather than storing everything directly inside events.

For example:

```text
event_results
```

Possible fields:

```text
id
event_id
team_id
position
score
status
submitted_by
submitted_at
approved_by
approved_at
```

Depending on the sport, you may need a more detailed structure.

For example, basketball may need:

```text
Team A = 78
Team B = 71
```

while track events may need:

```text
Athlete
time
rank
```

Therefore, **do not assume every sport uses the same scoring system.**

---

# PHASE 14 — Sport-Specific Scoring

Create a flexible scoring design.

Possible:

```text
sport_scoring_rules
```

Example:

```text
Basketball:
Winner receives 1 competition win

Track:
Rank 1 = 10 points
Rank 2 = 7 points
Rank 3 = 5 points
```

The system should calculate the overall tally based on the configured scoring rules.

---

# PHASE 15 — Score Submission Workflow

Coordinator:

```text
Login
 ↓
Select assigned event
 ↓
Enter result
 ↓
Validate
 ↓
Submit
```

Backend:

```text
Check coordinator assignment
 ↓
Check event status
 ↓
Check team status
 ↓
Validate result
 ↓
Save result
 ↓
Update tally
 ↓
Broadcast update
```

---

# PHASE 16 — Disqualification System

Coordinator can:

> Flag team for disqualification/investigation.

Important:

**Coordinator should not necessarily have the ability to permanently disqualify a team.**

Use:

```text
FLAGGED
```

first.

Example:

```text
Coordinator
      ↓
Flag Team
      ↓
Investigation
      ↓
Admin Decision
      ↓
Disqualified / Cleared
```

Create:

```text
team_flags
```

Fields:

```text
id
team_id
event_id
reported_by
reason
status
admin_notes
resolved_by
resolved_at
created_at
```

---

# PHASE 17 — Live Event Monitoring

Create an Admin dashboard:

```text
LIVE EVENTS
```

Display:

```text
Event
Sport
Coordinator
Status
Teams
Current Score
Last Update
```

Example:

```text
BASKETBALL
Men's Finals

Team A   72
Team B   68

Coordinator: Juan
Status: LIVE
Last Update: 10:32 PM
```

---

# PHASE 18 — Realtime Tally

The overall scoreboard could look like:

```text
TEAM          GOLD    SILVER    BRONZE    TOTAL
------------------------------------------------
Team A          5       3         2        10
Team B          3       5         4        12
Team C          2       2         3         7
```

The exact columns should depend on your intramurals scoring rules.

---

# PHASE 19 — Realtime Architecture

Do not make the browser repeatedly refresh the entire page.

Prefer:

```text
Coordinator
     ↓
Laravel API
     ↓
Database
     ↓
Event/Queue
     ↓
Broadcast
     ↓
Admin Monitor
```

For example:

```text
Coordinator submits result
        ↓
ResultSaved event
        ↓
Queue
        ↓
Broadcast
        ↓
Live dashboard updates
```

For small deployments, you can also implement:

```text
AJAX polling every 3–5 seconds
```

as a fallback.

---

# PHASE 20 — Queue System

Use queues for jobs that do not need to block the user.

Good queue candidates:

```text
PDF generation
Report generation
Bulk student import
Bulk assignment
Notifications
Scoreboard broadcast processing
Large data processing
```

Do **not** queue simple operations unnecessarily.

Example:

```text
Coordinator submits score
```

The actual validation and saving should happen immediately.

Then:

```text
Generate report
```

can happen asynchronously.

---

# PHASE 21 — Redis

If the server supports Redis:

Use it for:

```text
Cache
Queue
Sessions (if appropriate)
Rate limiting
Realtime infrastructure
```

But don't make Redis a hard dependency if the school's server is extremely limited.

Provide:

```text
Redis
```

as the preferred production driver, with:

```text
Database queue
```

as a fallback.

---

# PHASE 22 — API Structure

Organize API endpoints logically.

Example:

```text
/api/admin/students
/api/admin/teams
/api/admin/sports
/api/admin/events
/api/admin/coordinators
/api/admin/assignments

/api/coordinator/events
/api/coordinator/events/{event}/results
/api/coordinator/events/{event}/flags

/api/live/events
/api/live/tally
```

---

# PHASE 23 — API Security

Every protected endpoint must have:

```text
Authentication
+
Authorization
+
Validation
+
Rate limiting where appropriate
```

Example:

```text
POST /api/coordinator/events/5/result
```

must verify:

```text
Is user logged in?
        ↓
Is user a coordinator?
        ↓
Is coordinator assigned to Event 5?
        ↓
Is Event 5 currently accepting results?
        ↓
Is submitted data valid?
        ↓
Save
```

---

# PHASE 24 — Laravel Authorization

Use Laravel:

```text
Policies
Gates
Middleware
Form Requests
```

Do not scatter permission checks randomly throughout controllers.

Example concept:

```php
$this->authorize('update', $event);
```

---

# PHASE 25 — Validation

Every form/API request needs server-side validation.

Example:

```text
student_id → required + unique
event_id → required + exists
team_id → required + exists
score → numeric + appropriate range
```

Never trust:

```text
hidden inputs
JavaScript
URL parameters
frontend restrictions
```

---

# PHASE 26 — Database Integrity

Use:

```text
Primary keys
Foreign keys
Unique constraints
Indexes
Transactions
```

Example:

```text
student_id UNIQUE
```

and:

```text
event_id + student_id
```

should have a unique constraint if a student can only be registered once for an event.

---

# PHASE 27 — Transactions

Use database transactions for operations involving multiple changes.

Example:

```text
Assign student to event
 ↓
Check participation limit
 ↓
Create participant record
 ↓
Update related data
```

If something fails:

```text
ROLLBACK
```

so the database doesn't become partially updated.

---

# PHASE 28 — Prevent Duplicate Results

A coordinator should not accidentally submit the same result twice.

Implement:

```text
unique identifiers
+
database constraints
+
backend validation
```

Do not depend only on:

```text
"Submit button disabled"
```

because users can send requests manually.

---

# PHASE 29 — Audit Logs

This is highly recommended for an intramurals system.

Create:

```text
audit_logs
```

Record important actions:

```text
Admin created event
Admin deleted team
Admin assigned athlete
Admin approved coordinator
Coordinator submitted result
Coordinator flagged team
Admin disqualified team
Admin changed score
```

Store:

```text
user_id
action
entity_type
entity_id
old_values
new_values
ip_address
created_at
```

This is extremely useful when someone asks:

> "Who changed this score?"

---

# PHASE 30 — Reports

Admin should be able to generate:

## Event report

```text
Event
Sport
Date
Coordinator
Teams
Participants
Results
Winner
```

## Coordinator report

```text
Coordinator
Assigned events
Dates
Results submitted
Flags submitted
```

## Overall tally

```text
Team
Gold
Silver
Bronze
Points
```

## Athlete participation report

```text
Student
Sports
Events
Team
Status
```

---

# PHASE 31 — Printable Score Sheets

Admin selects:

```text
Sport
 ↓
Event
 ↓
Generate Score Sheet
```

System generates a printable PDF containing:

```text
School / Intramurals
Sport
Event
Date
Coordinator

Team A
Participants...

Team B
Participants...

Score section
Winner
Coordinator signature
```

Do not generate PDFs directly inside the normal HTTP request if the report becomes large.

Use a queue for large reports.

---

# PHASE 32 — Admin Dashboard

Dashboard should contain:

```text
Total Students
Total Teams
Total Sports
Total Events
Active Coordinators
Live Events
Pending Requests
Flagged Teams
```

Then:

```text
LIVE EVENTS
```

and:

```text
CURRENT TEAM TALLY
```

---

# PHASE 33 — Coordinator Dashboard

Coordinator sees only their assigned events.

Example:

```text
WELCOME, COORDINATOR

MY EVENTS

Basketball Men's
[ LIVE ]

Volleyball Women's
[ SCHEDULED ]

Badminton Singles
[ COMPLETED ]
```

Actions:

```text
Open Event
Submit Result
Flag Team
View Event
Request Assignment
```

---

# PHASE 34 — Admin Navigation

Keep it simple:

```text
Dashboard

Students
Teams

Sports
Events

Coordinators
Coordinator Requests

Assignments
Participation Rules

Live Monitor
Score Tally

Reports
Audit Logs

System Settings
```

---

# PHASE 35 — Security Checklist

Before production, verify:

### Authentication

- [ ] Password hashing
- [ ] Login rate limiting
- [ ] Session protection
- [ ] Logout
- [ ] Password reset if required
- [ ] Session expiration

### Authorization

- [ ] Admin middleware
- [ ] Coordinator middleware
- [ ] Event assignment authorization
- [ ] Policy checks

### Input security

- [ ] CSRF protection
- [ ] Validation
- [ ] SQL injection protection
- [ ] XSS protection
- [ ] File upload validation
- [ ] Output escaping

### API

- [ ] Authentication
- [ ] Authorization
- [ ] Rate limiting
- [ ] Request validation
- [ ] Proper HTTP status codes

---

# PHASE 36 — Performance

Don't optimize blindly.

First:

```text
Build
 ↓
Measure
 ↓
Find bottleneck
 ↓
Optimize
```

Use:

```text
Database indexes
Eager loading
Pagination
Caching
Queues
Query optimization
```

Avoid:

```text
SELECT *
```

when unnecessary.

Avoid loading thousands of students into one page.

Use:

```text
pagination
search
filters
```

---

# PHASE 37 — Database Indexes

Index fields commonly searched or joined:

```text
student_id
event_id
sport_id
team_id
coordinator_id
status
created_at
```

But don't blindly index every column.

Indexes have a storage/write cost.

---

# PHASE 38 — Caching

Cache relatively stable information:

```text
Sports
Event configuration
Participation rules
System settings
```

Do not aggressively cache constantly changing scores unless the architecture requires it.

For live tally:

```text
Database → cache → broadcast
```

can be considered after measuring performance.

---

# PHASE 39 — Load Balancer

For a real multi-server deployment:

```text
                    ┌── Web Server 1
                    │
User
 ↓                  ├── Web Server 2
Load Balancer ──────┤
                    └── Web Server 3
                           ↓
                       Database
                           ↓
                         Redis
```

Important:

If using multiple application servers, don't rely on local server storage for important shared data.

Use:

```text
Shared database
Shared Redis
Shared file/object storage
```

where needed.

---

# PHASE 40 — Session Architecture

If multiple servers are used:

```text
Server 1
Server 2
Server 3
```

should not each maintain independent session state.

Use shared session storage such as:

```text
Redis
```

or another shared backend.

---

# PHASE 41 — Queue Architecture

For multiple workers:

```text
Laravel App
     ↓
Redis Queue
     ↓
Worker 1
Worker 2
Worker 3
```

Workers process:

```text
reports
notifications
imports
other background jobs
```

---

# PHASE 42 — Deployment

Production architecture:

```text
Internet
   ↓
Nginx / Load Balancer
   ↓
Laravel
   ↓
PHP-FPM
   ↓
MySQL
```

Optional:

```text
        Redis
       ↙     ↘
    Cache    Queue
```

---

# PHASE 43 — Environment Configuration

Never hard-code:

```text
database password
API keys
secret keys
Redis credentials
```

Use:

```text
.env
```

and proper server environment configuration.

Never commit `.env` into Git.

---

# PHASE 44 — Git Structure

Use Git from day one.

Branches:

```text
main
develop
feature/authentication
feature/students
feature/events
feature/coordinators
feature/results
feature/live-monitor
```

Do not make the entire system in one giant commit.

---

# PHASE 45 — Testing

Create tests for the **business rules**, not just pages.

## Student

```text
Can create student
Cannot duplicate student ID
```

## Participation

```text
Student can join allowed number of sports
Student cannot exceed configured limit
```

## Coordinator

```text
Coordinator can access assigned event
Coordinator cannot access unassigned event
```

## Results

```text
Coordinator can submit valid result
Coordinator cannot submit invalid result
Coordinator cannot submit to unauthorized event
```

## Disqualification

```text
Coordinator can flag
Coordinator cannot finalize admin-only decision
Admin can resolve flag
```

---

# PHASE 46 — Security Testing

Before deployment, test:

```text
SQL Injection
XSS
CSRF
Broken authorization
IDOR
Brute-force login
Session hijacking
Unauthorized API access
Duplicate submissions
Race conditions
```

Especially test:

> Can Coordinator A access Coordinator B's event by changing the event ID in the URL?

Example:

```text
/events/10
```

to:

```text
/events/11
```

The answer must be **no** if Event 11 isn't assigned to them.

---

# PHASE 47 — Concurrency Testing

This is important for your realtime scoring.

Imagine:

```text
Coordinator submits result
        +
Another coordinator submits another result
        +
Admin opens tally
```

at exactly the same time.

Use:

```text
database transactions
atomic updates
unique constraints
proper locking where necessary
```

to prevent inconsistent tally data.

---

# PHASE 48 — Realtime Failure Handling

Don't assume realtime communication will always work.

If WebSocket/broadcasting fails:

```text
Database still contains the result
```

and the monitor should be able to recover through:

```text
polling / refresh / reconnect
```

The database must remain the source of truth.

---

# PHASE 49 — Backup

Set automatic database backups.

Recommended:

```text
Daily database backup
+
Before major event operations
```

Keep multiple backup generations.

Example:

```text
backup-2026-09-18
backup-2026-09-19
backup-2026-09-20
```

---

# PHASE 50 — Final System Flow

The final architecture should roughly look like:

```text
                         ┌──────────────┐
                         │    ADMIN     │
                         └──────┬───────┘
                                │
                         Laravel Web App
                                │
                  ┌─────────────┴─────────────┐
                  │                           │
            Admin Functions             Coordinator Functions
                  │                           │
                  └─────────────┬─────────────┘
                                │
                         Laravel API
                                │
                       Authentication
                       Authorization
                       Validation
                       Business Logic
                                │
                    ┌───────────┴───────────┐
                    │                       │
                 MySQL                    Redis
                    │                       │
                    │                  Queue/Cache
                    │                       │
                    └───────────┬───────────┘
                                │
                         Realtime Events
                                │
                         Live Monitor
```

# Recommended Laravel Folder Structure

Keep your code organized like this:

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/
│   │   └── Coordinator/
│   │
│   ├── Requests/
│   └── Middleware/
│
├── Models/
│
├── Policies/
│
├── Services/
│   ├── StudentService.php
│   ├── EventService.php
│   ├── ParticipationService.php
│   ├── CoordinatorService.php
│   ├── ResultService.php
│   └── TallyService.php
│
├── Jobs/
│
├── Events/
│
└── Notifications/
```

### Why `Services`?

Don't put 300 lines of business logic inside a controller.

Instead:

```text
Controller
    ↓
Service
    ↓
Model / Database
```

For example:

```text
ResultController
       ↓
ResultService
       ↓
Validate competition rules
       ↓
Save result
       ↓
Update tally
       ↓
Broadcast event
```

That makes the system much easier to maintain.

---

# 🚦 Development Order

This is the recommended implementation order:

```text
01. Requirements
02. ERD / database design
03. Laravel installation
04. Git repository
05. Authentication
06. User roles
07. Admin authorization
08. Student management
09. Team management
10. Sport management
11. Event management
12. Athlete/event assignment
13. Participation rules
14. Coordinator management
15. Coordinator assignments
16. Coordinator requests
17. Device/session restriction
18. Event lifecycle
19. Result submission
20. Disqualification/flags
21. Tally calculation
22. Admin dashboard
23. Coordinator dashboard
24. Live event monitor
25. Realtime updates
26. Score sheet generation
27. Reports
28. Audit logs
29. Queues
30. Redis/cache
31. Performance optimization
32. Automated tests
33. Security testing
34. Backup
35. Production deployment
36. Load balancing
37. Monitoring
```

# ⭐ Most Important Rule for the Junior Developer

Don't let an AI generate the entire application in one prompt.

Build it **module by module**.

For every module, use this cycle:

```text
REQUIREMENT
     ↓
DATABASE
     ↓
MODEL
     ↓
MIGRATION
     ↓
VALIDATION
     ↓
SERVICE / BUSINESS LOGIC
     ↓
CONTROLLER
     ↓
AUTHORIZATION
     ↓
ROUTES / API
     ↓
UI
     ↓
TEST
```

Then move to the next module.

This approach makes the project easier to understand, debug, test, and explain during project defense. It also prevents the common AI-generated-project problem where the screens look finished but the underlying permissions, database relationships, and business rules are broken.
