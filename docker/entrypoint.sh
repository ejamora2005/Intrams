#!/bin/sh
set -eu

run_artisan() {
    php artisan "$@"
}

require_env() {
    name="$1"
    value="$(printenv "$name" || true)"

    if [ -z "$value" ]; then
        echo "Missing required environment variable: $name" >&2
        exit 1
    fi
}

wait_for_mysql() {
    if [ "${DB_CONNECTION:-mysql}" != "mysql" ]; then
        return 0
    fi

    attempts=0
    until php -r '
        $host = getenv("DB_HOST") ?: "127.0.0.1";
        $port = getenv("DB_PORT") ?: "3306";
        $database = getenv("DB_DATABASE") ?: "";
        $username = getenv("DB_USERNAME") ?: "";
        $password = getenv("DB_PASSWORD") ?: "";
        try {
            new PDO("mysql:host={$host};port={$port};dbname={$database}", $username, $password, [
                PDO::ATTR_TIMEOUT => 3,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (Throwable $e) {
            fwrite(STDERR, $e->getMessage());
            exit(1);
        }
    ' >/dev/null 2>&1; do
        attempts=$((attempts + 1))
        if [ "$attempts" -ge 60 ]; then
            echo "Database is still unavailable after 60 seconds." >&2
            exit 1
        fi

        echo "Waiting for database connection..."
        sleep 2
    done
}

require_env APP_KEY

mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache

run_artisan optimize:clear
run_artisan package:discover --ansi
run_artisan storage:link --force

if [ "${DEPLOY_RUN_MIGRATIONS:-false}" = "true" ]; then
    wait_for_mysql
    run_artisan migrate --force
fi

if [ "${DEPLOY_SEED_DEFAULT_COMPETITIONS:-false}" = "true" ]; then
    wait_for_mysql
    run_artisan db:seed --class=DefaultIntramuralsSeeder --force
fi

if [ "${DEPLOY_SEED_DEFAULT_ACCOUNTS:-false}" = "true" ]; then
    wait_for_mysql
    run_artisan db:seed --class=AdminUserSeeder --force
fi

run_artisan config:cache
run_artisan event:cache
run_artisan view:cache

exec "$@"
