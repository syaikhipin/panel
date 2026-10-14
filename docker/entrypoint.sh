#!/bin/sh
set -e

# One connection string in, both config styles out (api reads DB_URL, panel reads DATABASE_URL).
url="${DB_URL:-${DATABASE_URL:-}}"
[ -n "$url" ] && export DB_URL="$url" DATABASE_URL="$url"

# The panel authenticates to the bundled API with PLOI_TOKEN.
export PLOI_STUB_TOKEN="${PLOI_STUB_TOKEN:-${PLOI_TOKEN:-}}"
: "${PLOI_API_URL:=http://127.0.0.1:8081/}"
export PLOI_API_URL

# When the platform provides its own PORT, serve the panel on exactly that port;
# otherwise Caddy already listens on both :80 and :3000 (covers Dokploy defaults).
if [ -n "$PORT" ] && [ "$PORT" != "80" ] && [ "$PORT" != "3000" ]; then
    sed -i "s/:80, :3000/:${PORT}/" /etc/caddy/Caddyfile
fi

if [ -z "$APP_KEY" ]; then
    echo "WARNING: APP_KEY is not set — generating an ephemeral key. Set APP_KEY in the environment for a stable key across deploys." >&2
    export APP_KEY="base64:$(head -c 32 /dev/urandom | base64)"
fi

migrate() {
    tries=0
    until (cd "$1" && php artisan migrate --force); do
        tries=$((tries + 1))
        if [ "$tries" -ge 10 ]; then
            echo "ERROR: migrations in $1 failed after ${tries} attempts." >&2
            exit 1
        fi
        echo "Database not ready — retrying in 3s (${tries}/10)..." >&2
        sleep 3
    done
}

migrate /var/www/api
migrate /var/www/panel

if [ -n "$ADMIN_EMAIL" ] && [ -n "$ADMIN_PASSWORD" ]; then
    export ADMIN_EMAIL ADMIN_PASSWORD ADMIN_NAME
    (cd /var/www/panel && php artisan tinker --execute='
        if (!\App\Models\User::query()->where("email", getenv("ADMIN_EMAIL"))->exists()) {
            \App\Models\User::forceCreate([
                "name" => getenv("ADMIN_NAME") ?: "Admin",
                "email" => getenv("ADMIN_EMAIL"),
                "password" => getenv("ADMIN_PASSWORD"),
                "role" => \App\Models\User::ADMIN,
            ]);
            echo "Admin user created.\n";
        }
    ')
fi

# Publish Filament assets (idempotent).
(cd /var/www/panel && php artisan filament:assets)

exec "$@"
