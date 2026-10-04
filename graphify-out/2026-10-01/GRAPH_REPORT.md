# Graph Report - Intrams  (2026-10-01)

## Corpus Check
- 319 files · ~184,150 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 27 file(s) not represented in the graph (top: (none) 16, .woff2 5, .css 2)

## Summary
- 1606 nodes · 2820 edges · 246 communities (79 shown, 167 thin omitted)
- Extraction: 99% EXTRACTED · 1% INFERRED · 0% AMBIGUOUS · INFERRED: 21 edges (avg confidence: 0.94)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `20aae5a6`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- Illuminate\View\View
- IntramuralEdition
- Illuminate\Support\Facades\Schema
- Illuminate\Support\Facades\Hash
- require
- EditionSport
- Controller
- package.json
- TODO_old.md
- SportController.php
- User
- Illuminate\Database\Eloquent\Model
- What You Must Do When Invoked
- 3. Database schema
- Illuminate\Support\Str
- TwoFactorAuthenticationSettingsTest.php
- Illuminate\Http\Request
- Intramurals Management System
- 2026_09_20_160000_reset_competition_structure_for_edition_centered_flow.php
- 2. Functional requirements
- graphify reference: extra exports and benchmark
- Intramurals Management System — Master Implementation TODO
- Pest.php
- 5. Business rules and workflows
- Illuminate\Validation\Rule
- 7. Delivery plan
- graphify reference: query, path, explain
- PHASE 45 — Testing
- api-token-manager.blade.php
- Kernel
- PHASE 35 — Security Checklist
- PHASE 30 — Reports
- Illuminate\Database\Migrations\Migration
- Handler.php
- graphify reference: add a URL and watch a folder
- graphify reference: commit hook and native CLAUDE.md integration
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
- deleteProfilePhoto
- coordinators/create.blade.php
- coordinators/edit.blade.php
- editions/create.blade.php
- editions/edit.blade.php
- events/create.blade.php
- events/edit.blade.php
- admin/sports/create.blade.php
- sports/edit.blade.php
- students/create.blade.php
- students/edit.blade.php
- teams/create.blade.php
- teams/edit.blade.php
- competition/index.blade.php
- coordinators/index.blade.php
- courses/index.blade.php
- admin/dashboard.blade.php
- editions/index.blade.php
- events/index.blade.php
- module.blade.php
- registrations/create.blade.php
- registrations/index.blade.php
- rules.blade.php
- sport-modules/index.blade.php
- sport-modules/show.blade.php
- events.blade.php
- sports/index.blade.php
- students/index.blade.php
- system-logs/index.blade.php
- teams/index.blade.php
- layouts.coordinator
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
- AthleteEntry
- AuditService
- Q: swap the competition and team b, make this changes in both the preview and downloadable
- 2014_10_12_200000_add_two_factor_columns_to_users_table.php
- Team
- assign-participants.blade.php
- CoordinatorController.php
- BracketMatch
- sports/basketball-score-sheet.blade.php
- participants.blade.php
- TeamController.php
- BracketService.php
- CompetitionSchedule
- Illuminate\Foundation\Http\FormRequest
- bracket.blade.php
- CoordinatorService
- PasswordResetTest.php
- Illuminate\Database\Schema\Blueprint
- TeamManagementTest.php
- editions/sports/create.blade.php
- BracketCompetitor
- StudentController.php
- TrustProxies.php
- Event
- Q: inheret the format from preview to the download
- Symfony\Component\HttpFoundation\Response
- 2026_09_19_090000_add_activity_context_to_audit_logs_table.php
- BracketEngineTest.php
- 2026_09_20_100000_add_source_event_to_coordinator_requests_table.php
- 2026_09_20_120000_add_school_year_to_students_table.php
- ScoreSheetPdfService.php
- 2026_09_20_130000_create_courses_and_add_course_to_students_table.php
- 2026_09_20_140000_add_is_system_to_sports_table.php
- volleyball-score-sheet.blade.php
- 2026_09_20_150000_create_edition_sports_table.php
- 2026_09_20_170000_add_course_to_teams.php
- 2026_09_21_170000_add_universal_competitors_to_brackets.php
- 2026_09_28_000000_link_competition_schedules_to_bracket_matches.php
- UserFactory
- Student
- Illuminate\Http\RedirectResponse
- CoordinatorManagementTest.php
- EventServiceProvider.php
- StudentManagementTest.php
- EmailVerificationTest.php
- require-dev
- composer.json
- config
- 2026_09_18_160000_add_intramurals_access_fields_to_users_table.php
- StoreSportRequest
- CoordinatorDevice
- .settleAutomaticMatches
- psr-4
- scripts
- autoload-dev
- extra
- VolleyballScoreSheetTest.php
- UpdateSportRequest
- UpdateEventRequest
- 2019_12_14_000001_create_personal_access_tokens_table.php
- landing.js
- CoordinatorRequest
- StoreParticipationRuleRequest
- landing-objects/README.md
- Authenticate.php
- Illuminate\Support\Facades\Route

