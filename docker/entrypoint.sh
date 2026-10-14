#!/bin/sh
set -e

cd /var/www/html

# Support both DB_URL and the conventional DATABASE_URL used by most PaaS.
export DB_URL="${DB_URL:-${DATABASE_URL:-}}"

if [ -z "$APP_KEY" ]; then
    echo "WARNING: APP_KEY is not set — generating an ephemeral key. Set APP_KEY in the environment for a stable key across deploys." >&2
    export APP_KEY="base64:$(head -c 32 /dev/urandom | base64)"
fi

# The external database may still be starting — retry briefly.
tries=0
until php artisan migrate --force; do
    tries=$((tries + 1))
    if [ "$tries" -ge 10 ]; then
        echo "ERROR: migrations failed after ${tries} attempts." >&2
        exit 1
    fi
    echo "Database not ready — retrying in 3s (${tries}/10)..." >&2
    sleep 3
done

exec "$@"
