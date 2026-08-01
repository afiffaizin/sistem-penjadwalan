#!/bin/bash
set -e

echo "[queue-worker] Preparing environment..."

# Ensure storage directories exist (shared volume)
mkdir -p storage/app/public \
         storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/logs

# Ensure .env exists
if [ ! -f .env ]; then
    if [ -f .env.docker ]; then
        cp .env.docker .env
    else
        cp .env.example .env
    fi
fi

# Generate APP_KEY if empty
if grep -q "^APP_KEY=$" .env 2>/dev/null || ! grep -q "^APP_KEY=" .env 2>/dev/null; then
    php artisan key:generate --force
fi

# Wait for database
echo "[queue-worker] Waiting for database..."
MAX_TRIES=30
COUNT=0
until mysqladmin ping -h "${DB_HOST:-db}" -u "${DB_USERNAME:-root}" -p"${DB_PASSWORD:-secret}" --ssl=0 --silent 2>/dev/null; do
    COUNT=$((COUNT + 1))
    if [ $COUNT -ge $MAX_TRIES ]; then
        echo "[queue-worker] ERROR: Database not reachable."
        exit 1
    fi
    sleep 2
done

php artisan config:cache

echo "[queue-worker] Starting queue worker..."
exec php artisan queue:work --timeout=0 --tries=1 --sleep=3
