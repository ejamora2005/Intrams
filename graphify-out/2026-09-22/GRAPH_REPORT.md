# Graph Report - IntramsProj  (2026-09-22)

## Corpus Check
- 299 files · ~74,843 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 21 file(s) not represented in the graph (top: (none) 16, .graphify-bak 1, .example 1)

## Summary
- 1177 nodes · 2027 edges · 209 communities (60 shown, 149 thin omitted)
- Extraction: 99% EXTRACTED · 1% INFERRED · 0% AMBIGUOUS · INFERRED: 19 edges (avg confidence: 0.95)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `2d21e3d0`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Illuminate\View\Component
- IntramuralEdition
- Illuminate\Database\Migrations\Migration
- StoreRegistrationRequest
- composer.json
- BracketService
- Illuminate\View\View
- package.json
- TODO_old.md
- .update
- User
- Illuminate\Database\Eloquent\Model
- What You Must Do When Invoked
- 3. Database schema
- CoordinatorService
- Illuminate\Database\Seeder
- Illuminate\Http\Request
- Intramurals Management System
- SportService
- 2. Functional requirements
- graphify reference: extra exports and benchmark
- Intramurals Management System — Master Implementation TODO
- TestCase
- 5. Business rules and workflows
- Illuminate\Validation\Rule
- 7. Delivery plan
- graphify reference: query, path, explain
- PHASE 45 — Testing
- api-token-manager.blade.php
- Kernel
- PHASE 35 — Security Checklist
- PHASE 30 — Reports
- Illuminate\Support\ServiceProvider
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
- CoordinatorController
- Event
- FortifyServiceProvider.php
- Livewire\Livewire
- Closure
- StoreStudentRequest
- TeamMember
- StoreCoordinatorRequest
- Team
- EditionSportController.php
- 4. Application services and key functions
- Student
- CoordinatorDeviceService
- RouteServiceProvider
- CoordinatorController.php
- Illuminate\Foundation\Http\FormRequest
- ResultSubmission
- Illuminate\Support\Str
- CreateNewUser.php
- Illuminate\Support\Facades\Hash
- UserFactory.php
- admin.sports.partials.bracket-match
- UpdateUserProfileInformation.php
- AssignTeamMemberRequest
- EventServiceProvider.php
- Authenticate.php
- EventService
- Illuminate\Http\RedirectResponse
- TrustProxies.php
- StoreSportRequest
- .update
- StoreEditionRequest
- EventRegistration
- CompetitionSchedule
- AuditService
- UpdateStudentRequest
- UpdateSportRequest
- TeamManagementTest.php
- StoreEventRequest
- StoreParticipationRuleRequest

## God Nodes (most connected - your core abstractions)
1. `User` - 78 edges
2. `IntramuralEdition` - 52 edges
3. `Sport` - 46 edges
4. `EditionSport` - 42 edges
5. `Student` - 42 edges
6. `Team` - 39 edges
7. `Controller` - 34 edges
8. `Event` - 33 edges
9. `SportController` - 26 edges
10. `BracketService` - 26 edges

## Surprising Connections (you probably didn't know these)
- `4. Application services and key functions` --references--> `AuditService`  [INFERRED]
  TODO.md → app/Services/AuditService.php
- `4. Application services and key functions` --references--> `CoordinatorService`  [INFERRED]
  TODO.md → app/Services/CoordinatorService.php
- `4. Application services and key functions` --references--> `EditionService`  [INFERRED]
  TODO.md → app/Services/EditionService.php
- `4. Application services and key functions` --references--> `EventService`  [INFERRED]
  TODO.md → app/Services/EventService.php
- `4. Application services and key functions` --references--> `ResultService`  [INFERRED]
  TODO.md → app/Services/ResultService.php

## Import Cycles
- None detected.

## Communities (209 total, 149 thin omitted)

### Community 0 - "Illuminate\View\Component"
Cohesion: 0.38
Nodes (3): AppLayout, GuestLayout, Illuminate\View\Component

