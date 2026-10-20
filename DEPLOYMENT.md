# Deployment

This project is ready for a Dokploy Docker Compose deployment on `slsubc.tech`.

## GAM dashboard archived-record fix

Deploy the latest code; no migration or seeder is needed. Medical review excludes registrations whose student has been archived, and Ready Matches excludes brackets whose sport has been deleted. These historical registrations and brackets remain in the database. Scores, declared winners and rosters are not rewritten.

The PHPUnit bootstrap ignores the development configuration cache and checks that its connection targets the local `intrams_test` database before any database refresh starts.

## Dokploy Setup

1. Point the DNS `A` record for `slsubc.tech` to the VPS public IP.
2. In Dokploy, create a Docker Compose application from this repository.
3. Use `docker-compose.yml` as the compose file.
4. Add the domain `slsubc.tech` in Dokploy's Domains tab and set the target port to `80`.
5. Add the environment variables from `.env.dokploy.example` in Dokploy.
6. Generate and set `APP_KEY` before deploying:

```bash
php artisan key:generate --show
```

7. Set strong values for `DB_PASSWORD`, `DB_ROOT_PASSWORD`, and `INTRAMURALS_DEFAULT_ACCOUNT_PASSWORD`.
8. For the first deploy only, set these flags to `true`:

```env
DEPLOY_RUN_MIGRATIONS=true
DEPLOY_SEED_DEFAULT_ACCOUNTS=true
DEPLOY_SEED_DEFAULT_COMPETITIONS=true
```

9. Deploy. After the first successful deploy, set the seed flags back to `false`.

## Keep the live Dokploy roster authoritative

Bundled/local roster imports are disabled when `APP_ENV=production`, even with `db:seed --force`. This covers `CurrentFactionRostersSeeder`, `NewFactionPlayersSeeder`, all three individual faction seeders, and the legacy roster cleanup. They print a skip message and leave live players, memberships and registrations alone. They cannot restore a removed source name or overwrite a player edited in Admin/GAM. Local and testing environments retain their development seed data.

`DefaultIntramuralsSeeder` also skips all competition setup when any production edition already exists. If the deployment seed flag is accidentally left enabled, restarting or redeploying does not replay setup over the live competition. A fresh production database receives only competition configuration and factions, without bundled players. The setup seeder does not delete production players whose identifier starts with `TEST-`.

Keep `DEPLOY_SEED_DEFAULT_COMPETITIONS=false` and `DEPLOY_SEED_DEFAULT_ACCOUNTS=false` after first setup. Add or edit live players through Admin/GAM. Do not change `APP_ENV` to bypass this protection or use `migrate:fresh` on Dokploy. This protection does not delete any names already in production or reset scores, winners or schedules.

## Public programme from the Sked sheet (October 20)

For the current 2026 edition, the login/schedule page initially uses the October 19–23 `Sked` snapshot in `config/public_programme.php`. The source spreadsheet URL and cell ranges are recorded there. Repeated cells are grouped by session and venue. AM/PM remain session labels where no exact hour is supplied. Day 1 Badminton ends at 3:00 PM, Day 2 Basketball 3x3 ends at 2:00 PM, and Day 3 Awarding Mugna starts at 7:00 PM (Philippine time). Day 1's unspecified venue stays TBA.

The public board displays only this programme, with separate sports, cultural and campus activity sections. Existing sport/bracket schedules stay linked and accessible internally; they are not displayed on this edition's public board. Sports, registrations, brackets, declared results, score details and standings are not updated or deleted. Other editions retain their existing schedule behavior.

Administrators can edit these sessions at **Schedules** (`/admin/schedules`), including event name, date, session, exact start time, cutoff, category, venue and public visibility. Times use Philippine time (UTC+8). The first save copies the current programme into the new `public_programmes` table; subsequent saves edit that stored copy. Redeployments and bundled snapshot changes do not overwrite Admin edits or restore hidden sessions. Source-sheet edits are not synchronized automatically. Concurrent changes to the same session are rejected with a reload message instead of overwriting the other administrator's save.

Keep `DEPLOY_RUN_MIGRATIONS=true` to create the new table during deployment. For a manual schema update, run only:

```bash
php artisan migrate --path=database/migrations/2026_10_20_060000_create_public_programmes_table.php --force
```

No seeder or score reset is needed. Opening the page does not create programme records; only an authorized save does.

## Game schedule and logos (October 20)

