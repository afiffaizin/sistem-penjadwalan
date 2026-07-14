#!/bin/bash
set -e

echo "========================================"
echo " SiJadwal Laravel — Entrypoint"
echo "========================================"

#  0. Ensure storage directory structure exists (volume may be empty) 
mkdir -p storage/app/public \
         storage/framework/cache/data \
         storage/framework/sessions \
         storage/framework/views \
         storage/logs

#  1. Ensure .env exists 
if [ ! -f .env ]; then
    if [ -f .env.docker ]; then
        echo "[entrypoint] .env not found, copying from .env.docker..."
        cp .env.docker .env
    else
        echo "[entrypoint] .env not found, copying from .env.example..."
        cp .env.example .env
    fi
fi

#  2. Generate APP_KEY if empty 
if grep -q "^APP_KEY=$" .env 2>/dev/null || ! grep -q "^APP_KEY=" .env 2>/dev/null; then
    echo "[entrypoint] Generating APP_KEY..."
    php artisan key:generate --force
fi

#  3. Wait for database to be ready 
echo "[entrypoint] Waiting for database..."
MAX_TRIES=30
COUNT=0
until mysqladmin ping -h "${DB_HOST:-db}" -u "${DB_USERNAME:-root}" -p"${DB_PASSWORD:-secret}" --ssl=0 --silent 2>/dev/null; do
    COUNT=$((COUNT + 1))
    if [ $COUNT -ge $MAX_TRIES ]; then
        echo "[entrypoint] ERROR: Database not reachable after ${MAX_TRIES} attempts."
        exit 1
    fi
    echo "[entrypoint] Database not ready yet... (${COUNT}/${MAX_TRIES})"
    sleep 2
done
echo "[entrypoint] Database is ready!"

#  4. Run migrations 
echo "[entrypoint] Running migrations..."
php artisan migrate --force

#  5. Seed database (only if users table is empty) 
USER_COUNT=$(php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null | tail -1)
if [ "$USER_COUNT" = "0" ] || [ -z "$USER_COUNT" ]; then
    echo "[entrypoint] Seeding database..."
    php artisan db:seed --force
fi

#  6. Clear stale cache then rebuild (route:cache skipped — app uses Closure middleware)
php artisan config:clear
php artisan view:clear
php artisan config:cache
php artisan view:cache

#  7. Fix storage link 
php artisan storage:link 2>/dev/null || true

#  8. Ensure correct permissions 
chown -R www-data:www-data storage bootstrap/cache

echo "========================================"
echo " SiJadwal Laravel — Ready!"
echo "========================================"

exec "$@"