### Community 1 - "IntramuralEdition"
Cohesion: 0.17
Nodes (4): EditionController, IntramuralEdition, EditionService, DefaultSportsSeeder

### Community 2 - "Illuminate\Database\Migrations\Migration"
Cohesion: 0.05
Nodes (3): Illuminate\Database\Migrations\Migration, Illuminate\Database\Schema\Blueprint, Illuminate\Support\Facades\Schema

### Community 4 - "composer.json"
Cohesion: 0.04
Nodes (48): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+40 more)

### Community 5 - "BracketService"
Cohesion: 0.09
Nodes (7): AthleteEntry, BracketCompetitor, BracketMatch, BracketService, Illuminate\Support\Collection, bracketEngineEdition(), finishReadyBracketMatches()

### Community 6 - "Illuminate\View\View"
Cohesion: 0.10
Nodes (13): CompetitionController, DashboardController, EventController, ModuleController, RegistrationController, SportModuleController, SystemLogController, Controller (+5 more)

### Community 7 - "package.json"
Cohesion: 0.08
Nodes (22): devDependencies, autoprefixer, axios, laravel-vite-plugin, postcss, tailwindcss, @tailwindcss/forms, @tailwindcss/typography (+14 more)

### Community 8 - "TODO_old.md"
Cohesion: 0.04
Nodes (44): 🚦 Development Order, ⭐ Most Important Rule for the Junior Developer, PHASE 11 — Coordinator Permissions, PHASE 12 — Event Status, PHASE 13 — Competition Results, PHASE 14 — Sport-Specific Scoring, PHASE 15 — Score Submission Workflow, PHASE 16 — Disqualification System (+36 more)

### Community 10 - "User"
Cohesion: 0.13
Nodes (13): User, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable, Laravel\Fortify\TwoFactorAuthenticatable, Laravel\Jetstream\HasProfilePhoto, Laravel\Sanctum\HasApiTokens, coordinatorModuleAdmin() (+5 more)

### Community 11 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.10
Nodes (7): AuditLog, CompetitionResult, ScheduleParticipant, TeamFlag, TeamTally, Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\SoftDeletes

### Community 12 - "What You Must Do When Invoked"
Cohesion: 0.08
Nodes (24): For /graphify add and --watch, For /graphify query, For the commit hook and native CLAUDE.md integration, For --update and --cluster-only, /graphify, Honesty Rules, Interpreter guard for subcommands, Part A - Structural extraction for code files (+16 more)

### Community 13 - "3. Database schema"
Cohesion: 0.24
Nodes (7): Database schema, 3.1 Identity and reference data, 3.2 Registration, rules, and staffing, 3.3 Competition and scoring, 3.4 Compliance and history, 3.5 Required indexes and integrity rules, 3. Database schema

### Community 15 - "Illuminate\Database\Seeder"
Cohesion: 0.38
Nodes (3): DatabaseSeeder, TestIntramuralsDataSeeder, Illuminate\Database\Seeder

### Community 16 - "Illuminate\Http\Request"
Cohesion: 0.14
Nodes (8): SportController, EditionSport, Sport, Barryvdh\DomPDF\Facade\Pdf, Illuminate\Contracts\Http\Kernel, Illuminate\Http\Request, bracketEngineSport(), bulkAssignmentSport()

### Community 17 - "Intramurals Management System"
Cohesion: 0.12
Nodes (16): Admin landing page and navigation, Athletes and participation rules, Competition operations, Current implementation state, Current next step, Development conventions, Intramurals edition management, Intramurals Management System (+8 more)

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

### Community 24 - "Illuminate\Validation\Rule"
Cohesion: 0.20
Nodes (3): StoreTeamRequest, UpdateEditionRequest, Illuminate\Validation\Rule

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

### Community 32 - "Illuminate\Support\ServiceProvider"
Cohesion: 0.11
Nodes (9): DeleteUser, AppServiceProvider, BroadcastServiceProvider, JetstreamServiceProvider, Illuminate\Support\Facades\Broadcast, Illuminate\Support\Facades\Facade, Illuminate\Support\ServiceProvider, Laravel\Jetstream\Contracts\DeletesUsers (+1 more)

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

