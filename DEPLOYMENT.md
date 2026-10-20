# Deployment

This project is ready for a Dokploy Docker Compose deployment on `slsubc.tech`.

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
