# Graph Report - IntramsProj  (2026-09-28)

## Corpus Check
- 314 files · ~94,906 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 21 file(s) not represented in the graph (top: (none) 16, .graphify-bak 1, .example 1)

## Summary
- 1239 nodes · 2138 edges · 227 communities (65 shown, 162 thin omitted)
- Extraction: 99% EXTRACTED · 1% INFERRED · 0% AMBIGUOUS · INFERRED: 20 edges (avg confidence: 0.94)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `c3b7f7b1`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Illuminate\View\Component
- IntramuralEdition
- Illuminate\Database\Migrations\Migration
- Illuminate\Support\Facades\Hash
- require
- EditionSport
- Controller
- package.json
- TODO_old.md
- ScoreSheetImageOfficeService.php
- User
- Illuminate\Database\Eloquent\Model
- What You Must Do When Invoked
- 3. Database schema
- ResultSubmission
- DeleteUser
- Illuminate\Http\Request
- Intramurals Management System
- Illuminate\Database\Schema\Blueprint
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
- Illuminate\Support\Facades\Schema
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
- SportService
- CoordinatorController
- Q: replace the basketball score sheet with this, but inheret the function autofill name based on the team, and lisence replaced with course.
- Livewire\Livewire
- Illuminate\Support\ServiceProvider
- StoreStudentRequest
- EventController.php
- StoreCoordinatorRequest
- FortifyServiceProvider.php
- StoreCoordinatorAssignmentRequest
- Event
- Q: swap the competition and team b, make this changes in both the preview and downloadable
- Student
- .reviewRequest
- admin.sports.partials.basketball-score-sheet
- Illuminate\Support\Str
- Illuminate\Foundation\Http\FormRequest
- admin.sports.partials.bracket-match
- CoordinatorService
- Authenticate.php
- Illuminate\Http\RedirectResponse
- TrustProxies.php
- AuditService
- Q: inheret the format from preview to the download
- Symfony\Component\HttpFoundation\Response
- UpdateUserProfileInformation.php
- ScoreSheetPdfService.php
- index.php
- StoreRegistrationRequest
- Illuminate\View\View
- CoordinatorRequest
- require-dev
- composer.json
- config
- StoreSportRequest
- UpdateSportRequest
- ScheduleParticipant
- psr-4
- scripts
- autoload-dev
- extra

## God Nodes (most connected - your core abstractions)
1. `User` - 80 edges
2. `IntramuralEdition` - 57 edges
3. `Sport` - 53 edges
4. `Student` - 44 edges
5. `Team` - 42 edges
6. `EditionSport` - 37 edges
7. `Controller` - 34 edges
8. `Event` - 33 edges
9. `SportController` - 31 edges
10. `BracketMatch` - 27 edges

## Surprising Connections (you probably didn't know these)
- `4. Application services and key functions` --references--> `AuditService`  [INFERRED]
  TODO.md → app/Services/AuditService.php
- `4. Application services and key functions` --references--> `CoordinatorService`  [INFERRED]
  TODO.md → app/Services/CoordinatorService.php
- `4. Application services and key functions` --references--> `EditionService`  [INFERRED]
  TODO.md → app/Services/EditionService.php
- `4. Application services and key functions` --references--> `EventService`  [INFERRED]
  TODO.md → app/Services/EventService.php
- `4. Application services and key functions` --references--> `ParticipationService`  [INFERRED]
  TODO.md → app/Services/ParticipationService.php

## Import Cycles
- None detected.

## Communities (227 total, 162 thin omitted)

### Community 0 - "Illuminate\View\Component"
Cohesion: 0.38
Nodes (3): AppLayout, GuestLayout, Illuminate\View\Component

### Community 1 - "IntramuralEdition"
Cohesion: 0.06
Nodes (17): EditionController, UpdateEditionRequest, CompetitionSchedule, IntramuralEdition, EditionService, PublicScheduleService, DatabaseSeeder, DefaultSportsSeeder (+9 more)

### Community 3 - "Illuminate\Support\Facades\Hash"
Cohesion: 0.15
Nodes (10): CreateNewUser, PasswordValidationRules, ResetUserPassword, UpdateUserPassword, Illuminate\Support\Facades\Hash, Illuminate\Validation\ValidationException, Laravel\Fortify\Contracts\CreatesNewUsers, Laravel\Fortify\Contracts\ResetsUserPasswords (+2 more)

### Community 4 - "require"
Cohesion: 0.20
Nodes (10): require, barryvdh/laravel-dompdf, guzzlehttp/guzzle, heroyt/tournament-generator, laravel/framework, laravel/jetstream, laravel/sanctum, laravel/tinker (+2 more)

### Community 5 - "EditionSport"
Cohesion: 0.07
Nodes (14): AthleteEntry, BracketCompetitor, BracketMatch, EditionSport, BracketService, Illuminate\Support\Carbon, Illuminate\Support\Collection, bracketEngineSport() (+6 more)

### Community 6 - "Controller"
Cohesion: 0.10
Nodes (11): CompetitionController, DashboardController, EditionSportController, ModuleController, SystemLogController, Controller, DashboardController, Illuminate\Foundation\Auth\Access\AuthorizesRequests (+3 more)