## God Nodes (most connected - your core abstractions)
1. `User` - 119 edges
2. `IntramuralEdition` - 69 edges
3. `Student` - 66 edges
4. `Sport` - 62 edges
5. `Team` - 62 edges
6. `EditionSport` - 44 edges
7. `BracketMatch` - 35 edges
8. `Controller` - 34 edges
9. `Event` - 34 edges
10. `SportController` - 31 edges

## Surprising Connections (you probably didn't know these)
- `4. Application services and key functions` --references--> `CoordinatorService`  [INFERRED]
  TODO.md → app/Services/CoordinatorService.php
- `4. Application services and key functions` --references--> `EditionService`  [INFERRED]
  TODO.md → app/Services/EditionService.php
- `4. Application services and key functions` --references--> `AuditService`  [INFERRED]
  TODO.md → app/Services/AuditService.php
- `4. Application services and key functions` --references--> `EventService`  [INFERRED]
  TODO.md → app/Services/EventService.php
- `4. Application services and key functions` --references--> `ParticipationService`  [INFERRED]
  TODO.md → app/Services/ParticipationService.php

## Import Cycles
- None detected.

## Communities (246 total, 167 thin omitted)

### Community 0 - "Illuminate\View\View"
Cohesion: 0.17
Nodes (3): SportModuleController, AppLayout, GuestLayout

