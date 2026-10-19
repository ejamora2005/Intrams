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

## Add newly listed players

Redeploy the latest code, then run this in the `intrams` app container terminal:

```bash
cd /var/www/html
php artisan db:seed --class=NewFactionPlayersSeeder --force
```

The October 19 Google Sheets additions contain 26 new Terraquatic members and 14 sports registrations. The later update also adds Basketball 3x3 (Women) to Jessa Mae Arca; four other updated basketball listings already exist. Repeated new names are combined, sports match the system's Men/Women categories, and cultural/support-only names remain roster members. There are no new Trojan or Sea Dragons names. The bundled roster now has 557 members and 537 sports registrations: Sea Dragons 192, Terraquatic Eagles 189, and Trojan Warriors 176.

This additions seeder creates missing players and appends only the specific new registration in `database/seeders/rosters/registration_additions.json`. Existing profiles and unrelated sports stay untouched. Existing entries, including withdrawn registrations, are never reactivated. Missing/inactive players are skipped for registration updates. It does not remove memberships or registrations or reset accounts, schedules, wins, results, or points. Rerunning it is safe. Imports and added registrations are recorded in System Logs. Keep `DEPLOY_SEED_DEFAULT_COMPETITIONS=false` after setup so deployments do not run the full replacement import. Source rows and import decisions are recorded in `database/seeders/rosters/source.json`.

## Replace the supplied roster

Redeploy the latest code before running these commands in the `intrams` app container terminal:

```bash
cd /var/www/html
php artisan migrate --force
php artisan db:seed --class=CurrentFactionRostersSeeder --force
```

The full replacement uses the October 18 base snapshot plus the October 19 additions (557 members and 537 sports registrations). Expanded names for Christine Belinario Alvero, Danicha Jane Cabudbud Gomez, and Kristine Jamero Lapasanda are linked to their earlier names so rerunning the seeder retains their existing records. Duplicate source names are combined and their sports are united. Explicit sport codes match the system's formats and Men/Women categories. Cultural-only and support-only names remain faction members without new event registrations. Existing cultural registrations are preserved; missing academic details use `N/A`. Source provenance and import decisions are recorded in `database/seeders/rosters/source.json`.

Run this import once, then keep `DEPLOY_SEED_DEFAULT_COMPETITIONS=false` so subsequent deployments preserve roster edits made in the app. This command updates all three rosters in one transaction and does not reset accounts, schedules, wins, results, or points. Existing pair assignments are retained when the source does not specify a pair. It expects the default edition, teams, and sports to exist; for a new empty database, run `php artisan db:seed --class=DefaultIntramuralsSeeder --force` instead. If an outdated sports registration is used in a schedule, the entire roster import rolls back instead of removing that history. Known old name aliases are consolidated; ambiguous individual/pair bracket duplicates require review.

## Player save and coordinator repairs (October 20)

Redeploy the latest code, then run in the `intrams` app terminal:

```bash
cd /var/www/html
php artisan migrate --force
```

Player edits now preserve the displayed student identifier when the form does not submit it. Previously, the first edit could replace it with an empty string and later edits failed with a duplicate empty identifier. The repair migration restores only empty identifiers that have a verified, unused original value in `student.updated` audit history. It leaves other profile fields, registrations, schedules, and scores alone; it does not invent an identifier if history is unavailable. Recovered identifiers are recorded as `student.identifier_restored` in System Logs.

The other repair creates only missing event/coordinator assignment/request tables, which an older edition-flow migration removed although those pages still use them. Existing tables and records are retained. Sports edits also preserve cultural registrations and paired entries managed from event participant pages. Do not run a roster replacement seeder for these fixes.

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
- `DEPLOY_SEED_DEFAULT_COMPETITIONS=true` creates the default event/edition named `SLSUBC INTRAMURALS 2026`, then attaches the default teams, sports, cultural events, point systems, proposal schedules, and the supplied student roster.