### Community 7 - "package.json"
Cohesion: 0.05
Nodes (31): dependencies, html-to-image, devDependencies, autoprefixer, axios, laravel-vite-plugin, postcss, tailwindcss (+23 more)

### Community 8 - "TODO_old.md"
Cohesion: 0.04
Nodes (44): 🚦 Development Order, ⭐ Most Important Rule for the Junior Developer, PHASE 11 — Coordinator Permissions, PHASE 12 — Event Status, PHASE 13 — Competition Results, PHASE 14 — Sport-Specific Scoring, PHASE 15 — Score Submission Workflow, PHASE 16 — Disqualification System (+36 more)

### Community 9 - "ScoreSheetImageOfficeService.php"
Cohesion: 0.40
Nodes (3): ScoreSheetImageOfficeService, RuntimeException, ZipArchive

### Community 10 - "User"
Cohesion: 0.15
Nodes (10): User, AdminUserSeeder, Illuminate\Database\Eloquent\Relations\HasMany, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable, Laravel\Fortify\TwoFactorAuthenticatable, Laravel\Jetstream\HasProfilePhoto, Laravel\Sanctum\HasApiTokens (+2 more)

### Community 11 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.13
Nodes (6): AuditLog, CompetitionResult, TeamFlag, TeamTally, Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\SoftDeletes

### Community 12 - "What You Must Do When Invoked"
Cohesion: 0.08
Nodes (24): For /graphify add and --watch, For /graphify query, For the commit hook and native CLAUDE.md integration, For --update and --cluster-only, /graphify, Honesty Rules, Interpreter guard for subcommands, Part A - Structural extraction for code files (+16 more)

### Community 13 - "3. Database schema"
Cohesion: 0.24
Nodes (7): Database schema, 3.1 Identity and reference data, 3.2 Registration, rules, and staffing, 3.3 Competition and scoring, 3.4 Compliance and history, 3.5 Required indexes and integrity rules, 3. Database schema

### Community 14 - "ResultSubmission"
Cohesion: 0.24
Nodes (3): Fixture, ResultSubmission, ResultService

### Community 16 - "Illuminate\Http\Request"
Cohesion: 0.22
Nodes (4): Collection, SportController, Sport, Illuminate\Http\Request

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
Cohesion: 0.18
Nodes (3): StoreEditionRequest, StoreTeamRequest, Illuminate\Validation\Rule

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

### Community 165 - "Q: replace the basketball score sheet with this, but inheret the function autofill name based on the team, and lisence replaced with course."
Cohesion: 0.40
Nodes (4): Answer, Outcome, Q: replace the basketball score sheet with this, but inheret the function autofill name based on the team, and lisence replaced with course., Source Nodes

### Community 166 - "Livewire\Livewire"
Cohesion: 0.15
Nodes (8): Laravel\Jetstream\Features, Laravel\Jetstream\Http\Livewire\ApiTokenManager, Laravel\Jetstream\Http\Livewire\DeleteUserForm, Laravel\Jetstream\Http\Livewire\LogoutOtherBrowserSessionsForm, Laravel\Jetstream\Http\Livewire\TwoFactorAuthenticationForm, Laravel\Jetstream\Http\Livewire\UpdateProfileInformationForm, Laravel\Jetstream\Http\Middleware\AuthenticateSession, Livewire\Livewire

### Community 167 - "Illuminate\Support\ServiceProvider"
Cohesion: 0.14
Nodes (7): AppServiceProvider, BroadcastServiceProvider, JetstreamServiceProvider, Illuminate\Support\Facades\Broadcast, Illuminate\Support\Facades\Facade, Illuminate\Support\ServiceProvider, Laravel\Jetstream\Jetstream

### Community 168 - "StoreStudentRequest"
Cohesion: 0.16
Nodes (3): SanitizesStudentInput, StoreStudentRequest, UpdateStudentRequest

### Community 170 - "StoreCoordinatorRequest"
Cohesion: 0.25
Nodes (3): StoreCoordinatorRequest, Illuminate\Contracts\Validation\Rule, Illuminate\Validation\Rules\Password

### Community 171 - "FortifyServiceProvider.php"
Cohesion: 0.15
Nodes (10): EnsureCoordinatorDevice, FortifyServiceProvider, CoordinatorDeviceService, Illuminate\Contracts\Auth\StatefulGuard, Laravel\Fortify\Actions\AttemptToAuthenticate, Laravel\Fortify\Actions\CanonicalizeUsername, Laravel\Fortify\Actions\EnsureLoginIsNotThrottled, Laravel\Fortify\Actions\PrepareAuthenticatedSession (+2 more)

### Community 173 - "Event"
Cohesion: 0.10
Nodes (5): UpdateEventRequest, Event, EventRegistration, ParticipationRule, ParticipationService

### Community 174 - "Q: swap the competition and team b, make this changes in both the preview and downloadable"
Cohesion: 0.40
Nodes (4): Answer, Outcome, Q: swap the competition and team b, make this changes in both the preview and downloadable, Source Nodes

