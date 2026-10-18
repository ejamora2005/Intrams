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

## Update the supplied roster

Redeploy the latest code before running these commands in the `intrams` app container terminal:

```bash
cd /var/www/html
php artisan migrate --force
php artisan db:seed --class=CurrentFactionRostersSeeder --force
```

The October 18, 2026 Google Sheets snapshot contains 192 Sea Dragons, 161 Terraquatic Eagles, and 148 Trojan Warriors (501 faction members and 518 sports registrations). Duplicate source names are combined and their sports are united. Explicit sport codes match the system's formats and Men/Women categories. Cultural-only and support-only names remain faction members without new event registrations. Existing cultural registrations are preserved; missing academic details use `N/A`. Source provenance and import decisions are recorded in `database/seeders/rosters/source.json`.

Run this import once, then keep `DEPLOY_SEED_DEFAULT_COMPETITIONS=false` so subsequent deployments preserve roster edits made in the app. This command updates all three rosters in one transaction and does not reset accounts, schedules, wins, results, or points. Existing pair assignments are retained when the source does not specify a pair. It expects the default edition, teams, and sports to exist; for a new empty database, run `php artisan db:seed --class=DefaultIntramuralsSeeder --force` instead. If an outdated sports registration is used in a schedule, the entire roster import rolls back instead of removing that history. Known old name aliases are consolidated; ambiguous individual/pair bracket duplicates require review.

## Notes

- Dokploy should expose the `intrams` service, not the `mysql` service.
- The app service uses `expose: 80`; Dokploy/Traefik should handle public routing and HTTPS.
- Uploaded/generated storage is persisted in the `intrams_storage` Docker volume.
- MySQL data is persisted in the `intrams_mysql` Docker volume.
- The container runs `config:cache`, `event:cache`, and `view:cache` at startup. Routes are not cached because this project still uses closure routes.
- The account seeder only creates default Admin, GAM, and Tabulator accounts when `INTRAMURALS_DEFAULT_ACCOUNT_PASSWORD` is set.
- `DEPLOY_SEED_DEFAULT_COMPETITIONS=true` creates the default event/edition named `SLSUBC INTRAMURALS 2026`, then attaches the default teams, sports, cultural events, point systems, proposal schedules, and the supplied student roster.
