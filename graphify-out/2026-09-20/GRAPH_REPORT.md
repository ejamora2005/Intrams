# Graph Report - IntramsProj  (2026-09-20)

## Corpus Check
- 283 files · ~55,736 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 21 file(s) not represented in the graph (top: (none) 16, .graphify-bak 1, .example 1)

## Summary
- 1093 nodes · 1752 edges · 171 communities (50 shown, 121 thin omitted)
- Extraction: 99% EXTRACTED · 1% INFERRED · 0% AMBIGUOUS · INFERRED: 21 edges (avg confidence: 0.95)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `2d21e3d0`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Illuminate\Http\RedirectResponse
- Illuminate\Database\Eloquent\Model
- Illuminate\Database\Migrations\Migration
- EventService
- composer.json
- Illuminate\View\View
- FortifyServiceProvider.php
- package.json
- TODO_old.md
- Illuminate\Support\ServiceProvider
- User
- Illuminate\Database\Seeder
- What You Must Do When Invoked
- .sports
- Student
- Illuminate\Http\Request
- Team
- Intramurals Management System
- 2. Functional requirements
- graphify reference: extra exports and benchmark
- Intramurals Management System — Master Implementation TODO
- TestCase
- 5. Business rules and workflows
- Illuminate\View\Component
- 7. Delivery plan
- graphify reference: query, path, explain
- PHASE 45 — Testing
- api-token-manager.blade.php
- Kernel
- PHASE 35 — Security Checklist
- PHASE 30 — Reports
- DeleteUser
- Handler.php
- graphify reference: add a URL and watch a folder
- graphify reference: commit hook and native CLAUDE.md integration
- logging.php
- graphify reference: incremental update and cluster-only
- PHASE 1 — Understand and Freeze the Requirements
- TrustHosts
- AuthServiceProvider
- logout-other-browser-sessions-form.blade.php
- delete-user-form.blade.php
- Http/Kernel.php
- EncryptCookies.php
- PreventRequestsDuringMaintenance.php
- TrimStrings.php
- ValidateSignature.php
- VerifyCsrfToken.php
- confirms-password.blade.php
- console.php
- sanctum.php
- deleteProfilePhoto
- coordinators/create.blade.php
- coordinators/edit.blade.php
- editions/create.blade.php
- editions/edit.blade.php
- events/create.blade.php
- events/edit.blade.php
- sports/create.blade.php
- sports/edit.blade.php
- students/create.blade.php
- students/edit.blade.php
- teams/create.blade.php
- teams/edit.blade.php
- graphify reference: GitHub clone and cross-repo merge
- graphify reference: transcribe video and audio
- Universal staff login
- 0. Recommended Technology Stack
- PHASE 3 — Sports and Events
- PHASE 2 — Database Design
- AGENTS.md
- extraction-spec.md
- policy.md
- terms.md
- PHASE 10 — One Phone / One Active Device
- PHASE 6 — Athlete Participation Rules
- Recommended Laravel Folder Structure
- Livewire\Livewire
- Illuminate\Support\Facades\Hash
- Illuminate\Foundation\Http\FormRequest
- RegistrationController.php
- UpdateUserProfileInformation.php
- IntramuralEdition
- Sport

## God Nodes (most connected - your core abstractions)
1. `User` - 72 edges
2. `IntramuralEdition` - 38 edges
3. `Student` - 36 edges
4. `Controller` - 34 edges
5. `Team` - 34 edges
6. `Event` - 33 edges
7. `Sport` - 30 edges
8. `AuditService` - 21 edges
9. `CoordinatorService` - 19 edges
10. `Intramurals Management System` - 17 edges

## Surprising Connections (you probably didn't know these)
- `4. Application services and key functions` --references--> `CoordinatorService`  [INFERRED]
  TODO.md → app/Services/CoordinatorService.php
- `4. Application services and key functions` --references--> `EditionService`  [INFERRED]
  TODO.md → app/Services/EditionService.php