### Community 176 - "Student"
Cohesion: 0.05
Nodes (16): TeamController, AssignTeamMemberRequest, RemoveTeamMembersRequest, UpdateTeamRequest, Student, Team, TeamMember, StudentService (+8 more)

### Community 185 - "Illuminate\Support\Str"
Cohesion: 0.24
Nodes (3): Illuminate\Support\Facades\Cookie, Illuminate\Support\Facades\DB, Illuminate\Support\Str

### Community 186 - "Illuminate\Foundation\Http\FormRequest"
Cohesion: 0.19
Nodes (4): StoreCoordinatorChangeRequest, StoreEventRequest, StoreParticipationRuleRequest, Illuminate\Foundation\Http\FormRequest

### Community 194 - "Illuminate\Http\RedirectResponse"
Cohesion: 0.23
Nodes (4): CourseController, StudentController, Course, Illuminate\Http\RedirectResponse

### Community 197 - "Q: inheret the format from preview to the download"
Cohesion: 0.40
Nodes (4): Answer, Outcome, Q: inheret the format from preview to the download, Source Nodes

### Community 198 - "Symfony\Component\HttpFoundation\Response"
Cohesion: 0.07
Nodes (21): EnsureUserHasRole, Response, RedirectIfAuthenticated, ReturnAdminSavesToModuleIndex, EventServiceProvider, RouteServiceProvider, Closure, Illuminate\Auth\Events\Registered (+13 more)

### Community 201 - "UpdateUserProfileInformation.php"
Cohesion: 0.38
Nodes (4): UpdateUserProfileInformation, Illuminate\Contracts\Auth\MustVerifyEmail, Illuminate\Support\Facades\Validator, Laravel\Fortify\Contracts\UpdatesUserProfileInformation

### Community 217 - "Illuminate\View\View"
Cohesion: 0.14
Nodes (3): RegistrationController, SportModuleController, Illuminate\View\View

### Community 218 - "CoordinatorRequest"
Cohesion: 0.13
Nodes (7): CoordinatorAssignment, CoordinatorDevice, CoordinatorRequest, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Support\Arr, coordinatorModuleAdmin(), coordinatorModuleEvent()

### Community 222 - "require-dev"
Cohesion: 0.22
Nodes (9): require-dev, fakerphp/faker, laravel/pint, laravel/sail, mockery/mockery, nunomaduro/collision, pestphp/pest, pestphp/pest-plugin-laravel (+1 more)

### Community 223 - "composer.json"
Cohesion: 0.25
Nodes (7): description, keywords, license, minimum-stability, name, prefer-stable, type

### Community 224 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 229 - "psr-4"
Cohesion: 0.40
Nodes (5): autoload, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 230 - "scripts"
Cohesion: 0.40
Nodes (5): scripts, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd

### Community 231 - "autoload-dev"
Cohesion: 0.67
Nodes (3): autoload-dev, psr-4, Tests\\

### Community 232 - "extra"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

## Knowledge Gaps
- **257 isolated node(s):** `name`, `type`, `description`, `keywords`, `license` (+252 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 597 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **162 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Work-memory lessons

**Preferred sources** — corroborated by past sessions; start here.
- `basketball-score-sheet-pdf.blade.php` (3× useful, score=2.999429679) _(code changed — re-verify)_

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `IntramuralEdition`, `Illuminate\Support\Facades\Hash`, `CoordinatorController`, `EditionSport`, `Controller`, `Livewire\Livewire`, `Symfony\Component\HttpFoundation\Response`, `UpdateUserProfileInformation.php`, `StoreCoordinatorRequest`, `FortifyServiceProvider.php`, `DeleteUser`, `Student`, `Illuminate\Support\Str`, `CoordinatorRequest`, `CoordinatorService`?**
  _High betweenness centrality (0.086) - this node is a cross-community bridge._
- **Why does `4. Application services and key functions` connect `Student` to `IntramuralEdition`, `AuditService`, `Event`, `ResultSubmission`, `Intramurals Management System — Master Implementation TODO`, `CoordinatorService`?**
  _High betweenness centrality (0.031) - this node is a cross-community bridge._
- **Why does `IntramuralEdition` connect `IntramuralEdition` to `Illuminate\Http\RedirectResponse`, `Illuminate\Support\Str`, `AuditService`, `EditionSport`, `Controller`, `EventController.php`, `User`, `Illuminate\Database\Eloquent\Model`, `3. Database schema`, `Illuminate\Http\Request`, `Student`, `Illuminate\Validation\Rule`, `Illuminate\View\View`?**
  _High betweenness centrality (0.030) - this node is a cross-community bridge._
- **What connects `name`, `type`, `description` to the rest of the system?**
  _257 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `IntramuralEdition` be split into smaller, more focused modules?**
  _Cohesion score 0.06285714285714286 - nodes in this community are weakly interconnected._
- **Should `EditionSport` be split into smaller, more focused modules?**
  _Cohesion score 0.073224043715847 - nodes in this community are weakly interconnected._
- **Should `Controller` be split into smaller, more focused modules?**
  _Cohesion score 0.09885057471264368 - nodes in this community are weakly interconnected._