### Community 1 - "IntramuralEdition"
Cohesion: 0.06
Nodes (26): EditionController, UpdateEditionRequest, IntramuralEdition, {closure#1}(), {closure#2}(), {closure#3}(), EditionService, PublicScheduleService (+18 more)

### Community 2 - "Illuminate\Support\Facades\Schema"
Cohesion: 0.15
Nodes (3): {closure#1}(), {closure#1}(), {closure#1}()

### Community 3 - "Illuminate\Support\Facades\Hash"
Cohesion: 0.11
Nodes (6): CreateNewUser, PasswordValidationRules, ResetUserPassword, UpdateUserPassword, UpdateUserProfileInformation, AdminUserSeeder

### Community 4 - "require"
Cohesion: 0.20
Nodes (10): require, barryvdh/laravel-dompdf, guzzlehttp/guzzle, heroyt/tournament-generator, laravel/framework, laravel/jetstream, laravel/sanctum, laravel/tinker (+2 more)

### Community 5 - "EditionSport"
Cohesion: 0.20
Nodes (4): EditionSport, BracketService, {closure#2}(), {closure#3}()

### Community 6 - "Controller"
Cohesion: 0.09
Nodes (7): CompetitionController, CourseController, DashboardController, ModuleController, SystemLogController, Controller, DashboardController

### Community 7 - "package.json"
Cohesion: 0.05
Nodes (33): dependencies, html-to-image, devDependencies, autoprefixer, axios, laravel-vite-plugin, postcss, tailwindcss (+25 more)

### Community 8 - "TODO_old.md"
Cohesion: 0.04
Nodes (44): 🚦 Development Order, ⭐ Most Important Rule for the Junior Developer, PHASE 11 — Coordinator Permissions, PHASE 12 — Event Status, PHASE 13 — Competition Results, PHASE 14 — Sport-Specific Scoring, PHASE 15 — Score Submission Workflow, PHASE 16 — Disqualification System (+36 more)

### Community 9 - "SportController.php"
Cohesion: 0.06
Nodes (3): {closure#27}(), {closure#30}(), ScoreSheetImageOfficeService

### Community 10 - "User"
Cohesion: 0.13
Nodes (9): User, {closure#1}(), {closure#2}(), {closure#3}(), {closure#1}(), {closure#2}(), {closure#1}(), {closure#2}() (+1 more)

### Community 11 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.13
Nodes (5): AuditLog, CompetitionResult, Course, TeamFlag, TeamTally

### Community 12 - "What You Must Do When Invoked"
Cohesion: 0.08
Nodes (24): For /graphify add and --watch, For /graphify query, For the commit hook and native CLAUDE.md integration, For --update and --cluster-only, /graphify, Honesty Rules, Interpreter guard for subcommands, Part A - Structural extraction for code files (+16 more)

### Community 13 - "3. Database schema"
Cohesion: 0.24
Nodes (7): Database schema, 3.1 Identity and reference data, 3.2 Registration, rules, and staffing, 3.3 Competition and scoring, 3.4 Compliance and history, 3.5 Required indexes and integrity rules, 3. Database schema

### Community 14 - "Illuminate\Support\Str"
Cohesion: 0.18
Nodes (3): {closure#2}(), {closure#1}(), {closure#2}()

### Community 15 - "TwoFactorAuthenticationSettingsTest.php"
Cohesion: 0.20
Nodes (3): {closure#1}(), {closure#3}(), {closure#5}()

### Community 16 - "Illuminate\Http\Request"
Cohesion: 0.18
Nodes (3): {closure#13}(), SportController, Sport

### Community 17 - "Intramurals Management System"
Cohesion: 0.12
Nodes (16): Admin landing page and navigation, Athletes and participation rules, Competition operations, Current implementation state, Current next step, Development conventions, Intramurals edition management, Intramurals Management System (+8 more)

### Community 18 - "2026_09_20_160000_reset_competition_structure_for_edition_centered_flow.php"
Cohesion: 0.22
Nodes (6): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}()

### Community 19 - "2. Functional requirements"
Cohesion: 0.14
Nodes (14): 2. Functional requirements, FR-01 Authentication and access control, FR-02 Admin master data, FR-03 Registrations and participation limits, FR-04 Coordinator management and device policy, FR-05 Event lifecycle and scheduling, FR-06 Results, scoring, and tally, FR-07 Flags and disqualification (+6 more)

### Community 20 - "graphify reference: extra exports and benchmark"
Cohesion: 0.22
Nodes (8): graphify reference: extra exports and benchmark, Step 6b - Wiki (only if --wiki flag), Step 7 - Neo4j export (only if --neo4j or --neo4j-push flag), Step 7a - FalkorDB export (only if --falkordb or --falkordb-push flag), Step 7b - SVG export (only if --svg flag), Step 7c - GraphML export (only if --graphml flag), Step 7d - MCP server (only if --mcp flag), Step 8 - Token reduction benchmark (only if total_words > 5000)

### Community 21 - "Intramurals Management System — Master Implementation TODO"
Cohesion: 0.20
Nodes (8): 1.1 Roles, 1.2 Definitions, 1. Purpose and scope, 6. Routes and screens, 8. Definition of done, Clean-reset direction — approved, Current implementation status (2026-09-20), Intramurals Management System — Master Implementation TODO

### Community 23 - "5. Business rules and workflows"
Cohesion: 0.22
Nodes (9): 5. Business rules and workflows, BR-01 Registration, BR-02 Coordinator authorization, BR-02A Team roster assignment, BR-03 Event state transitions, BR-04 Result approval and tally update, BR-05 Flag/disqualification, BR-06 Device restriction (+1 more)

### Community 24 - "Illuminate\Validation\Rule"
Cohesion: 0.14
Nodes (3): StoreEditionRequest, StoreTeamRequest, UpdateTeamRequest

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

### Community 30 - "PHASE 35 — Security Checklist"
Cohesion: 0.40
Nodes (5): API, Authentication, Authorization, Input security, PHASE 35 — Security Checklist

### Community 31 - "PHASE 30 — Reports"
Cohesion: 0.40
Nodes (5): Athlete participation report, Coordinator report, Event report, Overall tally, PHASE 30 — Reports

### Community 32 - "Illuminate\Database\Migrations\Migration"
Cohesion: 0.15
Nodes (3): {closure#1}(), {closure#1}(), {closure#1}()

### Community 34 - "graphify reference: add a URL and watch a folder"
Cohesion: 0.50
Nodes (3): For /graphify add, For --watch, graphify reference: add a URL and watch a folder

### Community 35 - "graphify reference: commit hook and native CLAUDE.md integration"
Cohesion: 0.50
Nodes (3): For git commit hook, For native CLAUDE.md integration, graphify reference: commit hook and native CLAUDE.md integration

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

### Community 163 - "SportService"
Cohesion: 0.20
Nodes (3): {closure#2}(), {closure#4}(), SportService

### Community 165 - "Q: replace the basketball score sheet with this, but inheret the function autofill name based on the team, and lisence replaced with course."
Cohesion: 0.40
Nodes (4): Answer, Outcome, Q: replace the basketball score sheet with this, but inheret the function autofill name based on the team, and lisence replaced with course., Source Nodes

### Community 166 - "Livewire\Livewire"
Cohesion: 0.11
Nodes (6): {closure#1}(), {closure#1}(), {closure#1}(), {closure#1}(), {closure#3}(), {closure#1}()

### Community 167 - "Illuminate\Support\ServiceProvider"
Cohesion: 0.06
Nodes (7): DeleteUser, AppServiceProvider, BroadcastServiceProvider, FortifyServiceProvider, JetstreamServiceProvider, {closure#1}(), RouteServiceProvider

### Community 168 - "StoreStudentRequest"
Cohesion: 0.14
Nodes (3): SanitizesStudentInput, StoreStudentRequest, UpdateStudentRequest

### Community 171 - "FortifyServiceProvider.php"
Cohesion: 0.12
Nodes (5): EnsureCoordinatorDevice, {closure#2}(), {closure#4}(), {closure#5}(), CoordinatorDeviceService

### Community 172 - "AthleteEntry"
Cohesion: 0.23
Nodes (14): AthleteEntry, TeamMember, {closure#2}(), bulkAssignmentAdmin(), bulkAssignmentEdition(), bulkAssignmentSport(), {closure#1}(), {closure#11}() (+6 more)

### Community 173 - "AuditService"
Cohesion: 0.05
Nodes (15): RegistrationController, EventRegistration, Fixture, ParticipationRule, ResultSubmission, AuditService, {closure#1}(), {closure#2}() (+7 more)

### Community 174 - "Q: swap the competition and team b, make this changes in both the preview and downloadable"
Cohesion: 0.40
Nodes (4): Answer, Outcome, Q: swap the competition and team b, make this changes in both the preview and downloadable, Source Nodes

### Community 176 - "Team"
Cohesion: 0.10
Nodes (10): Team, {closure#5}(), {closure#1}(), {closure#2}(), {closure#4}(), {closure#5}(), {closure#7}(), {closure#10}() (+2 more)

### Community 179 - "BracketMatch"
Cohesion: 0.14
Nodes (3): BracketMatch, {closure#12}(), {closure#13}()

### Community 184 - "BracketService.php"
Cohesion: 0.21
Nodes (8): {closure#1}(), {closure#10}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}(), {closure#9}()

### Community 185 - "CompetitionSchedule"
Cohesion: 0.09
Nodes (9): CompetitionSchedule, ScheduleParticipant, {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}() (+1 more)

### Community 186 - "Illuminate\Foundation\Http\FormRequest"
Cohesion: 0.14
Nodes (4): AssignTeamMemberRequest, StoreCoordinatorChangeRequest, StoreEventRequest, StoreRegistrationRequest

### Community 188 - "CoordinatorService"
Cohesion: 0.14
Nodes (8): CoordinatorAssignment, {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), CoordinatorService

### Community 189 - "PasswordResetTest.php"
Cohesion: 0.15
Nodes (3): {closure#3}(), {closure#5}(), {closure#8}()

### Community 190 - "Illuminate\Database\Schema\Blueprint"
Cohesion: 0.14
Nodes (19): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#1}() (+11 more)

### Community 191 - "TeamManagementTest.php"
Cohesion: 0.51
Nodes (12): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}() (+4 more)

### Community 193 - "BracketCompetitor"
Cohesion: 0.25
Nodes (4): {closure#20}(), {closure#22}(), BracketCompetitor, {closure#10}()

### Community 197 - "Q: inheret the format from preview to the download"
Cohesion: 0.40
Nodes (4): Answer, Outcome, Q: inheret the format from preview to the download, Source Nodes

### Community 198 - "Symfony\Component\HttpFoundation\Response"
Cohesion: 0.24
Nodes (3): EnsureUserHasRole, RedirectIfAuthenticated, ReturnAdminSavesToModuleIndex

### Community 201 - "BracketEngineTest.php"
Cohesion: 0.26
Nodes (12): bracketEngineEdition(), bracketEngineSport(), {closure#1}(), {closure#11}(), {closure#12}(), {closure#2}(), {closure#3}(), {closure#4}() (+4 more)

### Community 207 - "2026_09_20_130000_create_courses_and_add_course_to_students_table.php"
Cohesion: 0.33
Nodes (3): {closure#1}(), {closure#2}(), {closure#3}()

### Community 213 - "2026_09_21_170000_add_universal_competitors_to_brackets.php"
Cohesion: 0.29
Nodes (3): {closure#1}(), {closure#2}(), {closure#4}()

### Community 216 - "Student"
Cohesion: 0.13
Nodes (8): Student, {closure#3}(), {closure#4}(), {closure#5}(), {closure#1}(), {closure#2}(), {closure#4}(), {closure#1}()

### Community 218 - "CoordinatorManagementTest.php"
Cohesion: 0.44
Nodes (7): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#6}(), coordinatorModuleAdmin(), coordinatorModuleEvent()

### Community 220 - "StudentManagementTest.php"
Cohesion: 0.54
Nodes (7): activeAdmin(), {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), validStudentData()

### Community 221 - "EmailVerificationTest.php"
Cohesion: 0.22
Nodes (3): {closure#1}(), {closure#3}(), {closure#5}()

### Community 222 - "require-dev"
Cohesion: 0.22
Nodes (9): require-dev, fakerphp/faker, laravel/pint, laravel/sail, mockery/mockery, nunomaduro/collision, pestphp/pest, pestphp/pest-plugin-laravel (+1 more)

### Community 223 - "composer.json"
Cohesion: 0.25
Nodes (7): description, keywords, license, minimum-stability, name, prefer-stable, type

### Community 224 - "config"
Cohesion: 0.29
Nodes (7): pestphp/pest-plugin, php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 227 - "CoordinatorDevice"
Cohesion: 0.38
Nodes (4): CoordinatorDevice, {closure#1}(), {closure#2}(), {closure#5}()

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

### Community 233 - "VolleyballScoreSheetTest.php"
Cohesion: 0.70
Nodes (4): {closure#1}(), {closure#2}(), {closure#3}(), volleyballScoreSheetScenario()

### Community 237 - "landing.js"
Cohesion: 0.40
Nodes (4): bannerCloths, resultsControl, resultsPopover, resultsToggle

## Knowledge Gaps
- **300 isolated node(s):** `name`, `type`, `description`, `keywords`, `license` (+295 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 750 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **167 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Work-memory lessons

**Preferred sources** — corroborated by past sessions; start here.
- `basketball-score-sheet-pdf.blade.php` (3× useful, score=2.999429679) _(code changed — re-verify)_

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `IntramuralEdition`, `Illuminate\Support\Facades\Hash`, `EditionSport`, `Controller`, `Illuminate\Support\Str`, `TwoFactorAuthenticationSettingsTest.php`, `CoordinatorController`, `Livewire\Livewire`, `Illuminate\Support\ServiceProvider`, `StoreCoordinatorRequest`, `FortifyServiceProvider.php`, `AthleteEntry`, `CoordinatorController.php`, `CompetitionSchedule`, `CoordinatorService`, `PasswordResetTest.php`, `TeamManagementTest.php`, `Event`, `BracketEngineTest.php`, `SystemLogController.php`, `Student`, `CoordinatorManagementTest.php`, `StudentManagementTest.php`, `EmailVerificationTest.php`, `CoordinatorDevice`, `VolleyballScoreSheetTest.php`?**
  _High betweenness centrality (0.126) - this node is a cross-community bridge._
- **Why does `IntramuralEdition` connect `IntramuralEdition` to `Illuminate\View\View`, `TeamManagementTest.php`, `CompetitionSchedule`, `EditionSport`, `EventController.php`, `SportController.php`, `Illuminate\Database\Eloquent\Model`, `BracketEngineTest.php`, `AuditService`, `3. Database schema`, `AthleteEntry`, `Illuminate\Http\Request`, `Team`, `VolleyballScoreSheetTest.php`, `TeamController.php`, `Illuminate\Validation\Rule`, `Illuminate\Http\RedirectResponse`, `Student`?**
  _High betweenness centrality (0.051) - this node is a cross-community bridge._
- **Why does `4. Application services and key functions` connect `AuditService` to `Student`, `IntramuralEdition`, `CoordinatorService`, `Intramurals Management System — Master Implementation TODO`?**
  _High betweenness centrality (0.038) - this node is a cross-community bridge._
- **What connects `name`, `type`, `description` to the rest of the system?**
  _300 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `IntramuralEdition` be split into smaller, more focused modules?**
  _Cohesion score 0.06384180790960452 - nodes in this community are weakly interconnected._
- **Should `Illuminate\Support\Facades\Hash` be split into smaller, more focused modules?**
  _Cohesion score 0.1076923076923077 - nodes in this community are weakly interconnected._
- **Should `Controller` be split into smaller, more focused modules?**
  _Cohesion score 0.08870967741935484 - nodes in this community are weakly interconnected._