- `4. Application services and key functions` --references--> `EventService`  [INFERRED]
  TODO.md → app/Services/EventService.php
- `4. Application services and key functions` --references--> `ParticipationService`  [INFERRED]
  TODO.md → app/Services/ParticipationService.php
- `4. Application services and key functions` --references--> `StudentService`  [INFERRED]
  TODO.md → app/Services/StudentService.php

## Import Cycles
- None detected.

## Communities (171 total, 121 thin omitted)

### Community 0 - "Illuminate\Http\RedirectResponse"
Cohesion: 0.08
Nodes (11): CoordinatorController, DashboardController, CoordinatorAssignment, CoordinatorRequest, Event, CoordinatorService, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Http\RedirectResponse (+3 more)

### Community 1 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.05
Nodes (14): AthleteEntry, CompetitionResult, CompetitionSchedule, EditionSport, Fixture, ResultSubmission, ScheduleParticipant, TeamFlag (+6 more)

### Community 2 - "Illuminate\Database\Migrations\Migration"
Cohesion: 0.06
Nodes (3): Illuminate\Database\Migrations\Migration, Illuminate\Database\Schema\Blueprint, Illuminate\Support\Facades\Schema

### Community 3 - "EventService"
Cohesion: 0.13
Nodes (4): EventController, StoreEventRequest, UpdateEventRequest, EventService

### Community 4 - "composer.json"
Cohesion: 0.04
Nodes (47): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+39 more)

### Community 5 - "Illuminate\View\View"
Cohesion: 0.11
Nodes (13): CompetitionController, DashboardController, EditionSportController, ModuleController, SportModuleController, SystemLogController, Controller, AuditLog (+5 more)

### Community 6 - "FortifyServiceProvider.php"
Cohesion: 0.20
Nodes (7): FortifyServiceProvider, Laravel\Fortify\Actions\AttemptToAuthenticate, Laravel\Fortify\Actions\CanonicalizeUsername, Laravel\Fortify\Actions\EnsureLoginIsNotThrottled, Laravel\Fortify\Actions\PrepareAuthenticatedSession, Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable, Laravel\Fortify\Fortify

### Community 7 - "package.json"
Cohesion: 0.08
Nodes (22): devDependencies, autoprefixer, axios, laravel-vite-plugin, postcss, tailwindcss, @tailwindcss/forms, @tailwindcss/typography (+14 more)

### Community 8 - "TODO_old.md"
Cohesion: 0.04
Nodes (44): 🚦 Development Order, ⭐ Most Important Rule for the Junior Developer, PHASE 11 — Coordinator Permissions, PHASE 12 — Event Status, PHASE 13 — Competition Results, PHASE 14 — Sport-Specific Scoring, PHASE 15 — Score Submission Workflow, PHASE 16 — Disqualification System (+36 more)

### Community 9 - "Illuminate\Support\ServiceProvider"
Cohesion: 0.06
Nodes (21): AppServiceProvider, BroadcastServiceProvider, EventServiceProvider, JetstreamServiceProvider, RouteServiceProvider, Illuminate\Auth\Events\Registered, Illuminate\Auth\Events\Verified, Illuminate\Auth\Listeners\SendEmailVerificationNotification (+13 more)

### Community 10 - "User"
Cohesion: 0.18
Nodes (9): User, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable, Laravel\Fortify\TwoFactorAuthenticatable, Laravel\Jetstream\HasProfilePhoto, Laravel\Sanctum\HasApiTokens, editionModuleAdmin() (+1 more)

### Community 11 - "Illuminate\Database\Seeder"
Cohesion: 0.27
Nodes (4): AdminUserSeeder, DatabaseSeeder, DefaultSportsSeeder, Illuminate\Database\Seeder

### Community 12 - "What You Must Do When Invoked"
Cohesion: 0.08
Nodes (24): For /graphify add and --watch, For /graphify query, For the commit hook and native CLAUDE.md integration, For --update and --cluster-only, /graphify, Honesty Rules, Interpreter guard for subcommands, Part A - Structural extraction for code files (+16 more)