### Community 164 - "Event"
Cohesion: 0.16
Nodes (6): CoordinatorAssignment, CoordinatorDevice, Event, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Support\Arr, coordinatorModuleEvent()

### Community 165 - "FortifyServiceProvider.php"
Cohesion: 0.22
Nodes (6): FortifyServiceProvider, Laravel\Fortify\Actions\AttemptToAuthenticate, Laravel\Fortify\Actions\CanonicalizeUsername, Laravel\Fortify\Actions\EnsureLoginIsNotThrottled, Laravel\Fortify\Actions\PrepareAuthenticatedSession, Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable

### Community 166 - "Livewire\Livewire"
Cohesion: 0.18
Nodes (7): Laravel\Jetstream\Features, Laravel\Jetstream\Http\Livewire\ApiTokenManager, Laravel\Jetstream\Http\Livewire\DeleteUserForm, Laravel\Jetstream\Http\Livewire\LogoutOtherBrowserSessionsForm, Laravel\Jetstream\Http\Livewire\UpdateProfileInformationForm, Laravel\Jetstream\Http\Middleware\AuthenticateSession, Livewire\Livewire

### Community 167 - "Closure"
Cohesion: 0.24
Nodes (7): EnsureUserHasRole, Response, RedirectIfAuthenticated, ReturnAdminSavesToModuleIndex, Closure, Illuminate\Support\Facades\Auth, Symfony\Component\HttpFoundation\Response

### Community 169 - "TeamMember"
Cohesion: 0.22
Nodes (3): TeamMember, bulkAssignmentAdmin(), bulkAssignmentEdition()

### Community 170 - "StoreCoordinatorRequest"
Cohesion: 0.18
Nodes (4): StoreCoordinatorRequest, UpdateCoordinatorRequest, Illuminate\Contracts\Validation\Rule, Illuminate\Validation\Rules\Password

### Community 173 - "4. Application services and key functions"
Cohesion: 0.25
Nodes (3): ParticipationRule, ParticipationService, 4. Application services and key functions

### Community 175 - "CoordinatorDeviceService"
Cohesion: 0.23
Nodes (5): EnsureCoordinatorDevice, CoordinatorDeviceService, Illuminate\Contracts\Auth\StatefulGuard, Illuminate\Validation\ValidationException, Laravel\Fortify\Fortify

### Community 176 - "RouteServiceProvider"
Cohesion: 0.11
Nodes (11): RouteServiceProvider, Illuminate\Auth\Events\Verified, Illuminate\Auth\Notifications\ResetPassword, Illuminate\Cache\RateLimiting\Limit, Illuminate\Foundation\Support\Providers\RouteServiceProvider, Illuminate\Support\Facades\Notification, Illuminate\Support\Facades\RateLimiter, Illuminate\Support\Facades\Route (+3 more)

### Community 179 - "Illuminate\Foundation\Http\FormRequest"
Cohesion: 0.19
Nodes (4): RemoveTeamMembersRequest, StoreCoordinatorAssignmentRequest, StoreCoordinatorChangeRequest, Illuminate\Foundation\Http\FormRequest

### Community 182 - "ResultSubmission"
Cohesion: 0.24
Nodes (3): Fixture, ResultSubmission, ResultService

### Community 183 - "Illuminate\Support\Str"
Cohesion: 0.25
Nodes (3): Illuminate\Support\Facades\Cookie, Illuminate\Support\Facades\DB, Illuminate\Support\Str

### Community 184 - "CreateNewUser.php"
Cohesion: 0.24
Nodes (5): CreateNewUser, PasswordValidationRules, ResetUserPassword, Laravel\Fortify\Contracts\CreatesNewUsers, Laravel\Fortify\Contracts\ResetsUserPasswords