Schedule management is under **Admin → Schedules**. The **Public timetable** section supports creating, editing, hiding and deleting public events. The **Bracket / event schedules** section owns internal schedule CRUD, exact hours, venues and optional bracket associations. Live Competition is a monitoring page with links to Schedules and the brackets; its schedule mutation routes have moved under `/admin/schedules/matches`. The old create-page URL redirects to Schedules.

Each public game can link one or more actual bracket matches (for example, the Men's and Women's divisions). Teams, faction logos, declared match winners and saved match hours follow the linked bracket. The editor can override opponent labels without changing bracket competitors. Links retain the sport/round/game position, so replacing match IDs during a lineup rebuild does not disconnect them. Selecting **Save these times to the selected bracket matches** explicitly updates or creates their internal schedule; exact start/end dates are required. The checkbox resets after saving, so later public text edits do not replay stale timing over another admin's changes.

Deleting a public event does not delete linked matches or internal schedules. Deleting an internal schedule preserves bracket results and sport points; schedules with saved result records cannot be deleted. A scored match cannot be reassigned to a different bracket. No migration, roster seeder or score reset is needed for this update.

`Schedule-Games-2026.txt` adds the confirmed October 21–23 hours, venues and Game 1–3 pairings in `config/public_programme_games.php`. The user confirmed October 23 Modified Volleyball starts at **11 AM**, followed by the employee event at 1 PM. Russian Softball moves to October 22 at 1 PM. The old separate afternoon Basketball 5x5 and Beach Volleyball placeholders are hidden because their new three-game series already covers them; they remain editable in Admin. Unlisted events keep their existing details.

With `DEPLOY_RUN_MIGRATIONS=true`, migration `2026_10_20_070000_update_public_programme_game_schedule.php` amends only the matching sessions in any saved active 2026 public programme. It records a one-time marker and audit entry, preserves unrelated Admin edits and public visibility, and never changes sports, internal schedules, brackets, participants or scores. Programmes without a saved copy use the amended defaults immediately. Later redeployments do not replay the file over subsequent Admin changes.

Admin → Schedules → Edit schedule now includes **Games / matchups**. Add or remove games and select factions or the winner/loser of an earlier game. Faction logos and names render on Admin cards, in the editor preview and on the public timetable. Undecided opponents stay labeled as winner/loser placeholders; no result is invented or declared. Times are session start times from the file; no separate hours are invented for subsequent games.

## Doubles selections in student forms (October 20)

Redeploy the latest code; no roster seeding or new migration is required. Admin and GAM can add, keep, remove and reselect Badminton/Table Tennis doubles from student sport forms. Keeping a doubles selection preserves its pair key and medical review. Removing it deactivates only that student's entry and retains history; the other player is not automatically withdrawn. Sport management can pair registered players who do not have a complete active pair, reusing their existing entries. Complete pairs cannot be reassigned through a stale pairing form. Gender and participation limits still apply.

## Basketball 3x3 points correction (October 20)

Redeploy, then run `php artisan migrate --force` in `/var/www/html`. Basketball 3x3 Men and Women receive their own 15 / 10 / 7 point group while remaining Minor events for participation rules. Existing winner placements, declaration times, bracket wins, schedules and rosters are kept. The migration updates the declared point details and recalculates standings. Other minor sports retain their point values. Subsequent changes saved in Admin > Points System recalculate already declared points and standings without changing winners; no redeclaration or roster seeding is needed.

## Three-place scoring and declaration times (October 20)

Redeploy, then run `php artisan migrate --force` in `/var/www/html`. Current athletics events and other current four-place scoring rules become Champion / 1st Runner-up / 2nd Runner-up with 10 / 7 / 5 points. Other three-place scoring groups keep their values. The migration recalculates standings from existing declared winners; it does not reset results or bracket wins. Original fourth-place assignments remain as history and no longer earn points. Submission times and submitters are preserved. New declaration times are stored in UTC and displayed as Philippine time, explicitly marked PHT (UTC+8).

## Correct Chess and Badminton formats (October 20)

Redeploy, then run `php artisan migrate --force` in `/var/www/html`. This makes Chess (Men/Women) team events and keeps all three Badminton formats (Singles, Doubles, Team) for Men and Women. Existing team-event entries use the player's current faction. Registration IDs, badminton pairs, schedules, scores, declared winners, and standings are preserved. No roster replacement or score reset is needed. Doubles are listed in player forms with a reminder to assign both partners from Admin sport participant management.

## Bundled roster additions (local development only)

`NewFactionPlayersSeeder` is a local/testing data tool. It is blocked in production; use Admin/GAM for live additions.