### Community 13 - ".sports"
Cohesion: 0.24
Nodes (7): Database schema, 3.1 Identity and reference data, 3.2 Registration, rules, and staffing, 3.3 Competition and scoring, 3.4 Compliance and history, 3.5 Required indexes and integrity rules, 3. Database schema

### Community 14 - "Student"
Cohesion: 0.07
Nodes (10): CourseController, StudentController, SanitizesStudentInput, StoreStudentRequest, UpdateStudentRequest, Course, Student, StudentService (+2 more)

### Community 15 - "Illuminate\Http\Request"
Cohesion: 0.10
Nodes (18): EnsureCoordinatorDevice, Authenticate, EnsureUserHasRole, Response, RedirectIfAuthenticated, ReturnAdminSavesToModuleIndex, TrustProxies, CoordinatorDevice (+10 more)

### Community 16 - "Team"
Cohesion: 0.06
Nodes (11): TeamController, AssignTeamMemberRequest, StoreTeamRequest, UpdateTeamRequest, Team, TeamMember, TeamService, StudentFactory (+3 more)

### Community 17 - "Intramurals Management System"
Cohesion: 0.13
Nodes (15): Admin landing page and navigation, Athletes and participation rules, Competition operations, Current implementation state, Current next step, Development conventions, Intramurals edition management, Intramurals Management System (+7 more)

### Community 19 - "2. Functional requirements"
Cohesion: 0.14
Nodes (14): 2. Functional requirements, FR-01 Authentication and access control, FR-02 Admin master data, FR-03 Registrations and participation limits, FR-04 Coordinator management and device policy, FR-05 Event lifecycle and scheduling, FR-06 Results, scoring, and tally, FR-07 Flags and disqualification (+6 more)

### Community 20 - "graphify reference: extra exports and benchmark"
Cohesion: 0.22
Nodes (8): graphify reference: extra exports and benchmark, Step 6b - Wiki (only if --wiki flag), Step 7 - Neo4j export (only if --neo4j or --neo4j-push flag), Step 7a - FalkorDB export (only if --falkordb or --falkordb-push flag), Step 7b - SVG export (only if --svg flag), Step 7c - GraphML export (only if --graphml flag), Step 7d - MCP server (only if --mcp flag), Step 8 - Token reduction benchmark (only if total_words > 5000)

### Community 21 - "Intramurals Management System — Master Implementation TODO"
Cohesion: 0.20
Nodes (8): 1.1 Roles, 1.2 Definitions, 1. Purpose and scope, 6. Routes and screens, 8. Definition of done, Clean-reset direction — approved, Current implementation status (2026-09-20), Intramurals Management System — Master Implementation TODO

### Community 22 - "TestCase"
Cohesion: 0.22
Nodes (6): Illuminate\Contracts\Console\Kernel, Illuminate\Foundation\Application, Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Foundation\Testing\TestCase, CreatesApplication, TestCase

### Community 23 - "5. Business rules and workflows"
Cohesion: 0.22
Nodes (9): 5. Business rules and workflows, BR-01 Registration, BR-02 Coordinator authorization, BR-02A Team roster assignment, BR-03 Event state transitions, BR-04 Result approval and tally update, BR-05 Flag/disqualification, BR-06 Device restriction (+1 more)

### Community 24 - "Illuminate\View\Component"
Cohesion: 0.38
Nodes (3): AppLayout, GuestLayout, Illuminate\View\Component

### Community 25 - "7. Delivery plan"
Cohesion: 0.29
Nodes (7): 7. Delivery plan, Milestone 0 — Foundation, Milestone 1 — Master data and registration, Milestone 2 — Coordination and scheduling, Milestone 3 — Competition operations, Milestone 4 — Visibility and reporting, Milestone 5 — Quality and deployment

