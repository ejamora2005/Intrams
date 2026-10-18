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
php artisan db:seed --class=DefaultIntramuralsSeeder --force
```

The current roster contains 191 Sea Dragons, 161 Terraquatic Eagles, and 143 Trojan Warriors (495 faction members). The seeder synchronizes their sports and cultural registrations with the supplied JSON sheets. Support-only members remain on the faction roster without an event registration; missing academic details use `N/A`.

Run this import once, then keep `DEPLOY_SEED_DEFAULT_COMPETITIONS=false` so subsequent deployments preserve roster edits made in the app. This command does not run the account seeder. If an outdated athlete registration is already used in a schedule, the affected faction import stops with a message instead of removing that scheduled registration.

## Notes

- Dokploy should expose the `intrams` service, not the `mysql` service.
- The app service uses `expose: 80`; Dokploy/Traefik should handle public routing and HTTPS.
- Uploaded/generated storage is persisted in the `intrams_storage` Docker volume.
- MySQL data is persisted in the `intrams_mysql` Docker volume.
- The container runs `config:cache`, `event:cache`, and `view:cache` at startup. Routes are not cached because this project still uses closure routes.
- The account seeder only creates default Admin, GAM, and Tabulator accounts when `INTRAMURALS_DEFAULT_ACCOUNT_PASSWORD` is set.
- `DEPLOY_SEED_DEFAULT_COMPETITIONS=true` creates the default event/edition named `SLSUBC INTRAMURALS 2026`, then attaches the default teams, sports, cultural events, point systems, proposal schedules, and the supplied student roster.