### Community 185 - "Illuminate\Support\Facades\Hash"
Cohesion: 0.22
Nodes (5): UpdateUserPassword, AdminUserSeeder, Illuminate\Support\Facades\Hash, Laravel\Fortify\Contracts\UpdatesUserPasswords, Laravel\Jetstream\Http\Livewire\UpdatePasswordForm

### Community 186 - "UserFactory.php"
Cohesion: 0.27
Nodes (4): StudentFactory, UserFactory, Illuminate\Database\Eloquent\Factories\Factory, static

### Community 188 - "UpdateUserProfileInformation.php"
Cohesion: 0.38
Nodes (4): UpdateUserProfileInformation, Illuminate\Contracts\Auth\MustVerifyEmail, Illuminate\Support\Facades\Validator, Laravel\Fortify\Contracts\UpdatesUserProfileInformation

### Community 190 - "EventServiceProvider.php"
Cohesion: 0.29
Nodes (5): EventServiceProvider, Illuminate\Auth\Events\Registered, Illuminate\Auth\Listeners\SendEmailVerificationNotification, Illuminate\Foundation\Support\Providers\EventServiceProvider, Illuminate\Support\Facades\Event

### Community 194 - "Illuminate\Http\RedirectResponse"
Cohesion: 0.14
Nodes (5): CourseController, StudentController, TeamController, Course, Illuminate\Http\RedirectResponse

### Community 205 - "TeamManagementTest.php"
Cohesion: 0.40
Nodes (4): teamModuleAdmin(), teamModuleEdition(), teamModuleStudent(), validTeamData()

## Knowledge Gaps
- **240 isolated node(s):** `name`, `type`, `description`, `keywords`, `license` (+235 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 563 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **149 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `IntramuralEdition`, `BracketService`, `Illuminate\View\View`, `CoordinatorService`, `Illuminate\Http\Request`, `Illuminate\Support\ServiceProvider`, `CoordinatorController`, `Event`, `FortifyServiceProvider.php`, `Livewire\Livewire`, `TeamMember`, `StoreCoordinatorRequest`, `CoordinatorDeviceService`, `RouteServiceProvider`, `CoordinatorController.php`, `Illuminate\Support\Str`, `CreateNewUser.php`, `Illuminate\Support\Facades\Hash`, `UserFactory.php`, `UpdateUserProfileInformation.php`, `TeamManagementTest.php`?**
  _High betweenness centrality (0.066) - this node is a cross-community bridge._
- **Why does `IntramuralEdition` connect `IntramuralEdition` to `EventService`, `Illuminate\Http\RedirectResponse`, `BracketService`, `Illuminate\View\View`, `TeamMember`, `User`, `Illuminate\Database\Eloquent\Model`, `EditionSportController.php`, `3. Database schema`, `TeamManagementTest.php`, `Illuminate\Database\Seeder`, `Illuminate\Http\Request`, `Illuminate\Support\Str`, `Illuminate\Validation\Rule`?**
  _High betweenness centrality (0.056) - this node is a cross-community bridge._
- **Why does `Event` connect `Event` to `EventService`, `CoordinatorController`, `StoreRegistrationRequest`, `Illuminate\View\View`, `.update`, `Illuminate\Database\Eloquent\Model`, `3. Database schema`, `Student`, `CoordinatorService`, `4. Application services and key functions`, `CoordinatorController.php`, `Illuminate\Support\Str`?**
  _High betweenness centrality (0.032) - this node is a cross-community bridge._
- **What connects `name`, `type`, `description` to the rest of the system?**
  _240 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Illuminate\Database\Migrations\Migration` be split into smaller, more focused modules?**
  _Cohesion score 0.051203277009728626 - nodes in this community are weakly interconnected._
- **Should `composer.json` be split into smaller, more focused modules?**
  _Cohesion score 0.04081632653061224 - nodes in this community are weakly interconnected._
- **Should `BracketService` be split into smaller, more focused modules?**
  _Cohesion score 0.09393939393939393 - nodes in this community are weakly interconnected._