### Community 26 - "graphify reference: query, path, explain"
Cohesion: 0.33
Nodes (5): For /graphify explain, For /graphify path, graphify reference: query, path, explain, Step 0 — Constrained query expansion (REQUIRED before traversal), Step 1 — Traversal

### Community 27 - "PHASE 45 — Testing"
Cohesion: 0.33
Nodes (6): Coordinator, Disqualification, Participation, PHASE 45 — Testing, Results, Student

### Community 28 - "api-token-manager.blade.php"
Cohesion: 0.29
Nodes (6): confirmApiTokenDeletion({{ $token->id }}), deleteApiToken, manageApiTokenPermissions({{ $token->id }}), $toggle(, $set(, updateApiToken

### Community 29 - "Kernel"
Cohesion: 0.47
Nodes (3): Kernel, Illuminate\Console\Scheduling\Schedule, Illuminate\Foundation\Console\Kernel

### Community 30 - "PHASE 35 — Security Checklist"
Cohesion: 0.40
Nodes (5): API, Authentication, Authorization, Input security, PHASE 35 — Security Checklist

### Community 31 - "PHASE 30 — Reports"
Cohesion: 0.40
Nodes (5): Athlete participation report, Coordinator report, Event report, Overall tally, PHASE 30 — Reports

### Community 33 - "Handler.php"
Cohesion: 0.50
Nodes (3): Handler, Illuminate\Foundation\Exceptions\Handler, Throwable

### Community 34 - "graphify reference: add a URL and watch a folder"
Cohesion: 0.50
Nodes (3): For /graphify add, For --watch, graphify reference: add a URL and watch a folder

### Community 35 - "graphify reference: commit hook and native CLAUDE.md integration"
Cohesion: 0.50
Nodes (3): For git commit hook, For native CLAUDE.md integration, graphify reference: commit hook and native CLAUDE.md integration

### Community 36 - "logging.php"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 37 - "graphify reference: incremental update and cluster-only"
Cohesion: 0.50
Nodes (3): For --cluster-only, For --update (incremental re-extraction), graphify reference: incremental update and cluster-only

### Community 38 - "PHASE 1 — Understand and Freeze the Requirements"
Cohesion: 0.50
Nodes (4): PHASE 1 — Understand and Freeze the Requirements, TODO 1.1 — Define the main actors, TODO 1.2 — Define the system objects, TODO 1.3 — Define relationships

### Community 41 - "logout-other-browser-sessions-form.blade.php"
Cohesion: 0.50
Nodes (3): confirmLogout, logoutOtherBrowserSessions, $toggle(

### Community 42 - "delete-user-form.blade.php"
Cohesion: 0.50
Nodes (3): confirmUserDeletion, deleteUser, $toggle(

### Community 152 - "Universal staff login"
Cohesion: 0.67
Nodes (3): Coordinator trusted device policy, Local test administrator, Universal staff login

### Community 153 - "0. Recommended Technology Stack"
Cohesion: 0.67
Nodes (3): 0. Recommended Technology Stack, Important architecture decision, Intramurals Management System — Master TODO

### Community 154 - "PHASE 3 — Sports and Events"
Cohesion: 0.67
Nodes (3): Events, PHASE 3 — Sports and Events, Sports

### Community 155 - "PHASE 2 — Database Design"
Cohesion: 0.67
Nodes (3): PHASE 2 — Database Design, TODO 2.1 — Create users table, TODO 2.2 — Students/Athletes

### Community 164 - "Livewire\Livewire"
Cohesion: 0.15
Nodes (8): Laravel\Jetstream\Features, Laravel\Jetstream\Http\Livewire\ApiTokenManager, Laravel\Jetstream\Http\Livewire\DeleteUserForm, Laravel\Jetstream\Http\Livewire\LogoutOtherBrowserSessionsForm, Laravel\Jetstream\Http\Livewire\TwoFactorAuthenticationForm, Laravel\Jetstream\Http\Livewire\UpdateProfileInformationForm, Laravel\Jetstream\Http\Middleware\AuthenticateSession, Livewire\Livewire

### Community 165 - "Illuminate\Support\Facades\Hash"
Cohesion: 0.15
Nodes (11): CreateNewUser, PasswordValidationRules, ResetUserPassword, UpdateUserPassword, Illuminate\Support\Facades\Hash, Illuminate\Support\Facades\Validator, Illuminate\Validation\ValidationException, Laravel\Fortify\Contracts\CreatesNewUsers (+3 more)

### Community 167 - "Illuminate\Foundation\Http\FormRequest"
Cohesion: 0.06
Nodes (12): ReviewCoordinatorRequest, StoreCoordinatorAssignmentRequest, StoreCoordinatorChangeRequest, StoreCoordinatorRequest, StoreEditionRequest, StoreSportRequest, UpdateCoordinatorRequest, UpdateSportRequest (+4 more)

### Community 170 - "RegistrationController.php"
Cohesion: 0.08
Nodes (7): RegistrationController, StoreParticipationRuleRequest, StoreRegistrationRequest, EventRegistration, ParticipationRule, ParticipationService, Illuminate\Support\Collection

### Community 171 - "UpdateUserProfileInformation.php"
Cohesion: 0.47
Nodes (3): UpdateUserProfileInformation, Illuminate\Contracts\Auth\MustVerifyEmail, Laravel\Fortify\Contracts\UpdatesUserProfileInformation

### Community 172 - "IntramuralEdition"
Cohesion: 0.11
Nodes (10): EditionController, UpdateEditionRequest, IntramuralEdition, EditionService, sportsAdmin(), sportsEdition(), teamModuleAdmin(), teamModuleEdition() (+2 more)

### Community 176 - "Sport"
Cohesion: 0.18
Nodes (3): SportController, Sport, SportService

## Knowledge Gaps
- **237 isolated node(s):** `name`, `type`, `description`, `keywords`, `license` (+232 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 536 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **121 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `DeleteUser`, `Illuminate\Http\RedirectResponse`, `Livewire\Livewire`, `Illuminate\Support\Facades\Hash`, `Illuminate\View\View`, `Illuminate\Foundation\Http\FormRequest`, `FortifyServiceProvider.php`, `Illuminate\Support\ServiceProvider`, `UpdateUserProfileInformation.php`, `Illuminate\Database\Seeder`, `IntramuralEdition`, `Student`, `Illuminate\Http\Request`, `Team`?**
  _High betweenness centrality (0.059) - this node is a cross-community bridge._
- **Why does `Intramurals Management System — Master Implementation TODO` connect `Intramurals Management System — Master Implementation TODO` to `Illuminate\Database\Eloquent\Model`, `.sports`, `2. Functional requirements`, `5. Business rules and workflows`, `7. Delivery plan`?**
  _High betweenness centrality (0.040) - this node is a cross-community bridge._
- **Why does `4. Application services and key functions` connect `Illuminate\Database\Eloquent\Model` to `Illuminate\Http\RedirectResponse`, `EventService`, `RegistrationController.php`, `IntramuralEdition`, `Student`, `Team`, `Intramurals Management System — Master Implementation TODO`?**
  _High betweenness centrality (0.040) - this node is a cross-community bridge._
- **What connects `name`, `type`, `description` to the rest of the system?**
  _237 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Illuminate\Http\RedirectResponse` be split into smaller, more focused modules?**
  _Cohesion score 0.08333333333333333 - nodes in this community are weakly interconnected._
- **Should `Illuminate\Database\Eloquent\Model` be split into smaller, more focused modules?**
  _Cohesion score 0.0512987012987013 - nodes in this community are weakly interconnected._
- **Should `Illuminate\Database\Migrations\Migration` be split into smaller, more focused modules?**
  _Cohesion score 0.05639097744360902 - nodes in this community are weakly interconnected._