The October 19 Google Sheets additions contain 26 new Terraquatic members and 14 sports registrations. The later update also adds Basketball 3x3 (Women) to Jessa Mae Arca; four other updated basketball listings already exist. Repeated new names are combined, sports match the system's Men/Women categories, and cultural/support-only names remain roster members. There are no new Trojan or Sea Dragons names. The bundled roster now has 557 members and 537 sports registrations: Sea Dragons 192, Terraquatic Eagles 189, and Trojan Warriors 176.

In local/testing environments, this additions seeder creates missing source players and appends only the specific new registration in `database/seeders/rosters/registration_additions.json`. Existing profiles and unrelated sports stay untouched. Existing entries, including withdrawn registrations, are never reactivated. Missing/inactive players are skipped for registration updates. Imports and added registrations are recorded in System Logs. Source rows and import decisions are recorded in `database/seeders/rosters/source.json`.

## Bundled roster replacement (local development only)

`CurrentFactionRostersSeeder` is blocked in production. Do not run local roster replacement commands against Dokploy.

The full replacement uses the October 18 base snapshot plus the October 19 additions (557 members and 537 sports registrations). Expanded names for Christine Belinario Alvero, Danicha Jane Cabudbud Gomez, and Kristine Jamero Lapasanda are linked to their earlier names so rerunning the seeder retains their existing records. Duplicate source names are combined and their sports are united. Explicit sport codes match the system's formats and Men/Women categories. Cultural-only and support-only names remain faction members without new event registrations. Existing cultural registrations are preserved; missing academic details use `N/A`. Source provenance and import decisions are recorded in `database/seeders/rosters/source.json`.

In local/testing environments, this importer updates all three rosters in one transaction. Existing pair assignments are retained when the source does not specify a pair. It expects the default edition, teams, and sports to exist. If an outdated sports registration is used in a schedule, the entire roster import rolls back instead of removing that history. Known old name aliases are consolidated; ambiguous individual/pair bracket duplicates require review.

## Player save and coordinator repairs (October 20)

Redeploy the latest code, then run in the `intrams` app terminal:

```bash
cd /var/www/html
php artisan migrate --force
```

Player edits now preserve the displayed student identifier when the form does not submit it. Previously, the first edit could replace it with an empty string and later edits failed with a duplicate empty identifier. The repair migration restores only empty identifiers that have a verified, unused original value in `student.updated` audit history. It leaves other profile fields, registrations, schedules, and scores alone; it does not invent an identifier if history is unavailable. Recovered identifiers are recorded as `student.identifier_restored` in System Logs.

The other repair creates only missing event/coordinator assignment/request tables, which an older edition-flow migration removed although those pages still use them. Existing tables and records are retained. Sports edits also preserve cultural registrations and paired entries managed from event participant pages. Player selectors hide superseded open sports when the same edition has Men/Women divisions, and stale submissions receive validation. Do not run a roster replacement seeder for these fixes.

## Activity logging

After deploying, run `php artisan migrate --force` to add indexes for the growing activity history. No existing activity records are removed.

System Logs records staff page visits, searches, form submissions, exports, authentication, and failed or denied requests across all workspaces. Request details include the actor, route, IP, outcome, and model changes with before/after values. Related records from one action share a request ID. Passwords, tokens, recovery codes, and file contents are redacted. Date filters and displayed times use UTC+8. Health checks and anonymous public page views are excluded. New activity is captured from deployment onward; previously unrecorded history cannot be reconstructed.

Roster additions and their imports are also logged. Audit storage failures fall back to the Laravel application log without interrupting scoring or roster saves.

## Notes

- Dokploy should expose the `intrams` service, not the `mysql` service.
- The app service uses `expose: 80`; Dokploy/Traefik should handle public routing and HTTPS.
- Uploaded/generated storage is persisted in the `intrams_storage` Docker volume.
- MySQL data is persisted in the `intrams_mysql` Docker volume.
- The container runs `config:cache`, `event:cache`, and `view:cache` at startup. Routes are not cached because this project still uses closure routes.
- The account seeder only creates default Admin, GAM, and Tabulator accounts when `INTRAMURALS_DEFAULT_ACCOUNT_PASSWORD` is set.
- `DEPLOY_SEED_DEFAULT_COMPETITIONS=true` bootstraps the default edition, factions, sports, cultural events, point systems and proposal schedules only on an empty production setup. Existing production editions are skipped, and bundled student rosters are never imported in production.
